# CODING_STANDARDS.md

**Project:** VistaStay Travel Marketplace MVP
**Version:** 1.1
**Last Updated:** July 1, 2026
**Status:** FINAL

## Purpose
This document defines practical coding standards for consistent, maintainable, and readable code. It is intentionally short and actionable.

## General Principles
- Follow the PRD, AI_ENGINEERING_GUIDE, and BUSINESS_RULES strictly.
- Simplicity > Cleverness.
- Readability is more important than brevity.
- Code must be understandable by another developer in 6+ months.

## Laravel Structure & Naming

### Controllers
- Thin controllers (max 5–7 lines per method when possible).
- Use Services for all business logic.
- Always use Form Requests for input validation.
- Example: `BookingController`, `ListingController`.

### Services
- One main service per domain (e.g. `BookingService`, `ListingService`, `PaymentService`).
- Methods should be focused and named clearly (`createBooking`, `approveListing`, `processPayment`).
- Inject dependencies via constructor.
- Services receive DTOs or primitive values only — **never** the Request object.

### Models
- Use Eloquent models as the default data access layer.
- Keep models clean (no heavy logic).
- Define relationships, scopes, and accessors/mutators when needed.
- Always use Enums for status/type fields.

### DTOs
- Create a DTO class for every Service input.
- DTOs are simple, typed value objects (readonly classes preferred).
- DTOs represent validated, sanitized input from Form Requests.

### Form Requests
- Use Form Requests for all validation.
- Keep validation rules inside the Form Request class.
- Never validate inside a Controller method body.

### Requests & Validation
- Use Form Requests for validation.
- Keep validation rules in the Form Request class.

### Routes
- Use resource routes where appropriate.
- Group by domain (e.g. `/api/bookings`, `/api/listings`).
- Use meaningful route names.
- All API routes live in `routes/api.php`.

## Code Style
- Follow PSR-12.
- Use strict typing (`declare(strict_types=1);`) in every PHP file.
- Use meaningful variable and method names.
- Keep methods under 30–40 lines when possible.
- Use early returns to reduce nesting.

## API Responses
Use a consistent JSON structure across all endpoints:

```json
{
  "success": true,
  "message": "Booking created successfully.",
  "data": {},
  "meta": null,
  "errors": null
}
```

- Include `meta` key for pagination when applicable.
- Use HTTP status codes correctly (200, 201, 422, 404, 401, 403, 500).
- Never expose internal exceptions or stack traces to the API consumer.

## Database Transactions
Always wrap critical operations inside DB transactions:

- Booking creation
- Payment processing
- Listing approval
- Refunds
- Any multi-step write operation

## Exceptions
- Never return raw exceptions to the API.
- Throw meaningful custom exceptions (e.g. `BookingConflictException`, `PaymentFailedException`).
- Use Exceptions instead of boolean returns for business failures.
- Register custom exceptions in the global exception handler.

## Configuration
Never hardcode:

- Fees / commission percentages
- Booking limits
- Cancellation windows
- Timeouts / retry counts
- Provider credentials
- Feature toggles

Use `config/` files or `platform_settings` DB table.

## External Providers
Never call external SDKs directly from Services.

Always use:

```
Interface
  ↓
Provider Adapter  (implements the Interface)
  ↓
Business Service  (depends only on the Interface)
```

Current interfaces required:
- `PaymentGatewayInterface`
- `MediaStorageInterface`
- `PushNotificationInterface`

## Repository Rule
Repositories are **NOT** the default.

Introduce a Repository only when:
- Queries become complex enough to pollute the Service.
- Multiple data sources exist.
- The abstraction clearly reduces complexity.

Otherwise use Eloquent Models directly in Services.

## Logging
Log only meaningful events:

- Booking created / confirmed / cancelled
- Payment success / failure / refund
- External provider failure
- Admin approval actions

Avoid noisy or debug logs in production. Use log levels correctly (`info`, `warning`, `error`, `critical`).

## Comments
Write self-explanatory code.

Comments should explain **WHY**, not WHAT.

Never explain what obvious code already says.

## Enums
- Never hardcode status strings.
- Use PHP Enums for all status and type fields.
- Place enums in `app/Enums/`.

## Constants & Magic Values
Avoid magic numbers and magic strings.

Use:
- PHP Enums
- Named constants in dedicated classes
- Config values

## Method Ordering in Classes
```
Public methods
  ↓
Protected methods
  ↓
Private methods
```

## Null Safety
Avoid nullable fields unless there is a clear business reason.

## Imports
- Never use fully qualified class names inside method bodies.
- Always import classes at the top of the file.

## Testing
- Unit tests for all Service methods containing business logic.
- Feature tests for all API endpoints.
- Use `RefreshDatabase` trait for database tests.
- Use factories for test data.
- Critical paths (booking, payment) must have integration tests.
- Run tests with: `php artisan test` or `composer test`.

## Before Submitting Any Code
Ask yourself:

- Is this the simplest implementation?
- Does it follow the PRD?
- Does it follow AI_ENGINEERING_GUIDE?
- Does it follow BUSINESS_RULES?
- Does it follow DATABASE_SCHEMA?
- Does it follow this file?
- Is it covered by tests?

If any answer is "No", refactor before submitting.
