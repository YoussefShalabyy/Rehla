> **⚠️ CRITICAL ARCHITECTURE NOTE (JULY 2026):**  
> The backend MVP is complete. The business model has shifted from a peer-to-peer marketplace to a platform-owned inventory model. There are no providers or hosts. All inventory is managed exclusively via the internal Admin Dashboard.  
> **For the most up-to-date, single source of truth regarding project structure, API usage, and rules, ALWAYS consult `Rehla-Web/Dashboard/docs/`.**

**Product Requirements Document (PRD): Travel Platform MVP**

**Version:** 2.0 (Customer MVP)  
**Date:** July 2026  
**Author:** Grok (Principal Software Architect, Staff Backend Engineer, Senior PM, Startup CTO)  
**Project Name:** Rehla

### # Project Goal
The MVP should be completable by one developer (or a very small team) within a reasonable time. Whenever a feature increases complexity without providing significant launch value, move it to Future Phases.

### # Guiding Principles
- Simple > Clever  
- Done > Perfect  
- Ship Fast  
- Avoid Premature Optimization  
- Avoid Over-Engineering  
- Keep Business Logic Independent  
- Prefer Configuration over Hardcoding  
- Everything should be replaceable  

### # Architecture Constraints
The following architectural decisions are **FINAL** unless explicitly changed by the Tech Lead/Product Owner:

- Laravel Monolith.  
- MySQL Database.  
- React Native (Expo) for mobile.  
- Single React Dashboard (role-based).  
- Business Logic lives inside Services.  
- Models are the default data access layer.  
- Repository Pattern is **NOT** used by default.  
- External Providers are always behind Interfaces.  
- UUIDs for public identifiers.  
- Soft Deletes on all business entities.

### # Non Goals
The MVP **intentionally DOES NOT** include:

- Microservices  
- Kubernetes  
- Event Sourcing  
- CQRS  
- Elasticsearch  
- GraphQL  
- AI Recommendations  
- Dynamic Pricing  
- Loyalty Programs  
- Real-time Chat  
- Coupons / Promotions  
- Multi-Currency  
- Multi-Language  
- Offline Support  

These will be evaluated only after successful MVP launch and validated product-market fit.

### # Target Scale
**Expected at Launch:** ~10,000 users  
**Expected Growth:** 100,000+ users  

The architecture should comfortably scale to **1M users** without requiring a full rewrite (through vertical scaling first, then horizontal where needed).

### # Project Vision
Deliver a **minimal viable platform** that lets customers book basic accommodations (hotels, apartments, villas, rooms) and cars. The platform owns and manages all inventory via an internal Admin Dashboard. There is no vendor or host portal. 

Focus: Core end-to-end booking loop that works reliably. Production-ready and designed for long-term evolution.

**MVP Success Criteria:** Functional bookings with payments, manual approval workflows, basic trust mechanisms, and simple dashboards.

### # Problem Statement (MVP)
Travelers need a simple way to find and book accommodations + cars in one place.

### # Business Model (MVP)
- Platform owns the supply.
- Revenue is generated directly from bookings.

### # User Types (MVP)
- Customer  
- Admin  

### # Supported Property Types (MVP)
- Hotels
- Apartments  
- Villas  
- Rooms
- Cars (Luxury, Sports, Economy, Family)

### # Complete MVP User Journeys
**Customer:** Search → Browse → Details → Book (Instant) → Pay → Confirmation → Review.  

**Admin:** Create/Manage listings, manage users/bookings, moderate content via internal Dashboard.

### # Authentication (MVP)
Email, Google, Apple + basic verification.

### # Search & Details (MVP)
Simple city/dates/guests search + basic filters + Google Maps.  
Detailed view with photos, availability, pricing, reviews.

### # Availability, Booking & Pricing (MVP)
- Simple calendar with transaction safety.  
- **Instant Booking** after availability check.  
- Basic nightly pricing + fixed fees.  
- Transparent total at checkout.

### # Payment Flow (MVP)
Payment providers are implementation details.  

The business layer communicates only through `PaymentGatewayInterface`.  

The initial implementation may use Paymob and/or RevenueCat depending on the target platform and store policies.  

Adding or replacing payment providers must not require changes to the booking business logic.

### # Cancellation, Reviews, Notifications & Dashboards (MVP)
- Simple cancellation policy.  
- Verified post-stay reviews.  
- Expo Push + Email notifications.  
- **Single React Dashboard** for internal Admins only.

### # Media (MVP)
Cloudinary uploads (signed URLs). Metadata only in DB. Each image belongs to exactly one entity.

### # Architecture & Extensibility (MVP)
- Laravel Services + Models (core).  
- Interfaces only for external providers.  
- Clean separation so future features plug in easily.

**External Providers** (designed for easy addition):

**Payment**  
- Paymob  
- RevenueCat  
- Stripe  
- Fawry  
- PayPal  

**Media**  
- Cloudinary  
- AWS S3  
- Cloudflare R2  

**Notifications**  
- Expo Push  
- Firebase  
- OneSignal  

**Hotels**  
- Manual Dashboard (MVP)  
- Expedia  
- Hotelbeds  
- Amadeus  
- Booking.com  

**Cars**  
- Manual Dashboard (MVP)  
- RentalCars  
- Local Rental APIs  

**Maps**  
- Google Maps  
- Mapbox (future)  

**Places**  
- Google Places  

**Email**  
- SMTP  
- Mailgun  
- Resend  

### # Decision Priority
When multiple solutions exist, choose according to this order:

1. **Simplicity**  
2. **Maintainability**  
3. **Readability**  
4. **Scalability**  
5. **Performance**  
6. **Extensibility**

---
