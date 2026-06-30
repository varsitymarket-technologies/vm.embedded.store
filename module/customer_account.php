<?php
#   TITLE   : Customer Account Module
#   DESC    : Post-login customer account operations: order history,
#             profile updates, password change, and address book CRUD.
#             All public functions take database_manager $db and the
#             resolved int $customerId as the first two arguments so
#             they are testable in isolation. Customer id is never
#             accepted from request bodies — the API layer resolves it
#             from the X-Customer-Token bearer first.
#   PROPRIETOR: VARSITYMARKET_TECHNOLOGIES

require_once __DIR__ . '/customer_auth.php';

if (!defined('CUSTOMER_ACCOUNT_MAX_ORDERS')) {
    define('CUSTOMER_ACCOUNT_MAX_ORDERS', 100);
}

// ===== Orders =====

/**
 * Return the most recent orders for this customer (capped at
 * CUSTOMER_ACCOUNT_MAX_ORDERS), with items decoded.
 *
 * @return array{ok:bool, orders:array<int,array>}
 */
function customer_my_orders(database_manager $db, int $customerId): array
{
    // SELECT * because customer_phone / customer_address are added to
    // the orders table dynamically at checkout time (see api/index.php
    // checkout_complete handler). A site with no orders yet may not
    // have those columns, and naming them explicitly would make the
    // SELECT fail with a swallowed PDOException. Read fields with
    // null-coalesce so missing columns return null cleanly.
    $rows = $db->query(
        "SELECT * FROM orders WHERE customer_id = ?
          ORDER BY created_at DESC, id DESC
          LIMIT " . (int)CUSTOMER_ACCOUNT_MAX_ORDERS,
        [$customerId]
    );

    $orders = [];
    foreach ($rows as $r) {
        $orders[] = [
            'id' => (int)$r['id'],
            'customer_name' => $r['customer_name'] ?? null,
            'customer_email' => $r['customer_email'] ?? null,
            'customer_phone' => $r['customer_phone'] ?? null,
            'customer_address' => $r['customer_address'] ?? null,
            'total_amount' => (float)$r['total_amount'],
            'items' => json_decode($r['items'] ?? '[]', true) ?: [],
            'status' => $r['status'] ?? 'pending',
            'created_at' => $r['created_at'] ?? null,
        ];
    }

    return ['ok' => true, 'orders' => $orders];
}

// ===== Profile =====

/**
 * Update name and/or phone. Email and password are intentionally NOT
 * touched by this endpoint.
 *
 * @return array{ok:bool, error?:string, customer?:array}
 */
function customer_update_profile(database_manager $db, int $customerId, ?string $name, ?string $phone): array
{
    $nameSet  = ($name !== null && $name !== '');
    $phoneSet = ($phone !== null && $phone !== '');
    if (!$nameSet && !$phoneSet) {
        return ['ok' => false, 'error' => 'At least one of name or phone must be provided'];
    }

    $sets = [];
    $params = [];
    if ($nameSet)  { $sets[] = 'name = ?';  $params[] = $name; }
    if ($phoneSet) { $sets[] = 'phone = ?'; $params[] = $phone; }
    $params[] = $customerId;

    $db->query("UPDATE customers SET " . implode(', ', $sets) . " WHERE id = ?", $params);

    $fresh = $db->query("SELECT * FROM customers WHERE id = ? LIMIT 1", [$customerId]);
    if (empty($fresh)) {
        return ['ok' => false, 'error' => 'Customer not found'];
    }
    return ['ok' => true, 'customer' => customer_public_view($fresh[0])];
}

/**
 * Change the customer's password. Requires the current password. On
 * success, deletes ALL existing sessions and issues a fresh one for the
 * current request.
 *
 * @return array{ok:bool, error?:string, customer?:array, token?:string, expires_at?:string}
 */
function customer_change_password(database_manager $db, int $customerId, string $currentPassword, string $newPassword, ?string $userAgent): array
{
    $row = $db->query("SELECT * FROM customers WHERE id = ? LIMIT 1", [$customerId]);
    if (empty($row)) {
        return ['ok' => false, 'error' => 'Customer not found'];
    }
    $customer = $row[0];

    if (!password_verify($currentPassword, $customer['password_hash'])) {
        return ['ok' => false, 'error' => 'Current password is incorrect'];
    }
    if (strlen($newPassword) < CUSTOMER_AUTH_MIN_PASSWORD_LEN) {
        return ['ok' => false, 'error' => 'Password must be at least ' . CUSTOMER_AUTH_MIN_PASSWORD_LEN . ' characters'];
    }

    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    // Kill every existing session for this customer, THEN write the new
    // hash, THEN issue a fresh session for this request. Any in-flight
    // stolen token is dead before the next request.
    $db->query("DELETE FROM customer_sessions WHERE customer_id = ?", [$customerId]);
    $db->query("UPDATE customers SET password_hash = ? WHERE id = ?", [$newHash, $customerId]);
    $session = customer_create_session($db, $customerId, $userAgent);

    $fresh = $db->query("SELECT * FROM customers WHERE id = ? LIMIT 1", [$customerId]);
    return [
        'ok' => true,
        'customer' => customer_public_view($fresh[0]),
        'token' => $session['token'],
        'expires_at' => $session['expires_at'],
    ];
}

// ===== Addresses =====

/**
 * List the customer's addresses, default first, then by id ascending.
 *
 * @return array{ok:bool, addresses:array<int,array>}
 */
function customer_addresses_list(database_manager $db, int $customerId): array
{
    $rows = $db->query(
        "SELECT * FROM customer_addresses WHERE customer_id = ?
          ORDER BY is_default DESC, id ASC",
        [$customerId]
    );
    $out = [];
    foreach ($rows as $r) $out[] = customer_address_public_view($r);
    return ['ok' => true, 'addresses' => $out];
}

/**
 * Create a new address. First address for the customer becomes the
 * default automatically. is_default=true on subsequent addresses flips
 * the previous default to 0.
 *
 * @return array{ok:bool, error?:string, address?:array}
 */
function customer_address_create(database_manager $db, int $customerId, array $fields): array
{
    $norm = customer_address_normalize($fields, true);
    if (!$norm['ok']) {
        return ['ok' => false, 'error' => $norm['error']];
    }
    $data = $norm['data'];

    // Decide if this address should be default
    $countRow = $db->query("SELECT COUNT(*) AS c FROM customer_addresses WHERE customer_id = ?", [$customerId]);
    $existingCount = (int)($countRow[0]['c'] ?? 0);
    $shouldBeDefault = ($existingCount === 0) || !empty($fields['is_default']);

    $db->query("BEGIN TRANSACTION");
    try {
        $db->query(
            "INSERT INTO customer_addresses
                (customer_id, label, recipient_name, line1, line2, city, region, postal_code, country, phone, is_default)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $customerId,
                $data['label'],
                $data['recipient_name'],
                $data['line1'],
                $data['line2'],
                $data['city'],
                $data['region'],
                $data['postal_code'],
                $data['country'],
                $data['phone'],
                $shouldBeDefault ? 1 : 0,
            ]
        );

        // Get new id by re-SELECT (database_manager has no lastInsertId).
        $newRow = $db->query(
            "SELECT id FROM customer_addresses WHERE customer_id = ? ORDER BY id DESC LIMIT 1",
            [$customerId]
        );
        $newId = !empty($newRow) ? (int)$newRow[0]['id'] : 0;

        if ($shouldBeDefault && $newId > 0) {
            customer_address_clear_default($db, $customerId, $newId);
        }

        $db->query("COMMIT");
    } catch (Throwable $e) {
        $db->query("ROLLBACK");
        return ['ok' => false, 'error' => 'Failed to create address'];
    }

    $full = $db->query("SELECT * FROM customer_addresses WHERE id = ? LIMIT 1", [$newId]);
    if (empty($full)) {
        return ['ok' => false, 'error' => 'Failed to create address'];
    }
    return ['ok' => true, 'address' => customer_address_public_view($full[0])];
}

/**
 * Update fields on an existing address owned by the customer. Setting
 * is_default=true flips the previous default to 0. 404 returned for
 * address ids not owned by this customer.
 *
 * @return array{ok:bool, error?:string, address?:array}
 */
function customer_address_update(database_manager $db, int $customerId, int $addressId, array $fields): array
{
    // Ownership check (also disambiguates "doesn't exist" vs "not yours")
    $existing = $db->query(
        "SELECT * FROM customer_addresses WHERE id = ? AND customer_id = ? LIMIT 1",
        [$addressId, $customerId]
    );
    if (empty($existing)) {
        return ['ok' => false, 'error' => 'Address not found'];
    }

    $norm = customer_address_normalize($fields, false);
    if (!$norm['ok']) {
        return ['ok' => false, 'error' => $norm['error']];
    }
    $data = $norm['data'];

    $sets = [];
    $params = [];
    // Each field in $data is included only if the input array specified it.
    // The normalize function trims provided fields; we only UPDATE columns
    // the caller actually mentioned.
    $columns = ['label', 'recipient_name', 'line1', 'line2', 'city', 'region', 'postal_code', 'country', 'phone'];
    foreach ($columns as $col) {
        if (array_key_exists($col, $fields)) {
            $sets[] = "$col = ?";
            $params[] = $data[$col];
        }
    }

    $flipDefault = !empty($fields['is_default']) && (int)$existing[0]['is_default'] === 0;
    if ($flipDefault) {
        $sets[] = "is_default = 1";
    }

    $db->query("BEGIN TRANSACTION");
    try {
        if (!empty($sets)) {
            $params[] = $addressId;
            $db->query("UPDATE customer_addresses SET " . implode(', ', $sets) . " WHERE id = ?", $params);
        }
        if ($flipDefault) {
            customer_address_clear_default($db, $customerId, $addressId);
        }
        $db->query("COMMIT");
    } catch (Throwable $e) {
        $db->query("ROLLBACK");
        return ['ok' => false, 'error' => 'Failed to update address'];
    }

    $full = $db->query("SELECT * FROM customer_addresses WHERE id = ? LIMIT 1", [$addressId]);
    if (empty($full)) {
        return ['ok' => false, 'error' => 'Address not found'];
    }
    return ['ok' => true, 'address' => customer_address_public_view($full[0])];
}

/**
 * Delete an address. If it was the default and other rows exist, the
 * oldest remaining row is auto-promoted to default. 404 returned for
 * address ids not owned by this customer.
 *
 * @return array{ok:bool, error?:string}
 */
function customer_address_delete(database_manager $db, int $customerId, int $addressId): array
{
    $existing = $db->query(
        "SELECT id, is_default FROM customer_addresses WHERE id = ? AND customer_id = ? LIMIT 1",
        [$addressId, $customerId]
    );
    if (empty($existing)) {
        return ['ok' => false, 'error' => 'Address not found'];
    }
    $wasDefault = (int)$existing[0]['is_default'] === 1;

    $db->query("BEGIN TRANSACTION");
    try {
        $db->query("DELETE FROM customer_addresses WHERE id = ?", [$addressId]);
        if ($wasDefault) {
            customer_address_promote_oldest($db, $customerId);
        }
        $db->query("COMMIT");
    } catch (Throwable $e) {
        $db->query("ROLLBACK");
        return ['ok' => false, 'error' => 'Failed to delete address'];
    }

    return ['ok' => true];
}

/**
 * Mark the given address as the customer's default, clearing any
 * previous default. Idempotent if already default.
 *
 * @return array{ok:bool, error?:string, address?:array}
 */
function customer_address_set_default(database_manager $db, int $customerId, int $addressId): array
{
    $existing = $db->query(
        "SELECT id, is_default FROM customer_addresses WHERE id = ? AND customer_id = ? LIMIT 1",
        [$addressId, $customerId]
    );
    if (empty($existing)) {
        return ['ok' => false, 'error' => 'Address not found'];
    }

    if ((int)$existing[0]['is_default'] === 1) {
        // Already default — idempotent no-op
        $full = $db->query("SELECT * FROM customer_addresses WHERE id = ? LIMIT 1", [$addressId]);
        return ['ok' => true, 'address' => customer_address_public_view($full[0])];
    }

    $db->query("BEGIN TRANSACTION");
    try {
        $db->query("UPDATE customer_addresses SET is_default = 1 WHERE id = ?", [$addressId]);
        customer_address_clear_default($db, $customerId, $addressId);
        $db->query("COMMIT");
    } catch (Throwable $e) {
        $db->query("ROLLBACK");
        return ['ok' => false, 'error' => 'Failed to set default'];
    }

    $full = $db->query("SELECT * FROM customer_addresses WHERE id = ? LIMIT 1", [$addressId]);
    return ['ok' => true, 'address' => customer_address_public_view($full[0])];
}

// ===== Internal helpers =====

/**
 * Validate and trim address input. On create, required fields must be
 * non-empty. On update, only the provided fields are validated.
 *
 * @return array{ok:bool, error?:string, data?:array}
 */
function customer_address_normalize(array $fields, bool $isCreate): array
{
    $required = ['recipient_name', 'line1', 'city', 'country'];
    $optional = ['label', 'line2', 'region', 'postal_code', 'phone'];

    if ($isCreate) {
        foreach ($required as $r) {
            $val = isset($fields[$r]) ? trim((string)$fields[$r]) : '';
            if ($val === '') {
                return ['ok' => false, 'error' => "$r is required"];
            }
        }
    } else {
        // On update, if a required field is PRESENT, it must not be empty.
        foreach ($required as $r) {
            if (array_key_exists($r, $fields)) {
                $val = trim((string)$fields[$r]);
                if ($val === '') {
                    return ['ok' => false, 'error' => "$r must not be empty"];
                }
            }
        }
    }

    $data = [];
    foreach (array_merge($required, $optional) as $col) {
        if (array_key_exists($col, $fields)) {
            $val = $fields[$col];
            $data[$col] = ($val === null) ? null : trim((string)$val);
            if ($data[$col] === '') $data[$col] = null;
        } else {
            $data[$col] = null;
        }
    }
    return ['ok' => true, 'data' => $data];
}

/**
 * Set is_default = 0 on all addresses for this customer EXCEPT the one
 * passed in. Used by create/update/set_default to flip the default
 * cleanly inside a transaction.
 */
function customer_address_clear_default(database_manager $db, int $customerId, int $exceptAddressId): void
{
    $db->query(
        "UPDATE customer_addresses SET is_default = 0
          WHERE customer_id = ? AND id != ?",
        [$customerId, $exceptAddressId]
    );
}

/**
 * Pick the lowest-id remaining address for the customer and set it as
 * default. No-op if no rows remain. Called after deleting the current
 * default.
 */
function customer_address_promote_oldest(database_manager $db, int $customerId): void
{
    $row = $db->query(
        "SELECT id FROM customer_addresses WHERE customer_id = ? ORDER BY id ASC LIMIT 1",
        [$customerId]
    );
    if (!empty($row)) {
        $db->query("UPDATE customer_addresses SET is_default = 1 WHERE id = ?", [(int)$row[0]['id']]);
    }
}

/**
 * Strip customer_id and cast is_default to bool before returning to
 * callers. Mirrors customer_public_view for customer rows.
 */
function customer_address_public_view(array $row): array
{
    return [
        'id' => (int)($row['id'] ?? 0),
        'label' => $row['label'] ?? null,
        'recipient_name' => $row['recipient_name'] ?? '',
        'line1' => $row['line1'] ?? '',
        'line2' => $row['line2'] ?? null,
        'city' => $row['city'] ?? '',
        'region' => $row['region'] ?? null,
        'postal_code' => $row['postal_code'] ?? null,
        'country' => $row['country'] ?? '',
        'phone' => $row['phone'] ?? null,
        'is_default' => (bool)($row['is_default'] ?? 0),
        'created_at' => $row['created_at'] ?? null,
    ];
}
