# OTP Login System Implementation Guide

## Overview
Your project now has a complete **Email-based One-Time Password (OTP) authentication** system integrated into the customer login flow. Every login requires email verification using a 6-digit OTP code.

---

## System Architecture

### Login Flow (2-Step Process)

```
Step 1: Email + Password → Generate OTP → Send via Email
        ↓
        Response: {ok: true, pending_otp: true, customer: {...}}

Step 2: Enter OTP Code → Verify → Issue Session Token
        ↓
        Response: {ok: true, token: "...", customer: {...}}
```

---

## API Endpoints

### 1. POST `/store-access/{store-id}/?state=customer_login`

**Purpose**: Authenticate customer credentials and send OTP email

**Request Body**:
```json
{
  "email": "student@vossie.net",
  "password": "securePassword123"
}
```

**Success Response** (200):
```json
{
  "ok": true,
  "pending_otp": true,
  "customer": {
    "id": 42,
    "email": "student@vossie.net",
    "name": "John Doe",
    "phone": null,
    "email_verified": false,
    "created_at": "2026-08-16T10:30:00"
  },
  "message": "OTP code has been sent to your email. Please verify to complete login."
}
```

**Error Response** (401/429):
```json
{
  "ok": false,
  "error": "Invalid email or password"
}
```

---

### 2. POST `/store-access/{store-id}/?state=customer_verify_otp`

**Purpose**: Verify OTP code and issue session token

**Request Body**:
```json
{
  "email": "student@vossie.net",
  "otp": "837462"
}
```

**Success Response** (200):
```json
{
  "ok": true,
  "customer": {
    "id": 42,
    "email": "student@vossie.net",
    "name": "John Doe",
    "email_verified": true,
    "created_at": "2026-08-16T10:30:00"
  },
  "token": "a1b2c3d4e5f6...",
  "expires_at": "2026-09-15T10:35:00"
}
```

**Error Response** (401):
```json
{
  "ok": false,
  "error": "Invalid or expired OTP code"
}
```

---

## Client Implementation Example

### JavaScript/Frontend

```javascript
// Step 1: Request OTP
async function loginStep1(email, password) {
  const response = await fetch('/store-access/{store-id}/?state=customer_login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email, password })
  });
  
  const data = await response.json();
  
  if (data.ok && data.pending_otp) {
    console.log('OTP sent to email:', email);
    // Show OTP input form to user
    return data.customer;
  } else {
    console.error('Login failed:', data.error);
  }
}

// Step 2: Verify OTP
async function loginStep2(email, otp) {
  const response = await fetch('/store-access/{store-id}/?state=customer_verify_otp', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email, otp })
  });
  
  const data = await response.json();
  
  if (data.ok) {
    // Store token for future requests
    localStorage.setItem('customer_token', data.token);
    console.log('Login successful!', data.customer);
    return data.token;
  } else {
    console.error('OTP verification failed:', data.error);
  }
}

// Using authenticated endpoints with token
async function getCustomerProfile(token) {
  const response = await fetch('/store-access/{store-id}/?state=customer_me', {
    method: 'GET',
    headers: { 'X-Customer-Token': token }
  });
  return response.json();
}
```

---

## Configuration

### Email SMTP Setup

OTP emails are sent using the encrypted SMTP configuration stored at:
```
/sites/{domain}/email.config.enc
```

The configuration should include:
```json
{
  "host": "smtp.gmail.com",
  "port": 587,
  "user": "your-email@gmail.com",
  "pass": "your-app-password",
  "from_email": "noreply@yourdomain.com",
  "reply_to": "support@yourdomain.com",
  "template": "<html>...</html>"
}
```

**To configure SMTP**:
1. Go to your admin panel settings
2. Navigate to Email Configuration
3. Enter SMTP details (host, port, username, password)
4. (Optional) Customize email template with `{{name}}`, `{{otp}}` placeholders
5. Save configuration

---

## Security Features

1. **OTP Expiry**: 10 minutes (configurable via `CUSTOMER_AUTH_OTP_EXPIRY_MINUTES`)
2. **OTP Length**: 6 digits (configurable via `CUSTOMER_AUTH_OTP_LENGTH`)
3. **One-Time Use**: OTP is marked as "verified" after first successful use
4. **Constant-Time Verification**: Timing attack protection on credential checks
5. **Account Lockout**: 5 failed attempts → 15-minute lockout
6. **Email Verified Flag**: Updated to true after successful OTP verification

---

## Database Schema

### New Table: `customer_otp_codes`

| Column | Type | Description |
|--------|------|-------------|
| id | INTEGER PK | Auto-increment ID |
| customer_id | INTEGER FK | Reference to customers(id) |
| otp_code | TEXT | 6-digit OTP code |
| created_at | DATETIME | Creation timestamp |
| expires_at | DATETIME | Expiry time (10 min default) |
| verified | INTEGER | 0=pending, 1=used |

**Indexes**:
- `idx_otp_customer` on customer_id (fast lookups)
- `idx_otp_expires` on expires_at (cleanup queries)

---

## Environment Constants

Add to your PHP configuration as needed:

```php
define('CUSTOMER_AUTH_OTP_LENGTH', 6);           // OTP code length
define('CUSTOMER_AUTH_OTP_EXPIRY_MINUTES', 10);  // OTP validity period
define('CUSTOMER_AUTH_LOCKOUT_THRESHOLD', 5);    // Failed login attempts
define('CUSTOMER_AUTH_LOCKOUT_MINUTES', 15);     // Account lockout duration
```

---

## Modules & Files

### Core Modules Added/Modified

1. **`module/email.php`** (NEW)
   - Email configuration loading
   - SMTP-based email sending
   - OTP email template rendering

2. **`module/customer_auth.php`** (MODIFIED)
   - OTP generation functions
   - OTP storage and verification
   - Modified login flow with OTP step
   - Email verification flag management

3. **`services/database.install.php`** (MODIFIED)
   - New `customer_otp_codes` table creation

4. **`api/index.php`** (MODIFIED)
   - Updated `customer_login` → OTP request flow
   - New `customer_verify_otp` endpoint

5. **`skel/api.php`** (MODIFIED)
   - Same updates for skeleton API

---

## Testing the OTP System

### Step 1: Set Up Email Configuration
Ensure your admin panel has SMTP credentials configured.

### Step 2: Create Test Customer
```bash
# Use the registration endpoint or admin panel to create a test account
POST /store-access/{store-id}/?state=customer_register
{
  "email": "test@vossie.net",
  "password": "TestPassword123",
  "name": "Test User"
}
```

### Step 3: Login and Check Email
```bash
# Request OTP
POST /store-access/{store-id}/?state=customer_login
{
  "email": "test@vossie.net",
  "password": "TestPassword123"
}

# Check the email for OTP code (e.g., "837462")
```

### Step 4: Verify OTP
```bash
POST /store-access/{store-id}/?state=customer_verify_otp
{
  "email": "test@vossie.net",
  "otp": "837462"
}
```

### Step 5: Use Session Token
```bash
GET /store-access/{store-id}/?state=customer_me
Headers: X-Customer-Token: {token}
```

---

## Troubleshooting

### Issue: "Email not configured for this domain"
**Solution**: Go to admin panel and configure SMTP settings under Email Configuration.

### Issue: OTP code not received
**Solution**: 
1. Check SMTP configuration is correct
2. Verify sender email address is valid
3. Check spam/junk folder
4. Check server logs for email sending errors

### Issue: "Invalid or expired OTP code"
**Solution**:
1. OTP expires after 10 minutes — request a new one
2. OTP can only be used once — if already used, login again
3. Check that OTP was entered correctly (case-sensitive, spaces)

### Issue: Account locked after failed attempts
**Solution**:
1. Account auto-unlocks after 15 minutes
2. Or admin can manually unlock in customer management panel

---

## Future Enhancements

1. **OTP Resend**: Implement endpoint to resend OTP without re-authenticating
2. **SMS/Push Notifications**: Support alternative OTP delivery methods
3. **Backup Codes**: Generate recovery codes for account access
4. **Device Fingerprinting**: Skip OTP on trusted devices
5. **Rate Limiting**: IP-based rate limiting on OTP endpoints
6. **OTP Webhook**: Notify external systems on successful login

---

## API Migration Notes

If you have existing mobile apps or integrations, they will need to be updated to handle the 2-step login flow:

**Old Flow** (single step):
```
POST /customer_login → {token, customer}
```

**New Flow** (two steps):
```
POST /customer_login → {pending_otp: true, customer}
↓ (user receives OTP email)
POST /customer_verify_otp → {token, customer}
```

Update your client code to show an OTP input screen after the first login step.

---

## Support

For issues or questions about the OTP implementation:
1. Check the troubleshooting section above
2. Review error messages in API responses
3. Check server logs for SMTP errors
4. Contact system administrator

