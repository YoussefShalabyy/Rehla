# AI Engineering Guide

**Project:** VistaStay Travel Marketplace MVP  
**Version:** 1.1  
**Last Updated:** June 30, 2026  
**Status:** FINAL (Single Source of Truth alongside the PRD)

## Purpose
This document is the definitive operating manual for any AI agent or human developer working on VistaStay. It ensures long-term consistency, architectural integrity, and prevents drift over 5–10 years.

**The PRD is the single source of truth.** This file explains *how* to implement and extend the project while strictly respecting the PRD.

## Core Philosophy
- Simplicity First  
- Done > Perfect  
- No Premature Optimization  
- Explicit Decisions  

## Architecture Constraints (Non-Negotiable)
- Laravel Monolith  
- MySQL Database  
- React Native (Expo) for mobile  
- Single React Dashboard (role-based)  
- Business Logic lives inside Services  
- Models are the default data access layer  
- Repository Pattern is **NOT** used by default  
- External Providers are always behind Interfaces  
- UUIDs for public identifiers  
- Soft Deletes on all business entities  

**Decision Priority:**  
1. Simplicity → 2. Maintainability → 3. Readability → 4. Scalability → 5. Performance → 6. Extensibility

## AI Development Workflow
For every new feature or change, **always** follow this order:

1. Read the full PRD.  
2. Read this AI Engineering Guide.  
3. Read PROJECT_STATUS.md.  
4. Identify affected modules.  
5. Review BUSINESS_RULES.md.  
6. Review DATABASE_SCHEMA.md.  
7. Design the API (if applicable).  
8. Design the Service layer.  
9. Implement.  
10. Write or update tests.  
11. Update PROJECT_STATUS.md (and other relevant docs) if needed.

Never skip documentation review before implementation.

## Architecture Principles
- High Cohesion  
- Low Coupling  
- SOLID  
- DRY  
- KISS  
- YAGNI  

## Configuration
Never hardcode values that may change (commission percentage, booking limits, cancellation windows, platform fees, feature toggles, etc.).  

Use configuration files (`config/`) or database settings.

## Performance
**Default expectations:**
- Use eager loading when appropriate.  
- Paginate list endpoints.  
- Queue heavy jobs (notifications, reports, etc.).  
- Cache only when there is a measurable benefit.  

Do not optimize prematurely.

## Database Principles
- Normalize first.  
- Never use floats for money.  
- Use transactions for critical operations (bookings, payments, approvals).  
- Index foreign keys.  
- Keep tables focused on one responsibility.

## Before Writing Code
Before implementing anything, ask:
- Is this the simplest solution?
- Does this follow the PRD exactly?
- Does this violate any architecture rule?
- Can another developer understand this in six months?
- Can this be extended without rewriting core logic?

## Documentation Responsibility
Whenever a significant architectural or implementation decision is made:
- Update `PROJECT_STATUS.md` for progress/architecture changes.
- Update `DATABASE_SCHEMA.md` if database changes.
- Update `BUSINESS_RULES.md` if business logic changes.
- Update the PRD only if product requirements change.

## External Providers & Extensibility
- Manual implementations are the default (MVP).
- All external integrations must go behind their respective Interfaces.
- Core business logic must never depend on concrete provider implementations.

## Folder Structure & Naming
Follow Laravel conventions with clear responsibility.  
Follow `CODING_STANDARDS.md`.

---

**This document is FINAL and binding.**  
It will be updated only when the PRD is explicitly updated.

