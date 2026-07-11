# DATABASE_SCHEMA.md

**Project:** Rehla Platform MVP
**Version:** 2.0
**Last Updated:** July 2026
**Status:** FINAL

> **Schema Decisions:**
> - The platform owns and manages all supply. There is no provider or host role.
> - `media` table intentionally has **no soft deletes** — physical deletion via `MediaStorageInterface` is required. Media deletion is irreversible by design.
> - `availability_blocks` table has no `uuid` — these records are internal/operational and not exposed as public UUIDs.

## Design Principles
- Normalize first.
- Never use floats for money (use integer cents).
- Use BIGINT UNSIGNED AUTO_INCREMENT as primary key.
- Use UUID (CHAR(36)) as unique public identifier.
- Soft Deletes on all business entities.
- Index foreign keys and frequently queried columns.
- Transactions for critical operations.
- metadata should only contain provider-specific or optional data. Never core business fields.

## Core Tables

### users
- `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `uuid` CHAR(36) UNIQUE NOT NULL
- `name`
- `email` (unique)
- `email_verified_at`
- `password`
- `phone` (nullable)
- `role` (enum: customer, admin)
- `status` (enum: active, pending, suspended)
- `avatar_url` (nullable)
- `last_login_at` (timestamp, nullable)
- `provider` (nullable)
- `provider_id` (nullable)
- `remember_token`
- `soft_deletes`
- `timestamps`

### listings
- `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `uuid` CHAR(36) UNIQUE NOT NULL
- `created_by` (foreign → users)
- `type` (enum: accommodation, car)
- `property_type` (enum: hotel, apartment, villa, room, nullable for cars)
- `title`
- `description`
- `address`
- `country`
- `city`
- `latitude`
- `longitude`
- `base_price_cents` (integer)
- `cleaning_fee_cents` (integer, default 0)
- `extra_guest_fee_cents` (integer, default 0)
- `status` (enum: pending, published, rejected, archived)
- `is_instant_bookable` (boolean, default true)
- `max_guests` (integer)
- `bedrooms` (integer, nullable)
- `bathrooms` (decimal, nullable)
- `transmission` (string, nullable – for cars)
- `fuel_type` (string, nullable – for cars)
- `soft_deletes`
- `timestamps`

### media
- `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `uuid` CHAR(36) UNIQUE NOT NULL
- `entity_type` (string) -- e.g. Listing, User
- `entity_id` (BIGINT UNSIGNED)
- `type` (enum: image, video)
- `provider` (string, default 'cloudinary')
- `url`
- `public_id` (string, nullable) -- provider-specific ID for deletion
- `order` (integer, default 0)
- `is_primary` (boolean, default false)
- `timestamps`

> **No soft deletes on media.** Deletion is permanent and must go through `MediaStorageInterface` to also remove from the provider (Cloudinary, S3, etc.).

### amenities
- `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `name`
- `icon` (nullable)
- `type` (enum: property, car)
- `timestamps`

### listing_amenity (pivot)
- `listing_id` (foreign)
- `amenity_id` (foreign)
- Primary key (listing_id + amenity_id)

### availability_blocks
- `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `listing_id` (foreign → listings)
- `start_date` (date)
- `end_date` (date)
- `blocked_by_user_id` (foreign → users)
- `reason` (string, nullable)
- `timestamps`

### bookings
- `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `uuid` CHAR(36) UNIQUE NOT NULL
- `booking_reference` VARCHAR(20) UNIQUE NOT NULL  -- e.g. VS-8F2K19
- `listing_id` (foreign → listings)
- `customer_id` (foreign → users)
- `check_in_date` (date)
- `check_out_date` (date)
- `guests_count` (integer)
- `currency` (string, default 'EGP')
- `total_amount_cents` (integer)
- `platform_fee_cents` (integer)
- `status` (enum: pending, confirmed, active, completed, cancelled)
- `payment_status` (enum: pending, paid, refunded, failed)
- `cancellation_reason` (text, nullable)
- `notes` (text, nullable)
- `soft_deletes`
- `timestamps`

### payments
- `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `uuid` CHAR(36) UNIQUE NOT NULL
- `booking_id` (foreign → bookings)
- `amount_cents` (integer)
- `fee_cents` (integer, default 0)
- `gateway` (enum: paymob, revenuecat, stripe, fawry, paypal, null_adapter)
- `gateway_transaction_id` (string, nullable)
- `provider_response` (json, nullable)
- `status` (enum: pending, succeeded, failed, refunded)
- `payment_method` (string, nullable)
- `metadata` (json, nullable) -- provider-specific optional data only
- `soft_deletes`
- `timestamps`

### reviews
- `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `booking_id` (foreign → bookings)
- `reviewer_id` (foreign → users)
- `listing_id` (foreign → listings)
- `rating` (tinyint 1-5)
- `comment` (text, nullable)
- `admin_reply` (text, nullable)
- `admin_reply_at` (timestamp, nullable)
- `status` (enum: pending, approved, hidden)
- `soft_deletes`
- `timestamps`

### platform_settings
- `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
- `key` (string, unique)
- `value` (text)
- `type` (enum: string, integer, boolean, json)
- `description` (string, nullable)
- `timestamps`

## Migration Order
1. users  
2. listings  
3. amenities  
4. listing_amenity (pivot)  
5. media  
6. availability_blocks  
7. bookings  
8. payments  
9. reviews  
10. platform_settings

## Future Tables
- hotel_providers
- car_providers
- car_brands
- car_models
- (General polymorphic media already covered)

## Future Features Notes
- Expedia / Hotelbeds / Amadeus integrations
- Additional payment gateways
- Mapbox support
- Multi-currency full support

---

**This document is now FINAL.**

