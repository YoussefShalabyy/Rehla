# API_REFERENCE.md

**Project:** Rehla Platform MVP
**Version:** 2.0
**Last Updated:** July 2026
**Status:** FINAL

> All endpoints are prefixed with `/api/v1/`.
> All responses follow the standard JSON structure defined in `CODING_STANDARDS.md`.
> Authentication uses Bearer tokens (Laravel Sanctum).

---

## Authentication

All authenticated endpoints require:
```
Authorization: Bearer {token}
```

---

## Standard Response Structure

```json
{
  "success": true,
  "message": "Human readable message.",
  "data": {},
  "meta": null,
  "errors": null
}
```

### Pagination (list endpoints)
```json
{
  "success": true,
  "data": [...],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 150,
    "last_page": 8
  }
}
```

---

## Phase 3: Authentication Endpoints

### Register
```
POST /api/v1/auth/register
```
**Body:**
```json
{
  "name": "Ahmed Hassan",
  "email": "ahmed@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "role": "customer"
}
```
**Roles allowed:** `customer`
**Returns:** `201` with user data + token

---

### Login
```
POST /api/v1/auth/login
```
**Body:**
```json
{
  "email": "ahmed@example.com",
  "password": "password123"
}
```
**Returns:** `200` with user data + token

---

### Logout
```
POST /api/v1/auth/logout
Authorization: Bearer {token}
```
**Returns:** `200`

---

### Get Current User
```
GET /api/v1/auth/me
Authorization: Bearer {token}
```
**Returns:** `200` with user data

---

### Social Auth (Stub — Phase 3)
```
POST /api/v1/auth/google
POST /api/v1/auth/apple
```
**Body:**
```json
{ "token": "provider_token_here" }
```
**Returns:** `200` with user data + token (or `501` until implemented)

---

## Phase 4: Listing Endpoints

### Search Listings (Public)
```
GET /api/v1/listings
```
**Query Parameters:**
```
city          string
type          property|car
property_type hotel|apartment|villa
check_in      date (YYYY-MM-DD)
check_out     date (YYYY-MM-DD)
guests        integer
min_price     integer (cents)
max_price     integer (cents)
page          integer (default: 1)
per_page      integer (default: 20, max: 50)
```
**Returns:** `200` with paginated listings

---

### Get Listing Details (Public)
```
GET /api/v1/listings/{uuid}
```
**Returns:** `200` with full listing data including media, amenities, average rating

---

### Create Listing (Admin only)
```
POST /api/v1/admin/listings
Authorization: Bearer {token}
```
**Body:**
```json
{
  "type": "property",
  "property_type": "apartment",
  "title": "Cozy Apartment in Cairo",
  "description": "Beautiful apartment near the Nile...",
  "address": "123 Nile Street",
  "city": "Cairo",
  "country": "Egypt",
  "latitude": 30.0444,
  "longitude": 31.2357,
  "base_price_cents": 50000,
  "cleaning_fee_cents": 10000,
  "extra_guest_fee_cents": 5000,
  "max_guests": 4,
  "bedrooms": 2,
  "bathrooms": 1.5,
  "amenity_ids": [1, 3, 5]
}
```
**Returns:** `201` with listing (status: `pending`)

---

### Update Listing (Admin only)
```
PUT /api/v1/admin/listings/{uuid}
Authorization: Bearer {token}
```
**Returns:** `200`

---

### Delete Listing (Admin only)
```
DELETE /api/v1/admin/listings/{uuid}
Authorization: Bearer {token}
```
**Returns:** `200` (soft delete)

---

### Get Listing Availability
```
GET /api/v1/listings/{uuid}/availability
```
**Query:** `month=2026-08`
**Returns:** `200` with array of blocked dates

---

## Phase 4: Admin Listing Endpoints

### List All Listings (Admin)
```
GET /api/v1/admin/listings
Authorization: Bearer {token}
```
**Query:** `status=pending|published|rejected|archived`

---

### Approve Listing (Admin)
```
POST /api/v1/admin/listings/{uuid}/approve
Authorization: Bearer {token}
```
**Returns:** `200` with updated listing

---

### Reject Listing (Admin)
```
POST /api/v1/admin/listings/{uuid}/reject
Authorization: Bearer {token}
```
**Body:**
```json
{ "reason": "Photos are low quality." }
```
**Returns:** `200`

---

## Phase 5: Booking Endpoints

### Create Booking
```
POST /api/v1/bookings
Authorization: Bearer {token}
```
**Body:**
```json
{
  "listing_uuid": "uuid-here",
  "check_in_date": "2026-08-01",
  "check_out_date": "2026-08-07",
  "guests_count": 2,
  "notes": "We will arrive late."
}
```
**Returns:** `201` with booking data (status: `pending`, pricing breakdown included)
**Error:** `409` if dates are not available

---

### Get Booking Details
```
GET /api/v1/bookings/{uuid}
Authorization: Bearer {token}
```
**Returns:** `200`

---

### Cancel Booking
```
POST /api/v1/bookings/{uuid}/cancel
Authorization: Bearer {token}
```
**Body:**
```json
{ "reason": "Change of plans." }
```
**Returns:** `200`

---

### List My Bookings (Customer)
```
GET /api/v1/bookings
Authorization: Bearer {token}
```
**Query:** `status=confirmed|completed|cancelled`
**Returns:** `200` paginated

---

## Phase 5: Availability Endpoints

### Block Dates (Admin)
```
POST /api/v1/admin/listings/{uuid}/availability/block
Authorization: Bearer {token}
```
**Body:**
```json
{
  "start_date": "2026-09-01",
  "end_date": "2026-09-07",
  "reason": "Maintenance"
}
```

---

### Unblock Dates (Admin)
```
DELETE /api/v1/admin/listings/{uuid}/availability/{id}
Authorization: Bearer {token}
```

---

## Phase 6: Payment Endpoints

### Process Payment
```
POST /api/v1/payments
Authorization: Bearer {token}
```
**Body:**
```json
{
  "booking_uuid": "uuid-here",
  "payment_method": "card",
  "gateway_token": "gateway-specific-token"
}
```
**Returns:** `200` with payment result

---

### Payment Webhooks (No auth — signature verified)
```
POST /api/v1/webhooks/paymob
POST /api/v1/webhooks/revenuecat
```

---

## Phase 7: Media Endpoints

### Upload Listing Image (Admin)
```
POST /api/v1/admin/listings/{uuid}/media
Authorization: Bearer {token}
Content-Type: multipart/form-data
```
**Body:** `file` (image file), `type`, `order`, `is_primary`
**Returns:** `201` with media data including URL

---

### Delete Media (Admin)
```
DELETE /api/v1/admin/media/{uuid}
Authorization: Bearer {token}
```
**Returns:** `200`

---

### Reorder Media (Admin)
```
PUT /api/v1/admin/listings/{uuid}/media/reorder
Authorization: Bearer {token}
```
**Body:**
```json
{ "ordered_ids": ["uuid1", "uuid2", "uuid3"] }
```

---

### Set Primary Image (Admin)
```
PUT /api/v1/admin/media/{uuid}/primary
Authorization: Bearer {token}
```

---

## Phase 8: Review Endpoints

### Create Review (Customer — completed booking only)
```
POST /api/v1/reviews
Authorization: Bearer {token}
```
**Body:**
```json
{
  "booking_uuid": "uuid-here",
  "rating": 5,
  "comment": "Amazing stay!"
}
```
**Returns:** `201`
**Error:** `422` if booking not completed or review already exists

---

### Get Listing Reviews (Public)
```
GET /api/v1/listings/{uuid}/reviews
```
**Returns:** `200` paginated (only `approved` reviews)

---

### Admin Reply to Review
```
POST /api/v1/admin/reviews/{uuid}/reply
Authorization: Bearer {token}
```
**Body:**
```json
{ "reply": "Thank you for your kind words!" }
```

---

## Phase 10: Admin Dashboard Endpoints

### Dashboard Stats
```
GET /api/v1/admin/dashboard/stats
Authorization: Bearer {token}
```
**Returns:**
```json
{
  "data": {
    "total_users": 1200,
    "total_bookings": 450,
    "confirmed_bookings": 380,
    "total_revenue_cents": 125000000,
    "pending_listings": 8
  }
}
```

---

### User Management (Admin)
```
GET  /api/v1/admin/users
PUT  /api/v1/admin/users/{uuid}/status
```

---

### Platform Settings (Admin)
```
GET /api/v1/admin/settings
PUT /api/v1/admin/settings/{key}
```
**Body for PUT:**
```json
{ "value": "12" }
```

---

---

## Phase 10: Admin Dashboard Endpoints

| HTTP Code | Meaning |
|---|---|
| 200 | Success |
| 201 | Resource created |
| 400 | Bad request |
| 401 | Unauthenticated |
| 403 | Forbidden (wrong role / not owner) |
| 404 | Resource not found |
| 409 | Conflict (e.g. booking dates taken) |
| 422 | Validation failed |
| 429 | Rate limit exceeded |
| 500 | Server error |
