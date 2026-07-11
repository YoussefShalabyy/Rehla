# BUSINESS_RULES.md

**Project:** Rehla Platform MVP  
**Version:** 2.0  
**Last Updated:** July 2026  
**Status:** FINAL (Single Source of Truth alongside the PRD)

## Purpose
This document defines all core business rules for the VistaStay platform. It serves as the authoritative reference for both humans and AI agents to ensure consistent behavior across the application.

These rules must be enforced in the Service layer. Controllers should remain thin.

## 1. User & Role Rules

- There are two user types: Customer, Admin.
- A user can have only one primary role (RBAC enforced).
- UUIDs are used for all public user identifiers.

## 2. Listing Rules

- Listings include Accommodations (Hotels, Apartments, Villas, Rooms) and Cars (Luxury, Sports, Economy, Family).
- All inventory is owned and managed by the platform via the Admin Dashboard.
- Soft delete supported. Hard delete only by Admin.

## 3. Availability & Booking Rules

- Availability is managed per listing via a simple calendar.
- Bookings use **Instant Booking**.
- Overlapping bookings are strictly prevented using database transactions.
- A temporary hold is placed during the payment process (expires automatically if payment fails).
- Booking statuses: Pending (pre-payment), Confirmed, Active, Completed, Cancelled.
- Payment must succeed for a booking to become Confirmed.

## 4. Pricing Rules

- Each listing has a base nightly price.
- Additional fixed fees: cleaning fee, extra guest fee (if configured).
- Platform service fee is added at checkout (configurable).
- Total price must be shown transparently to the customer before payment.
- No dynamic pricing, seasonal adjustments, or discounts in MVP.

## 5. Payment Rules

- All payments go through `PaymentGatewayInterface`.
- Business logic never depends on a specific provider.
- Payment is required to confirm a booking.
- Refunds are supported for cancellations according to the cancellation policy.

## 6. Cancellation & Refund Rules

- A single simple cancellation policy applies (configurable by platform).
- Example: Full refund if cancelled at least X days before check-in.
- Cancellations after the window may result in no refund (platform decides case-by-case in MVP).
- Refunds are processed through the original payment gateway.

## 7. Review Rules

- Reviews can only be left by customers who completed a verified stay (booking status = Completed).
- Reviews include star rating (1-5) and optional text.
- Admin can moderate (hide) inappropriate reviews.
- Average rating is displayed on listings.

## 8. Notification Rules

- Customers and Admins receive notifications for key events:
  - Booking Confirmed
  - Booking Cancelled
  - Check-in reminder
- Use Expo Push + Email in MVP.

## 9. Media Rules

- All media is handled through `MediaStorageInterface` (Cloudinary in MVP).
- Only metadata (URLs, type, entity_id) is stored in the database.
- Every image belongs to exactly one entity (Listing, User, etc.).

## 10. General Business Rules

- All monetary values must use integer cents (no floats).
- All critical operations (booking creation, payment, approval) must use database transactions.
- Soft deletes are used for business entities to preserve history.
- UUIDs for all public-facing IDs (security & obfuscation).
- Admin has full override capability for disputes and manual adjustments.

## Enforcement
- All rules must be implemented and validated in the Service layer.
- Use Laravel validation + custom Service methods for complex rules.
- Critical rules (booking, payment) must have accompanying tests.

---

**This document is FINAL.**  
Any change to business rules must update this file and `PROJECT_STATUS.md`.

