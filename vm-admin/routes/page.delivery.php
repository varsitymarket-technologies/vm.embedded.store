<?php
$db = initiate_web_database();

// Create table if not exists
$db->query("CREATE TABLE IF NOT EXISTS delivery (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    province VARCHAR(255),
    type VARCHAR(50),
    price DECIMAL(10,2),
    status VARCHAR(20) DEFAULT 'active'
)");

$db->query("CREATE TABLE IF NOT EXISTS delivery_location (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    enabled INTEGER DEFAULT 0,
    label VARCHAR(255),
    country_code VARCHAR(8),
    state_code VARCHAR(16),
    city_name VARCHAR(255),
    latitude DECIMAL(10,6),
    longitude DECIMAL(10,6),
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
)");

$locationRow = $db->query("SELECT * FROM delivery_location ORDER BY id DESC LIMIT 1");
$location = $locationRow[0] ?? [];

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_delivery') {
        $province = trim($_POST['province'] ?? '');
        $countryCode = trim($_POST['country_code'] ?? '');
        $stateCode = trim($_POST['state_code'] ?? '');
        $cityName = trim($_POST['city_name'] ?? '');
        if ($province === '') {
            $parts = array_filter([$cityName, $stateCode, $countryCode]);
            $province = !empty($parts) ? implode(' / ', $parts) : '';
        }
        $type = $_POST['type'] ?? 'flat';
        $price = $_POST['price'] ?? 0;
        $status = $_POST['status'] ?? 'active';

        $db->query("INSERT INTO delivery (province, type, price, status) VALUES (?, ?, ?, ?)", [$province, $type, $price, $status]);
        echo "<script>window.location.href = window.location.href;</script>";
        exit;

    } elseif ($action === 'update_delivery') {
        $id = $_POST['id'] ?? 0;
        $province = trim($_POST['province'] ?? '');
        $countryCode = trim($_POST['country_code'] ?? '');
        $stateCode = trim($_POST['state_code'] ?? '');
        $cityName = trim($_POST['city_name'] ?? '');
        if ($province === '') {
            $parts = array_filter([$cityName, $stateCode, $countryCode]);
            $province = !empty($parts) ? implode(' / ', $parts) : '';
        }
        $type = $_POST['type'] ?? 'flat';
        $price = $_POST['price'] ?? 0;
        $status = $_POST['status'] ?? 'active';

        $db->query("UPDATE delivery SET province = ?, type = ?, price = ?, status = ? WHERE id = ?", [$province, $type, $price, $status, $id]);
        echo "<script>window.location.href = window.location.href;</script>";
        exit;

    } elseif ($action === 'delete_delivery') {
        $id = $_POST['id'] ?? 0;
        $db->query("DELETE FROM delivery WHERE id = ?", [$id]);
        echo "<script>window.location.href = window.location.href;</script>";
        exit;
    } elseif ($action === 'save_store_location') {
        $enabled = isset($_POST['location_enabled']) ? 1 : 0;
        $label = trim($_POST['location_label'] ?? '');
        $countryCode = strtoupper(trim($_POST['country_code'] ?? ''));
        $stateCode = strtoupper(trim($_POST['state_code'] ?? ''));
        $cityName = trim($_POST['city_name'] ?? '');
        $latitude = trim($_POST['latitude'] ?? '');
        $longitude = trim($_POST['longitude'] ?? '');

        $existing = $db->query("SELECT id FROM delivery_location ORDER BY id DESC LIMIT 1");
        if (!empty($existing)) {
            $db->query("UPDATE delivery_location SET enabled = ?, label = ?, country_code = ?, state_code = ?, city_name = ?, latitude = ?, longitude = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?", [
                $enabled,
                $label,
                $countryCode,
                $stateCode,
                $cityName,
                $latitude !== '' ? (float) $latitude : null,
                $longitude !== '' ? (float) $longitude : null,
                $existing[0]['id']
            ]);
        } else {
            $db->query("INSERT INTO delivery_location (enabled, label, country_code, state_code, city_name, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?)", [
                $enabled,
                $label,
                $countryCode,
                $stateCode,
                $cityName,
                $latitude !== '' ? (float) $latitude : null,
                $longitude !== '' ? (float) $longitude : null
            ]);
        }

        echo "<script>window.location.href = window.location.href;</script>";
        exit;
    }
}

$deliveries = $db->query("SELECT * FROM delivery ORDER BY id DESC");
$location = $db->query("SELECT * FROM delivery_location ORDER BY id DESC LIMIT 1")[0] ?? $location;
$totalZones = is_array($deliveries) ? count($deliveries) : 0;
$activeZones = 0;
$avgPrice = 0;
$priceSum = 0;
if ($deliveries) {
    foreach ($deliveries as $d) {
        if (($d['status'] ?? 'active') === 'active')
            $activeZones++;
        $priceSum += (float) ($d['price'] ?? 0);
    }
    $avgPrice = $totalZones > 0 ? $priceSum / $totalZones : 0;
}
?>
<!-- Main Content -->
<div class="flex flex-1 flex-col overflow-hidden">
    <?php @include_once "header.php"; ?>

    <main class="flex-1 overflow-y-auto overflow-x-hidden bg-[#1b1b1c]  p-6">

        <!-- Page Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
            <div>
                <h2 class="text-2xl font-bold text-white">Delivery Zones</h2>
                <p class="text-zinc-400 text-sm mt-1">Manage shipping rates and delivery regions</p>
            </div>
            <button onclick="openModal('add')"
                class="bg-violet-600 hover:bg-violet-500 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center gap-2">
                <i class="bi bi-plus-lg"></i> Add Zone
            </button>
        </div>

        <!-- Stat Cards -->
        <div class="grid grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
            <div class="bg-[#252526] border border-zinc-800 rounded-xl p-4">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-zinc-400 text-xs font-medium">Total Zones</span>
                    <span class="w-8 h-8 rounded-lg bg-violet-500/10 flex items-center justify-center">
                        <i class="bi bi-geo-alt text-violet-400"></i>
                    </span>
                </div>
                <p class="text-2xl font-bold text-white"><?php echo $totalZones; ?></p>
            </div>
            <div class="bg-[#252526] border border-zinc-800 rounded-xl p-4">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-zinc-400 text-xs font-medium">Active</span>
                    <span class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center">
                        <i class="bi bi-check-circle text-emerald-400"></i>
                    </span>
                </div>
                <p class="text-2xl font-bold text-white"><?php echo $activeZones; ?></p>
            </div>
            <div class="bg-[#252526] border border-zinc-800 rounded-xl p-4">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-zinc-400 text-xs font-medium">Avg Rate</span>
                    <span class="w-8 h-8 rounded-lg bg-sky-500/10 flex items-center justify-center">
                        <i class="bi bi-currency-exchange text-sky-400"></i>
                    </span>
                </div>
                <p class="text-2xl font-bold text-white">R<?php echo number_format($avgPrice, 2); ?></p>
            </div>
        </div>

        <!-- Global Delivery Visualization -->
        <div class="w-full h-[500px] mb-8 flex justify-center items-center">
            <div id="deliveryMap" class="w-full h-full"></div>
        </div>

        <!-- Store Location -->
        <div class="bg-[#252526] border border-zinc-800 rounded-xl overflow-hidden mb-6">
            <div class="px-5 py-4 border-b border-zinc-800 flex items-center justify-between">
                <div>
                    <h3 class="text-white font-semibold text-sm">
                        <i class="bi bi-geo-alt mr-2 text-zinc-500"></i>Store Location
                    </h3>
                    <p class="text-zinc-500 text-xs mt-1">Optional: pin the store location to help calculate delivery costs from the map.</p>
                </div>
                <span class="text-xs px-2 py-1 rounded-full <?php echo !empty($location['enabled']) ? 'bg-emerald-500/10 text-emerald-400' : 'bg-zinc-800 text-zinc-400'; ?>">
                    <?php echo !empty($location['enabled']) ? 'Enabled' : 'Not set'; ?>
                </span>
            </div>
            <div class="grid lg:grid-cols-2 gap-0">
                <div class="p-5 space-y-4 lg:border-r border-zinc-800">
                    <form method="POST" id="storeLocationForm" class="space-y-4">
                        <input type="hidden" name="action" value="save_store_location">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <h4 class="text-white font-medium">Pin the store</h4>
                                <p class="text-zinc-500 text-xs">Choose the location, then save it here.</p>
                            </div>
                            <label class="inline-flex items-center gap-2 text-sm text-zinc-300">
                                <input type="checkbox" name="location_enabled" class="rounded border-zinc-700 bg-zinc-800 text-violet-500 focus:ring-violet-500" <?php echo !empty($location['enabled']) ? 'checked' : ''; ?>>
                                Active
                            </label>
                        </div>
                        <div>
                            <label class="block text-zinc-400 text-xs font-medium mb-1.5">Store Label</label>
                            <input type="text" name="location_label" id="locationLabel" value="<?php echo htmlspecialchars($location['label'] ?? ''); ?>"
                                class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition-colors"
                                placeholder="e.g. Main store, Warehouse, HQ">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-zinc-400 text-xs font-medium mb-1.5">Country</label>
                                <input type="text" name="country_code" id="countryCode" value="<?php echo htmlspecialchars($location['country_code'] ?? ''); ?>"
                                    class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition-colors"
                                    placeholder="ZA">
                            </div>
                            <div>
                                <label class="block text-zinc-400 text-xs font-medium mb-1.5">State</label>
                                <input type="text" name="state_code" id="stateCode" value="<?php echo htmlspecialchars($location['state_code'] ?? ''); ?>"
                                    class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition-colors"
                                    placeholder="GP">
                            </div>
                        </div>
                        <div>
                            <label class="block text-zinc-400 text-xs font-medium mb-1.5">City</label>
                            <input type="text" name="city_name" id="cityName" value="<?php echo htmlspecialchars($location['city_name'] ?? ''); ?>"
                                class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition-colors"
                                placeholder="Johannesburg">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-zinc-400 text-xs font-medium mb-1.5">Latitude</label>
                                <input type="text" name="latitude" id="latitude" value="<?php echo htmlspecialchars((string)($location['latitude'] ?? '')); ?>"
                                    class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition-colors"
                                    placeholder="-26.2041">
                            </div>
                            <div>
                                <label class="block text-zinc-400 text-xs font-medium mb-1.5">Longitude</label>
                                <input type="text" name="longitude" id="longitude" value="<?php echo htmlspecialchars((string)($location['longitude'] ?? '')); ?>"
                                    class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition-colors"
                                    placeholder="28.0473">
                            </div>
                        </div>
                        <button type="submit" class="w-full bg-violet-600 hover:bg-violet-500 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                            Save Store Location
                        </button>
                    </form>
                </div>
                <div class="p-5">
                    <div class="bg-[#1f1f20] border border-zinc-800 rounded-xl p-4 h-full">
                        <p class="text-xs font-semibold uppercase tracking-widest text-zinc-500 mb-3">Global Explorer</p>
                        <div id="deliveryGeoSummary" class="space-y-2 max-h-[300px] overflow-auto pr-1 text-sm text-zinc-300">
                            <p class="text-zinc-500 text-xs">Loading geography data...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delivery Table -->
        <div class="bg-[#252526] border border-zinc-800 rounded-xl overflow-hidden">
            <div class="px-5 py-4 border-b border-zinc-800 flex items-center justify-between">
                <h3 class="text-white font-semibold text-sm"><i class="bi bi-truck mr-2 text-zinc-500"></i>Delivery
                    Rates</h3>
            </div>
            <?php if (empty($deliveries)): ?>
                <div class="flex flex-col items-center justify-center py-16 text-zinc-500">
                    <i class="bi bi-truck text-4xl mb-3"></i>
                    <p class="text-sm">No delivery zones configured</p>
                    <p class="text-xs text-zinc-600 mt-1">Add zones to enable shipping for your store</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-zinc-800 text-xs text-zinc-500 uppercase">
                                <th class="px-5 py-3 font-medium">Zone</th>
                                <th class="px-5 py-3 font-medium">Type</th>
                                <th class="px-5 py-3 font-medium">Price</th>
                                <th class="px-5 py-3 font-medium">Status</th>
                                <th class="px-5 py-3 font-medium text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-800/50">
                            <?php foreach ($deliveries as $del): ?>
                                <tr class="hover:bg-zinc-800/30 transition-colors group">
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-lg bg-zinc-800 flex items-center justify-center">
                                                <i class="bi bi-geo-alt text-zinc-400"></i>
                                            </div>
                                            <span
                                                class="text-white font-medium"><?php echo htmlspecialchars($del['province'] ?? ''); ?></span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <span
                                            class="text-xs font-medium px-2 py-0.5 rounded-full <?php echo ($del['type'] ?? 'flat') === 'flat' ? 'bg-sky-500/10 text-sky-400' : 'bg-violet-500/10 text-violet-400'; ?>">
                                            <?php echo ucfirst($del['type'] ?? 'flat'); ?> Rate
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-white font-medium">
                                        R<?php echo number_format((float) ($del['price'] ?? 0), 2); ?></td>
                                    <td class="px-5 py-4">
                                        <span
                                            class="inline-flex items-center gap-1.5 text-xs font-medium px-2 py-0.5 rounded-full <?php echo ($del['status'] ?? 'active') === 'active' ? 'bg-emerald-500/10 text-emerald-400' : 'bg-red-500/10 text-red-400'; ?>">
                                            <span
                                                class="w-1.5 h-1.5 rounded-full <?php echo ($del['status'] ?? 'active') === 'active' ? 'bg-emerald-400' : 'bg-red-400'; ?>"></span>
                                            <?php echo ucfirst($del['status'] ?? 'active'); ?>
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <div
                                            class="flex justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button onclick='openModal("edit", <?php echo json_encode($del); ?>)'
                                                class="p-1.5 rounded-md hover:bg-zinc-700 text-zinc-400 transition-colors"
                                                title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form method="POST" class="inline"
                                                onsubmit="return confirm('Delete this delivery zone?');">
                                                <input type="hidden" name="action" value="delete_delivery">
                                                <input type="hidden" name="id" value="<?php echo $del['id']; ?>">
                                                <button type="submit"
                                                    class="p-1.5 rounded-md hover:bg-red-900/30 text-red-400 transition-colors"
                                                    title="Delete">
                                                    <i class="bi bi-trash3"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

    </main>
</div>

<!-- Modal -->
<div id="deliveryModal" class="fixed inset-0 z-50 hidden">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-[#252526] /60 backdrop-blur-sm" onclick="closeModal()"></div>
        <div class="relative bg-[#252526] border border-zinc-800 rounded-xl w-full max-w-md shadow-2xl">
            <form method="POST" id="deliveryForm">
                <input type="hidden" name="action" id="formAction" value="add_delivery">
                <input type="hidden" name="id" id="deliveryId">
                <div class="flex items-center justify-between px-5 py-4 border-b border-zinc-800">
                    <h3 class="text-white font-semibold" id="modalTitle">Add Delivery Zone</h3>
                    <button type="button" onclick="closeModal()"
                        class="text-zinc-500 hover:text-white transition-colors"><i class="bi bi-x-lg"></i></button>
                </div>
                <div class="p-5 space-y-4">
                        <div>
                            <label class="block text-zinc-400 text-xs font-medium mb-1.5">Zone Name</label>
                        <input type="text" name="province" id="deliveryProvince" required list="deliveryZoneSuggestions"
                            class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition-colors"
                            placeholder="Start typing a country, state, or city">
                        <datalist id="deliveryZoneSuggestions"></datalist>
                        </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-zinc-400 text-xs font-medium mb-1.5">Country</label>
                            <input type="text" name="country_code" id="deliveryCountry" list="deliveryCountryOptions"
                                class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition-colors"
                                placeholder="South Africa">
                        </div>
                        <div>
                            <label class="block text-zinc-400 text-xs font-medium mb-1.5">State</label>
                            <input type="text" name="state_code" id="deliveryState" list="deliveryStateOptions"
                                class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition-colors"
                                placeholder="Gauteng">
                        </div>
                        <div>
                            <label class="block text-zinc-400 text-xs font-medium mb-1.5">City</label>
                            <input type="text" name="city_name" id="deliveryCity" list="deliveryCityOptions"
                                class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition-colors"
                                placeholder="Johannesburg">
                        </div>
                    </div>
                    <datalist id="deliveryCountryOptions"></datalist>
                    <datalist id="deliveryStateOptions"></datalist>
                    <datalist id="deliveryCityOptions"></datalist>
                    <div>
                        <label class="block text-zinc-400 text-xs font-medium mb-1.5">Rate Type</label>
                        <select name="type" id="deliveryType"
                            class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition-colors">
                            <option value="flat">Flat Rate</option>
                            <option value="regional">Regional Rate</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-zinc-400 text-xs font-medium mb-1.5">Price (R)</label>
                        <input type="number" step="0.01" name="price" id="deliveryPrice" required min="0"
                            class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition-colors"
                            placeholder="100.00">
                    </div>
                    <div>
                        <label class="block text-zinc-400 text-xs font-medium mb-1.5">Status</label>
                        <select name="status" id="deliveryStatus"
                            class="w-full bg-zinc-800 border border-zinc-700 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:border-violet-500 focus:ring-1 focus:ring-violet-500 transition-colors">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="px-5 py-4 border-t border-zinc-800 flex justify-end gap-2">
                    <button type="button" onclick="closeModal()"
                        class="px-4 py-2 text-sm font-medium text-zinc-400 hover:text-white transition-colors">Cancel</button>
                    <button type="submit"
                        class="bg-violet-600 hover:bg-violet-500 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">Save
                        Zone</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const deliveryZones = <?php echo json_encode($deliveries ?? []); ?>;
    let deliveryMap = null;
    let deliveryMarker = null;
    let deliveryGeoData = null;

    function openModal(mode, del = null) {
        const modal = document.getElementById('deliveryModal');
        const form = document.getElementById('deliveryForm');
        const title = document.getElementById('modalTitle');
        const action = document.getElementById('formAction');
        modal.classList.remove('hidden');
        if (mode === 'edit' && del) {
            title.textContent = 'Edit Delivery Zone';
            action.value = 'update_delivery';
            document.getElementById('deliveryId').value = del.id;
            document.getElementById('deliveryProvince').value = del.province;
            document.getElementById('deliveryType').value = del.type || 'flat';
            document.getElementById('deliveryPrice').value = del.price;
            document.getElementById('deliveryStatus').value = del.status || 'active';
            const parts = String(del.province || '').split(' / ').map(s => s.trim()).filter(Boolean);
            document.getElementById('deliveryCity').value = parts[0] || '';
            document.getElementById('deliveryState').value = parts[1] || '';
            document.getElementById('deliveryCountry').value = parts[2] || '';
        } else {
            title.textContent = 'Add Delivery Zone';
            action.value = 'add_delivery';
            form.reset();
            document.getElementById('deliveryId').value = '';
            document.getElementById('deliveryCountry').value = '';
            document.getElementById('deliveryState').value = '';
            document.getElementById('deliveryCity').value = '';
        }
        syncDeliveryProvince();
    }

    function closeModal() {
        document.getElementById('deliveryModal').classList.add('hidden');
    }

    function syncDeliveryProvince() {
        const province = document.getElementById('deliveryProvince');
        const country = document.getElementById('deliveryCountry');
        const state = document.getElementById('deliveryState');
        const city = document.getElementById('deliveryCity');
        if (!province || !country || !state || !city) return;
        if (province.value.trim() !== '') return;
        const parts = [city.value.trim(), state.value.trim(), country.value.trim()].filter(Boolean);
        province.value = parts.join(' / ');
    }

    async function loadGlobe() {
        if (window.Globe) return window.Globe;
        await new Promise((resolve, reject) => {
            const script3 = document.createElement('script');
            script3.src = 'https://unpkg.com/three';
            document.head.appendChild(script3);

            script3.onload = () => {
                const scriptGlobe = document.createElement('script');
                scriptGlobe.src = 'https://unpkg.com/globe.gl';
                scriptGlobe.onload = resolve;
                scriptGlobe.onerror = reject;
                document.head.appendChild(scriptGlobe);
            };
        });
        return window.Globe;
    }

    async function initDeliveryMap() {
        const mapEl = document.getElementById('deliveryMap');
        if (!mapEl || !window.Globe) return;

        const lat = parseFloat(document.getElementById('latitude').value || '-26.2041');
        const lng = parseFloat(document.getElementById('longitude').value || '28.0473');

        deliveryMap = Globe()(mapEl)
            .globeImageUrl('https://unpkg.com/three-globe/example/img/earth-night.jpg')
            .backgroundColor('rgba(0,0,0,0)')
            .width(mapEl.clientWidth)
            .height(mapEl.clientHeight);
            
        updateGlobeMarkers(lng, lat);

        deliveryMap.pointOfView({ lat: isFinite(lat) ? lat : -26.2, lng: isFinite(lng) ? lng : 28.0, altitude: 1.5 }, 1000);

        window.addEventListener('resize', () => {
            if (mapEl && deliveryMap) {
                deliveryMap.width(mapEl.clientWidth).height(mapEl.clientHeight);
            }
        });

        deliveryMap.onGlobeClick(({ lat, lng }) => {
            setStoreCoordinates(lng, lat);
        });
    }

    const regionNames = new Intl.DisplayNames(['en'], { type: 'region' });
    const countryNameCache = {};
    function getFullCountryName(code) {
        if (!code) return '';
        if (countryNameCache[code]) return countryNameCache[code];
        let name = code;
        try { name = regionNames.of(code); } catch(e) {}
        countryNameCache[code] = name;
        return name;
    }

    function updateGlobeMarkers(lng, lat) {
        if (!deliveryMap) return;
        
        const markers = [];
        const arcs = [];
        
        const hasStore = isFinite(lat) && isFinite(lng);
        if (hasStore) {
            markers.push({ 
                lat, lng, 
                type: 'store', 
                color: '#8b5cf6',
                size: 24,
                icon: 'bi-geo-alt-fill'
            });
        }
        
        if (deliveryGeoData && Array.isArray(deliveryGeoData)) {
            deliveryZones.forEach(zone => {
                if (zone.status !== 'active') return;
                
                const parts = (zone.province || '').split(' / ').map(p => p.trim().toLowerCase());
                if (parts.length > 0) {
                    const match = deliveryGeoData.find(c => {
                        const cName = (c.name || '').toLowerCase();
                        const cAdmin1 = (c.admin1 || '').toLowerCase();
                        const cCountry = getFullCountryName(c.country).toLowerCase();
                        
                        if (parts.length === 3) {
                            return cName === parts[0] && cAdmin1 === parts[1];
                        } else if (parts.length === 2) {
                            return cName === parts[0] || cAdmin1 === parts[0];
                        }
                        return cName === parts[0] || cCountry === parts[0];
                    });
                    
                    if (match) {
                        const matchLat = parseFloat(match.lat);
                        const matchLng = parseFloat(match.lon);
                        
                        markers.push({
                            lat: matchLat,
                            lng: matchLng,
                            type: 'zone',
                            color: '#10b981',
                            size: 18,
                            icon: 'bi-box-seam'
                        });
                        
                        if (hasStore) {
                            arcs.push({
                                startLat: lat,
                                startLng: lng,
                                endLat: matchLat,
                                endLng: matchLng,
                                color: ['rgba(139, 92, 246, 0.5)', 'rgba(16, 185, 129, 0.8)']
                            });
                        }
                    }
                }
            });
        }

        deliveryMap.htmlElementsData(markers)
            .htmlElement(d => {
                const el = document.createElement('div');
                el.innerHTML = `<i class="bi ${d.icon} drop-shadow-lg" style="color: ${d.color}; font-size: ${d.size}px;"></i>`;
                el.style.width = `${d.size}px`;
                el.style.height = `${d.size}px`;
                el.style.marginTop = `-${d.size/2}px`;
                el.style.marginLeft = `-${d.size/2}px`;
                if (d.type === 'store') el.style.cursor = 'pointer';
                return el;
            });
            
        deliveryMap.arcsData(arcs)
            .arcColor('color')
            .arcDashLength(0.4)
            .arcDashGap(0.2)
            .arcDashAnimateTime(1500)
            .arcStroke(1.5);
    }

    function setStoreCoordinates(lng, lat) {
        document.getElementById('longitude').value = Number(lng).toFixed(6);
        document.getElementById('latitude').value = Number(lat).toFixed(6);
        
        if (deliveryMap) {
            updateGlobeMarkers(lng, lat);
            deliveryMap.pointOfView({ lat, lng, altitude: 1.5 }, 1000);
        }
    }

    function renderGeoSummary(data) {
        const summary = document.getElementById('deliveryGeoSummary');
        if (!summary || !data || !Array.isArray(data)) return;

        const grouped = {};
        for (let i = 0; i < data.length; i++) {
            const item = data[i];
            const fullCountry = getFullCountryName(item.country) || item.country;
            if (!grouped[item.country]) {
                grouped[item.country] = { name: fullCountry, iso2: item.country, states: {} };
            }
            if (!grouped[item.country].states[item.admin1]) {
                grouped[item.country].states[item.admin1] = { name: item.admin1, iso2: item.admin1, cities: [] };
            }
            if (grouped[item.country].states[item.admin1].cities.length < 50) {
                grouped[item.country].states[item.admin1].cities.push({
                    name: item.name,
                    latitude: item.lat,
                    longitude: item.lon
                });
            }
        }

        const countryList = Object.values(grouped).slice(0, 6);

        const countries = countryList.map(country => {
            const stateValues = Object.values(country.states);
            const stateCount = stateValues.length;
            const cityCount = stateValues.reduce((sum, state) => sum + state.cities.length, 0);
            
            const countryData = {
                name: country.name,
                iso2: country.iso2,
                states: stateValues
            };

            return `
                <button type="button" class="w-full text-left rounded-lg border border-zinc-800 bg-zinc-900/60 px-3 py-2 hover:border-violet-500/40 transition"
                    data-country='${JSON.stringify(countryData).replace(/'/g, "&apos;")}'>
                    <div class="flex items-center justify-between gap-3">
                        <span class="text-white font-medium">${country.name || 'Country'}</span>
                        <span class="text-xs text-zinc-500">${stateCount} states &middot; ${cityCount} cities</span>
                    </div>
                </button>
            `;
        }).join('');

        summary.innerHTML = countries || '<p class="text-zinc-500 text-xs">No cached geography data found yet.</p>';
    }

    async function loadDeliveryGeography() {
        try {
            const summary = document.getElementById('deliveryGeoSummary');
            if (summary) summary.innerHTML = '<p class="text-zinc-500 text-xs">Loading geography data (this may take a moment)...</p>';
            
            const response = await fetch('https://raw.githubusercontent.com/lmfmaier/cities-json/refs/heads/master/cities500.json');
            const data = await response.json();
            deliveryGeoData = data;
            
            renderGeoSummary(deliveryGeoData);
            hydrateDeliveryComboboxes(deliveryGeoData);
        } catch (error) {
            const summary = document.getElementById('deliveryGeoSummary');
            if (summary) summary.innerHTML = '<p class="text-red-400 text-xs">Failed to load geography data.</p>';
            console.error(error);
        }
    }

    let allCountries = [];
    let allStates = [];
    let allCities = [];
    let allZones = [];

    function setupDynamicDatalist(inputId, datalistId, sourceArray) {
        const input = document.getElementById(inputId);
        const datalist = document.getElementById(datalistId);
        if (!input || !datalist) return;
        
        datalist.innerHTML = sourceArray.slice(0, 100).map(v => `<option value="${String(v).replace(/"/g, '&quot;')}"></option>`).join('');
        
        input.addEventListener('input', (e) => {
            const val = e.target.value.toLowerCase();
            if (!val) {
                datalist.innerHTML = sourceArray.slice(0, 100).map(v => `<option value="${String(v).replace(/"/g, '&quot;')}"></option>`).join('');
                return;
            }
            const filtered = [];
            for (let i = 0; i < sourceArray.length; i++) {
                if (sourceArray[i].toLowerCase().includes(val)) {
                    filtered.push(sourceArray[i]);
                    if (filtered.length >= 100) break;
                }
            }
            datalist.innerHTML = filtered.map(v => `<option value="${String(v).replace(/"/g, '&quot;')}"></option>`).join('');
        });
    }

    function hydrateDeliveryComboboxes(data) {
        if (!data || !Array.isArray(data)) return;

        const countries = new Set();
        const states = new Set();
        const cities = new Set();
        const zones = new Set();

        data.forEach(item => {
            const fullCountry = getFullCountryName(item.country) || item.country;
            if (fullCountry) countries.add(fullCountry);
            if (item.admin1) {
                states.add(item.admin1);
                zones.add([item.admin1, fullCountry].filter(Boolean).join(' / '));
            }
            if (item.name) {
                cities.add(item.name);
                zones.add([item.name, item.admin1, fullCountry].filter(Boolean).join(' / '));
            }
        });

        allCountries = Array.from(countries);
        allStates = Array.from(states);
        allCities = Array.from(cities);
        allZones = Array.from(zones);

        setupDynamicDatalist('deliveryCountry', 'deliveryCountryOptions', allCountries);
        setupDynamicDatalist('deliveryState', 'deliveryStateOptions', allStates);
        setupDynamicDatalist('deliveryCity', 'deliveryCityOptions', allCities);
        setupDynamicDatalist('deliveryProvince', 'deliveryZoneSuggestions', allZones);

        ['deliveryCountry', 'deliveryState', 'deliveryCity', 'deliveryProvince'].forEach(id => {
            const el = document.getElementById(id);
            el?.addEventListener('input', syncDeliveryProvince);
            el?.addEventListener('change', syncDeliveryProvince);
        });
    }

    document.addEventListener('click', (event) => {
        const button = event.target.closest('[data-country]');
        if (!button) return;
        try {
            const country = JSON.parse(button.getAttribute('data-country'));
            const firstState = country.states && country.states[0];
            const firstCity = firstState && firstState.cities && firstState.cities[0];
            if (firstCity && firstCity.latitude && firstCity.longitude) {
                setStoreCoordinates(firstCity.longitude, firstCity.latitude);
                document.getElementById('countryCode').value = country.iso2 || country.country_code || '';
                document.getElementById('stateCode').value = firstState.iso2 || firstState.state_code || '';
                document.getElementById('cityName').value = firstCity.name || '';
            }
        } catch (e) {}
    });

    window.addEventListener('load', async () => {
        await loadDeliveryGeography();
        try {
            await loadGlobe();
            if (window.Globe) initDeliveryMap();
        } catch (e) {
            const mapEl = document.getElementById('deliveryMap');
            if (mapEl) {
                mapEl.innerHTML = '<div class="flex h-full items-center justify-center text-zinc-500 text-sm">Globe could not load in this browser.</div>';
            }
        }
    });
</script>
