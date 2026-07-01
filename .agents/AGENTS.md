# AGENTS.md — VistaStay AI Operating Rules

**Project:** VistaStay Travel Marketplace MVP
**Version:** 1.0
**Last Updated:** July 1, 2026

> These rules are **binding** for every AI agent working on this project.
> Read this file completely before touching any code.

---

## 0. Mandatory First Steps (Always)

Before writing **any** code:

1. Read `docs/PRD.md`
2. Read `docs/AI_ENGINEERING_GUIDE.md`
3. Read `PROJECT_STATUS.md` — know which phase you're on
4. Read `docs/BUSINESS_RULES.md`
5. Read `docs/DATABASE_SCHEMA.md`
6. Read `docs/CODING_STANDARDS.md`
7. Read `docs/IMPLEMENTATION_ROADMAP.md` — know what your phase requires

**No exceptions. No shortcuts. Docs first, code second.**

---

## 1. Architecture Rules (Non-Negotiable)

These are **final decisions**. Do not debate or change them:

| Rule | What it means |
|---|---|
| Laravel Monolith | No microservices, no separate services/APIs |
| MySQL Database | No NoSQL, no Redis as primary DB |
| Business Logic in Services | Controllers are thin (≤7 lines per method) |
| Models = default data layer | No Repository Pattern unless explicitly justified |
| External Providers behind Interfaces | Never call SDKs directly from Services |
| UUIDs for public IDs | Never expose sequential integer IDs in the API |
| Soft Deletes everywhere | Never hard-delete business entities |
| Integer cents for money | Never use floats for any monetary calculation |
| `declare(strict_types=1)` | In every PHP file |
| PHP Enums for status fields | Never use raw strings for status/type values |

---

## 2. Code Quality Rules

### General
- One responsibility per class, method, and file.
- Methods must be ≤40 lines. If longer, extract.
- No magic numbers or strings — use Enums or constants.
- No hardcoded config values (fees, timeouts, limits) — use `config/` or `platform_settings`.

### Naming Conventions
- **Services:** `BookingService`, `ListingService`, `PaymentService`
- **DTOs:** `CreateBookingDTO`, `RegisterDTO`, `SearchListingDTO`
- **Requests:** `CreateBookingRequest`, `RegisterRequest`
- **Controllers:** `BookingController` (resource-style where possible)
- **Enums:** `BookingStatus`, `UserRole`, `PaymentGateway`
- **Interfaces:** `PaymentGatewayInterface`, `MediaStorageInterface`
- **Adapters:** `PaymobAdapter`, `CloudinaryAdapter`, `ExpoAdapter`
- **Exceptions:** `BookingConflictException`, `PaymentFailedException`

### Folder Structure (Strict)
```
app/
├── DTOs/
├── Enums/
├── Exceptions/
├── Http/
│   ├── Controllers/Api/{Auth,Admin,Owner,Customer}/
│   ├── Middleware/
│   └── Requests/{Auth,Listing,Booking,Admin}/
├── Interfaces/
├── Models/
├── Services/
│   ├── Auth/
│   ├── Booking/
│   ├── Listing/
│   ├── Media/
│   │   └── Adapters/
│   ├── Notification/
│   │   └── Adapters/
│   └── Payment/
│       └── Adapters/
└── Providers/
```

---

## 3. Service Layer Rules

- Services are the **only** place for business logic.
- Services receive DTOs or primitives — **never** a Request object.
- Constructors use dependency injection (never `new SomeService()`).
- All critical operations wrapped in `DB::transaction()`.
- Services throw typed exceptions — never return `false` or `null` for failures.

### Pattern: Controller → Service

```php
// ✅ Correct
class BookingController extends Controller
{
    public function store(CreateBookingRequest $request, BookingService $service): JsonResponse
    {
        $booking = $service->createBooking(
            CreateBookingDTO::fromRequest($request),
            $request->user()
        );

        return response()->json(['success' => true, 'data' => $booking], 201);
    }
}

// ❌ Wrong — business logic in controller
class BookingController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $booking = Booking::create([...]); // No! Logic belongs in Service
        // ...
    }
}
```

---

## 4. Interface Pattern Rules

Every external provider must go through an interface:

```php
// ✅ Correct — Service depends on interface
class PaymentService
{
    public function __construct(private PaymentGatewayInterface $gateway) {}
}

// ❌ Wrong — Service depends on concrete class
class PaymentService
{
    public function __construct(private PaymobAdapter $paymob) {}
}
```

Current required interfaces:
- `App\Interfaces\PaymentGatewayInterface`
- `App\Interfaces\MediaStorageInterface`
- `App\Interfaces\PushNotificationInterface`

---

## 5. API Response Rules

Always use this consistent structure:

```json
{
  "success": true,
  "message": "Booking created successfully.",
  "data": {},
  "meta": null,
  "errors": null
}
```

- HTTP status codes must be correct (201 for created, 422 for validation, 409 for conflicts, 403 for forbidden).
- Never expose raw exception messages in production.
- Paginated lists must include `meta.pagination`.

---

## 6. Database Rules

- Never use floats for money. Always integer cents.
- Always wrap booking creation, payment, and approval in `DB::transaction()`.
- Use `SELECT ... FOR UPDATE` inside transactions to prevent race conditions on availability.
- Index every foreign key column.
- Never hard delete. Always soft delete business entities.
- Never put business logic in migrations.
- Migration names must be descriptive: `create_bookings_table`, `add_platform_fee_to_bookings`.

---

## 7. Testing Rules

> The AI agent **must** run tests using the terminal. Don't skip this.

### Commands

```bash
# Run all tests
php artisan test

# Run tests for a specific domain
php artisan test --filter=Booking
php artisan test --filter=Payment
php artisan test --filter=Auth

# Run with coverage
php artisan test --coverage

# Run a specific test file
php artisan test tests/Feature/BookingTest.php
```

### What to Test
- **Unit tests** for every Service method with business logic.
- **Feature tests** for every API endpoint.
- **Critical paths must have tests:** Booking conflict prevention, payment flow, review eligibility.
- Use `RefreshDatabase` on all database tests.
- Use factories for all test data — never hardcode test data.
- Use `NullPaymentAdapter` and stub adapters in tests — never call real APIs.

### Test Naming
```php
it('prevents double booking for the same dates', function () { ... });
it('returns 422 when check_in is in the past', function () { ... });
it('calculates platform fee correctly', function () { ... });
```

### Required Coverage
- Service layer: **>80%** coverage.
- All API endpoints: at least one happy path + one sad path test.

---

## 8. Phase Execution Rules

1. **Read the phase in `docs/IMPLEMENTATION_ROADMAP.md` fully before starting.**
2. Implement everything listed in the phase — no selective implementation.
3. **Run all tests. All must pass.**
4. If a test fails, fix it before moving on.
5. **Update `PROJECT_STATUS.md`:** mark phase as 🟢 Complete, set next phase.
6. Log any architectural decisions made during the phase in the Architecture Decisions Log.

---

## 9. Common Mistakes to Avoid

| ❌ Mistake | ✅ Correct Approach |
|---|---|
| Business logic in Controller | Move to Service |
| Calling `PaymobAdapter` directly in Service | Use `PaymentGatewayInterface` |
| Using `float` for price calculations | Use `int` cents |
| Hardcoding `0.10` as platform fee | Read from `platform_settings` |
| Skipping DB transaction on booking creation | Always use `DB::transaction()` |
| Returning `false` from Service on failure | Throw a typed Exception |
| Using raw strings `'pending'` for status | Use `BookingStatus::Pending` Enum |
| Returning raw exception to API | Catch and return proper JSON response |
| Writing logic-heavy migrations | Keep migrations schema-only |
| Not paginating list endpoints | Always paginate (default 20/page) |
| Calling Eloquent N+1 in list endpoints | Eager load with `->with(...)` |
| Passing Request object to Service | Pass a DTO or primitives |

---

## 10. When You Encounter Ambiguity

If something is unclear:

1. Check the docs in this order: `PRD.md` → `BUSINESS_RULES.md` → `DATABASE_SCHEMA.md` → `AI_ENGINEERING_GUIDE.md`
2. If still unclear, **implement the simpler solution** and leave a `// TODO:` comment noting the ambiguity.
3. Do not invent new business rules — document the assumption and implement conservatively.

---

## 11. Configuration & Environment

- All provider credentials live in `.env` only — never in code.
- All configurable values live in `config/` files.
- All business-configurable values (fees, limits) live in `platform_settings` DB table.
- Use `config('payment.default')` not `env('PAYMENT_GATEWAY')` inside application code.

---

## 12. What NOT to Build in MVP

Do not implement any of these — they are explicitly excluded:

- Microservices or service mesh
- Elasticsearch or full-text search engines
- GraphQL
- AI recommendations or dynamic pricing
- Loyalty programs or coupons
- Real-time chat (WebSockets)
- Multi-currency support
- Multi-language (i18n)
- Offline mode
- Event Sourcing or CQRS

If a feature is not in the PRD, do not build it. Ask first.

---

## Quick Reference

```
docs/PRD.md                    → Product requirements (what to build)
docs/BUSINESS_RULES.md         → Business logic rules (how it works)
docs/DATABASE_SCHEMA.md        → Tables, columns, relations
docs/CODING_STANDARDS.md       → Code style and patterns
docs/AI_ENGINEERING_GUIDE.md   → Architecture philosophy
docs/IMPLEMENTATION_ROADMAP.md → Phase-by-phase execution plan
PROJECT_STATUS.md              → Current progress (update this!)
.agents/AGENTS.md              → This file (AI operating rules)
```
