# 🚀 Rehla: Frontend Integration Guide

This document is specifically crafted to guide AI Frontend Developers and human developers integrating with the Rehla Backend API.

## 🔗 Base Configuration
- **Base URL:** `http://localhost:8000/api/v1` (Update for staging/production)
- **Headers Required:**
  - `Accept: application/json`
  - `Content-Type: application/json`
  - `Authorization: Bearer {token}` (For protected routes)

## 📦 Global Response Structure
**Every** API response follows this exact structure. Do not expect raw arrays or arbitrary objects at the root level.

```json
{
  "success": true,
  "message": "Operation successful.",
  "data": { ... }, // Payload goes here
  "meta": null,    // Pagination data goes here if paginated
  "errors": null   // Validation errors go here if success=false
}
```

### 🚫 Error Handling (422 Unprocessable Entity)
When validation fails, `success` will be `false`. For simplicity, the **`message`** field will always contain the very first human-readable validation error directly, so you can display it immediately without parsing the `errors` object. The `errors` bag is still provided if you need field-specific highlighting.

```json
{
  "success": false,
  "message": "The email has already been taken.",
  "errors": {
    "email": ["The email has already been taken."]
  }
}
```

### 📄 Pagination
For list endpoints, `data` is an array of objects, and `meta` contains pagination info:
```json
"meta": {
  "pagination": {
    "total": 50,
    "count": 20,
    "per_page": 20,
    "current_page": 1,
    "total_pages": 3
  }
}
```

---

## 🔐 Authentication Flow (Sanctum)
We use Laravel Sanctum tokens.

1. **Register/Login:** `POST /api/v1/auth/login`
   - Send `email`, `password`, and `device_name`.
   - Response contains `data.token` and `data.user`.
2. **Store Token:** Save the token securely on the device.
3. **Use Token:** Attach `Authorization: Bearer {token}` to all subsequent requests.
4. **Logout:** `POST /api/v1/auth/logout`

---

## 📸 Media Uploading
When uploading files (e.g., listing photos), you MUST use `multipart/form-data` instead of JSON.

**Endpoint:** `POST /api/v1/owner/listings/{uuid}/media`
**Body:**
- `file`: The actual image/video file.
- `is_primary`: `1` or `0`

---

## 🗂️ Core Endpoints Overview

### 👤 Public / Customer
- `GET /listings` (Search listings, accepts `?city=`, `?type=`, `?min_price=`, etc.)
- `GET /listings/{uuid}` (View details, includes amenities, primary media, reviews)
- `POST /bookings` (Create a booking: needs `listing_uuid`, `check_in_date`, `check_out_date`, `guests_count`)
- `POST /payments` (Initiate payment: needs `booking_uuid`, `gateway: paymob`. Returns `payment_url` to open in WebView)
- `GET /bookings` (View customer's bookings)

### 🏢 Provider (Owner)
*All routes prefixed with `/owner` and require `Provider` role token.*
- `GET /owner/dashboard/stats`
- `GET /owner/listings`
- `POST /owner/listings` (Create new listing)
- `POST /owner/listings/{uuid}/media` (Upload photos)
- `POST /owner/availability/block` (Block dates for maintenance)
- `GET /owner/bookings` (View incoming bookings)

### 👑 Admin
*All routes prefixed with `/admin` and require `Admin` role token.*
- `GET /admin/dashboard/stats`
- `GET /admin/listings`
- `POST /admin/listings/{uuid}/approve`
- `PUT /admin/users/{uuid}/status` (Suspend/Activate users)

---

## 📋 Enums & Types (Strict)

**User Roles (`role`):**
`customer`, `provider`, `admin`

**Listing Types (`type`):**
`property`, `car`

**Listing Status (`status`):**
`pending`, `published`, `rejected`

**Booking Status (`status`):**
`pending` (awaiting payment), `confirmed` (paid), `completed` (past dates), `cancelled`

**Payment Status (`status`):**
`pending`, `succeeded`, `failed`, `refunded`
