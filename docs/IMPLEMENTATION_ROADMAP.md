# IMPLEMENTATION_ROADMAP.md

**Project:** VistaStay Travel Marketplace MVP
**Version:** 2.0
**Last Updated:** July 1, 2026
**Status:** ACTIVE — Living Document

> This document is the **single source of truth for implementation order**.
> Every AI session must read this file before writing code.
> Mark phases complete in `PROJECT_STATUS.md` — do **NOT** modify this roadmap's phase content.
> This roadmap is designed for a production system built to last 5–10 years.

---

## Implementation Order Overview

```
Phase 0  → Foundation (skeleton, interfaces, enums — no features)
Phase 1  → Database Migrations
Phase 2  → Models & Relationships
Phase 3  → Authentication (Sanctum, Email, Social stubs)
Phase 4  → Listings Management
Phase 5  → Availability & Booking
Phase 6  → Payment Integration
Phase 7  → Media Uploads
Phase 8  → Reviews
Phase 9  → Notifications
Phase 10 → Admin & Owner Dashboard APIs
Phase 11 → Seeders, Factories & Demo Data
Phase 12 → Security Hardening & Production Readiness
```

---

## Phase 0 — Foundation

**Status:** 🔴 Not Started

### Goal
Build the complete architectural skeleton that every future phase depends on.
No business features. No database migrations. Just infrastructure: base classes, enums, interfaces, fake adapters, config files, folder structure, and testing setup.

### Why This Phase Exists
Every future phase needs: typed enums, provider interfaces, null adapters for testing, config files, and a unified API response format. Building this first prevents architectural drift, eliminates duplication, and ensures the AI has a consistent foundation to build on without making architectural decisions mid-feature.

### Dependencies
None. This is the starting point.

### Modules Involved
- `app/Enums/` — all enums
- `app/Interfaces/` — all provider interfaces
- `app/Services/Payment/Adapters/` — NullPaymentAdapter
- `app/Services/Media/Adapters/` — LocalMediaAdapter (for tests)
- `app/Services/Notification/Adapters/` — NullNotificationAdapter
- `app/Http/Controllers/` — BaseApiController
- `app/Exceptions/` — exception hierarchy
- `app/Providers/` — AppServiceProvider (interface bindings)
- `config/` — payment.php, media.php, notification.php, platform.php

### Database Impact
None. No migrations in this phase.

### API Impact
- `routes/api.php` created but empty (returns placeholder 200 on `/api/v1/health`)
- API versioning prefix `/api/v1/` established

### Deliverables

**Folder Structure:**
```
app/
├── DTOs/
├── Enums/
│   ├── UserRole.php
│   ├── UserStatus.php
│   ├── ListingType.php
│   ├── PropertyType.php
│   ├── ListingStatus.php
│   ├── BookingStatus.php
│   ├── PaymentStatus.php
│   ├── PaymentGateway.php
│   ├── MediaType.php
│   ├── MediaProvider.php
│   ├── ReviewStatus.php
│   └── SettingType.php
├── Exceptions/
│   ├── BaseException.php
│   ├── BookingConflictException.php
│   ├── ListingNotAvailableException.php
│   ├── PaymentFailedException.php
│   ├── UnauthorizedActionException.php
│   └── NotFoundException.php
├── Http/
│   ├── Controllers/
│   │   ├── Controller.php (BaseApiController with response helpers)
│   │   └── Api/
│   │       ├── Auth/
│   │       ├── Admin/
│   │       ├── Owner/
│   │       └── Customer/
│   ├── Middleware/
│   └── Requests/
│       ├── Auth/
│       ├── Listing/
│       ├── Booking/
│       └── Admin/
├── Interfaces/
│   ├── PaymentGatewayInterface.php
│   ├── MediaStorageInterface.php
│   └── PushNotificationInterface.php
├── Models/
├── Services/
│   ├── Auth/
│   ├── Booking/
│   ├── Listing/
│   ├── Payment/
│   │   └── Adapters/
│   │       └── NullPaymentAdapter.php
│   ├── Media/
│   │   └── Adapters/
│   │       └── LocalMediaAdapter.php
│   └── Notification/
│       └── Adapters/
│           └── NullNotificationAdapter.php
└── Providers/
    └── AppServiceProvider.php
```

**Interfaces to define:**
```php
// PaymentGatewayInterface.php
interface PaymentGatewayInterface {
    public function charge(array $payload): array;
    public function refund(string $transactionId, int $amountCents): array;
    public function verifyWebhook(array $payload, string $signature): bool;
}

// MediaStorageInterface.php
interface MediaStorageInterface {
    public function upload(UploadedFile $file, string $folder): array; // ['url', 'public_id']
    public function delete(string $publicId): void;
}

// PushNotificationInterface.php
interface PushNotificationInterface {
    public function send(string $token, string $title, string $body, array $data = []): void;
    public function sendBulk(array $tokens, string $title, string $body, array $data = []): void;
}
```

**Null Adapters (for testing and local dev):**
- `NullPaymentAdapter` — returns fake success response, never calls real API
- `LocalMediaAdapter` — stores files in `storage/app/media/`, returns local URL
- `NullNotificationAdapter` — logs to file, never calls Expo/FCM

**Config files to create:**
```
config/payment.php    → default gateway, credentials per provider
config/media.php      → default provider, Cloudinary credentials
config/notification.php → default provider, Expo credentials
config/platform.php   → app-level settings fallbacks
```

**BaseApiController response format:**
```php
protected function success(mixed $data = null, string $message = 'Success', int $status = 200): JsonResponse
protected function created(mixed $data, string $message = 'Created'): JsonResponse
protected function error(string $message, int $status, array $errors = []): JsonResponse
protected function paginated(LengthAwarePaginator $paginator, string $resource): JsonResponse
```

**AppServiceProvider bindings:**
```php
$this->app->bind(PaymentGatewayInterface::class, NullPaymentAdapter::class);
$this->app->bind(MediaStorageInterface::class, LocalMediaAdapter::class);
$this->app->bind(PushNotificationInterface::class, NullNotificationAdapter::class);
// Switch to real adapters once credentials are configured
```

### Testing Strategy
- Install Pest PHP: `composer require --dev pestphp/pest --with-all-dependencies && php artisan pest:install`
- Verify: `php artisan test` → default tests pass with Pest
- No business tests yet — foundation only

### Exit Criteria
- [ ] `php artisan config:cache` → no errors
- [ ] `php artisan route:list` → `/api/v1/` prefix visible
- [ ] `php artisan optimize:clear` → no class resolution errors
- [ ] `GET /api/v1/health` → returns `{"success": true, "message": "OK"}`
- [ ] `php artisan test` → all default tests pass
- [ ] All 12 Enums created with correct values
- [ ] All 3 Interfaces created
- [ ] All 3 Null/Local Adapters created and bound in AppServiceProvider
- [ ] `declare(strict_types=1)` in every PHP file created

---

## Phase 1 — Database Migrations

**Status:** 🔴 Not Started

### Goal
All database tables created exactly as defined in `DATABASE_SCHEMA.md`. Correct column types, indexes, foreign keys, and constraints — in the right migration order.

### Why This Phase Exists
Migrations are the contract between the application and the database. Getting them right upfront prevents costly schema changes mid-implementation. All future phases depend on a stable, correct schema.

### Dependencies
- Phase 0 complete (Enums defined — needed for migration enum values)
- MySQL database created and `.env` configured (`DB_DATABASE=rehla`)

### Modules Involved
- `database/migrations/`

### Database Impact
All 10 tables created:
1. `users` (modified from Laravel default)
2. `listings`
3. `amenities`
4. `listing_amenity` (pivot)
5. `media`
6. `availability_blocks`
7. `bookings`
8. `payments`
9. `reviews`
10. `platform_settings`

### Migration Execution Order
```bash
php artisan make:migration modify_users_table_for_vistastay
php artisan make:migration create_listings_table
php artisan make:migration create_amenities_table
php artisan make:migration create_listing_amenity_table
php artisan make:migration create_media_table
php artisan make:migration create_availability_blocks_table
php artisan make:migration create_bookings_table
php artisan make:migration create_payments_table
php artisan make:migration create_reviews_table
php artisan make:migration create_platform_settings_table
```

### Critical: Users Migration
The default Laravel users table must be modified — do NOT create a new one:
```php
// Add to existing users table:
$table->char('uuid', 36)->unique()->after('id');
$table->string('phone')->nullable()->after('email_verified_at');
$table->enum('role', ['customer', 'provider', 'admin'])->default('customer');
$table->enum('status', ['active', 'pending', 'suspended'])->default('active');
$table->string('avatar_url')->nullable();
$table->timestamp('last_login_at')->nullable();
$table->string('provider')->nullable();  // social auth provider name
$table->string('provider_id')->nullable();
$table->softDeletes();
// Remove: 'password' nullable? No — keep required but nullable for social-only users
```

### Critical: Bookings Migration (money + reference)
```php
$table->bigIncrements('id');
$table->char('uuid', 36)->unique();
$table->varchar('booking_reference', 20)->unique(); // e.g. VS-8F2K19A3
$table->foreignId('listing_id')->constrained()->cascadeOnDelete();
$table->foreignId('customer_id')->references('id')->on('users');
$table->date('check_in_date');
$table->date('check_out_date');
$table->unsignedSmallInteger('guests_count');
$table->string('currency', 3)->default('EGP');
$table->unsignedBigInteger('total_amount_cents');      // NEVER float
$table->unsignedBigInteger('platform_fee_cents');      // NEVER float
$table->enum('status', ['pending','confirmed','active','completed','cancelled'])->default('pending');
$table->enum('payment_status', ['pending','paid','refunded','failed'])->default('pending');
$table->text('cancellation_reason')->nullable();
$table->text('notes')->nullable();
$table->softDeletes();
$table->timestamps();
// Indexes:
$table->index(['listing_id', 'check_in_date', 'check_out_date']); // availability queries
$table->index(['customer_id', 'status']);
```

### Indexes to Create (all foreign keys + query columns)
```
users: (email), (uuid), (role, status)
listings: (owner_id), (uuid), (city, type, status), (status)
bookings: (listing_id, check_in_date, check_out_date), (customer_id, status), (uuid)
payments: (booking_id), (uuid), (gateway_transaction_id)
reviews: (listing_id, status), (booking_id)
media: (entity_type, entity_id)
availability_blocks: (listing_id, start_date, end_date)
```

### API Impact
None. Migrations only.

### Services Involved
None. Migrations only.

### Testing Strategy
```bash
php artisan migrate:fresh
php artisan migrate:status
```
- All migrations must run with zero errors
- `migrate:status` must show all as `Ran`

### Exit Criteria
- [ ] `php artisan migrate:fresh` → zero errors
- [ ] All 10 tables exist in database
- [ ] No float columns anywhere (all money = `unsignedBigInteger`)
- [ ] All foreign keys have indexes
- [ ] `bookings.booking_reference` column is `VARCHAR(20) UNIQUE`
- [ ] All business tables have `softDeletes()`
- [ ] `media` table has NO `softDeletes()` (intentional — see `DATABASE_SCHEMA.md`)

---

## Phase 2 — Models & Relationships

**Status:** 🔴 Not Started

### Goal
All Eloquent models created with correct relationships, casts, fillable arrays, soft deletes, and Enum casts. Models are clean data-access objects — zero business logic.

### Why This Phase Exists
Models are the foundation of the data layer. Every Service depends on correctly defined relationships and casts. Getting them right here prevents bugs and N+1 issues in every subsequent phase.

### Dependencies
- Phase 1 complete (all tables exist)
- Phase 0 complete (all Enums defined)

### Modules Involved
- `app/Models/`

### Database Impact
None. Models only.

### Models to Create/Modify

**User** (modify existing):
```php
use HasApiTokens, HasFactory, Notifiable, SoftDeletes;
protected $casts = [
    'role'           => UserRole::class,
    'status'         => UserStatus::class,
    'email_verified_at' => 'datetime',
    'last_login_at'  => 'datetime',
    'password'       => 'hashed',
];
// Relationships:
public function listings(): HasMany
public function bookings(): HasMany   // as customer
public function reviews(): HasMany    // as reviewer
```

**Listing:**
```php
use HasFactory, SoftDeletes;
protected $casts = [
    'type'          => ListingType::class,
    'property_type' => PropertyType::class,
    'status'        => ListingStatus::class,
    'is_instant_bookable' => 'boolean',
    // money fields — always integer, never float
    'base_price_cents'        => 'integer',
    'cleaning_fee_cents'      => 'integer',
    'extra_guest_fee_cents'   => 'integer',
];
// Relationships:
public function owner(): BelongsTo          // → User
public function amenities(): BelongsToMany  // → Amenity (pivot: listing_amenity)
public function media(): HasMany            // → Media (morphic via entity_type/entity_id)
public function bookings(): HasMany
public function reviews(): HasMany
public function availabilityBlocks(): HasMany
// Scopes:
public function scopePublished(Builder $query): Builder
public function scopeOfType(Builder $query, ListingType $type): Builder
public function scopeInCity(Builder $query, string $city): Builder
```

**Booking:**
```php
use HasFactory, SoftDeletes;
protected $casts = [
    'status'         => BookingStatus::class,
    'payment_status' => PaymentStatus::class,
    'check_in_date'  => 'date',
    'check_out_date' => 'date',
    'total_amount_cents'  => 'integer',
    'platform_fee_cents'  => 'integer',
];
// Auto-generate booking_reference on creating:
protected static function booted(): void {
    static::creating(function (Booking $booking) {
        $booking->uuid = (string) Str::uuid();
        $booking->booking_reference = 'VS-' . strtoupper(Str::random(8));
    });
}
// Relationships:
public function listing(): BelongsTo
public function customer(): BelongsTo   // → User
public function payment(): HasOne
public function review(): HasOne
```

**Payment:**
```php
use HasFactory, SoftDeletes;
protected $casts = [
    'gateway'          => PaymentGateway::class,
    'status'           => PaymentStatus::class,
    'provider_response' => 'array',
    'metadata'         => 'array',
    'amount_cents'     => 'integer',
    'fee_cents'        => 'integer',
];
// Relationships:
public function booking(): BelongsTo
```

**Review:**
```php
use SoftDeletes;
protected $casts = [
    'status' => ReviewStatus::class,
    'owner_reply_at' => 'datetime',
];
// Relationships:
public function booking(): BelongsTo
public function reviewer(): BelongsTo   // → User
public function listing(): BelongsTo
```

**Media:**
```php
// NO soft deletes — physical deletion required
protected $casts = [
    'type'       => MediaType::class,
    'provider'   => MediaProvider::class,
    'is_primary' => 'boolean',
    'order'      => 'integer',
];
// Polymorphic:
public function entity(): MorphTo  // resolves to Listing or User
```

**AvailabilityBlock:**
```php
protected $casts = [
    'start_date' => 'date',
    'end_date'   => 'date',
];
public function listing(): BelongsTo
public function blockedBy(): BelongsTo  // → User
```

**Amenity:**
```php
public function listings(): BelongsToMany
```

**PlatformSetting:**
```php
protected $casts = [
    'type' => SettingType::class,
];
// Static helper — the ONLY way to read settings:
public static function get(string $key, mixed $default = null): mixed
public static function set(string $key, mixed $value): void
```

### API Impact
None. Models only.

### Services Involved
None. Models only.

### Testing Strategy
```bash
php artisan test --filter=Model
```
Write `tests/Unit/Models/` tests:
- `UserModelTest` — role/status casts, relationships resolve
- `BookingModelTest` — uuid+reference auto-generated on create, casts, relationships
- `ListingModelTest` — scopes work (published, inCity, ofType)
- `PlatformSettingModelTest` — `get()` / `set()` static helper

### Exit Criteria
- [ ] All models have `declare(strict_types=1)`
- [ ] All money fields cast to `integer` — no float anywhere
- [ ] `Booking::creating()` auto-generates `uuid` and `booking_reference`
- [ ] `PlatformSetting::get()` static method works
- [ ] All model unit tests pass
- [ ] No business logic in any model (scopes are OK, business methods are not)

---

## Phase 3 — Authentication

**Status:** 🔴 Not Started

### Goal
Full authentication: register, login, logout, get current user, email verification, and social auth stubs (Google/Apple). Tokens issued via Laravel Sanctum.

### Why This Phase Exists
Authentication is the gate to every feature. Every subsequent phase requires knowing who the user is, what their role is, and whether they are verified.

### Dependencies
- Phase 2 complete (User model with casts and relationships)
- Sanctum installed: `composer require laravel/sanctum && php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"`

### Modules Involved
- `app/Http/Controllers/Api/Auth/AuthController.php`
- `app/Services/Auth/AuthService.php`
- `app/DTOs/Auth/RegisterDTO.php`
- `app/DTOs/Auth/LoginDTO.php`
- `app/Http/Requests/Auth/RegisterRequest.php`
- `app/Http/Requests/Auth/LoginRequest.php`
- `app/Http/Resources/Auth/AuthUserResource.php`

### Database Impact
None. Uses existing `users` table and Sanctum's `personal_access_tokens` table (auto-created by Sanctum).

### API Endpoints
```
POST /api/v1/auth/register           → Register new user
POST /api/v1/auth/login              → Login, returns token
POST /api/v1/auth/logout             → Revoke current token (auth required)
GET  /api/v1/auth/me                 → Get current user profile (auth required)
POST /api/v1/auth/google             → Social login via Google token
POST /api/v1/auth/apple              → Social login via Apple token
GET  /api/v1/auth/email/verify/{id}/{hash}  → Email verification link
POST /api/v1/auth/email/resend       → Resend verification email (auth required)
```

### Services
**AuthService:**
```php
public function register(RegisterDTO $dto): array  // returns ['user' => User, 'token' => string]
public function login(LoginDTO $dto): array         // returns ['user' => User, 'token' => string]
public function logout(User $user): void
public function findOrCreateSocialUser(string $provider, string $providerId, string $email, string $name): User
```
- Never receives a `Request` object — only DTOs
- On register: creates user, sends verification email, generates Sanctum token
- On login: validates credentials, updates `last_login_at`, generates token
- On social auth: finds existing user by `provider_id` OR creates new one

### DTOs
**RegisterDTO:**
```php
readonly class RegisterDTO {
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public UserRole $role,  // only 'customer' or 'provider' allowed on self-register
    ) {}
    public static function fromRequest(RegisterRequest $request): self
}
```
**LoginDTO:**
```php
readonly class LoginDTO {
    public function __construct(
        public string $email,
        public string $password,
    ) {}
    public static function fromRequest(LoginRequest $request): self
}
```

### Form Requests
**RegisterRequest — validation rules:**
```
name     → required|string|max:255
email    → required|email|unique:users,email
password → required|string|min:8|confirmed
role     → required|in:customer,provider  (admin cannot self-register)
```
**LoginRequest — validation rules:**
```
email    → required|email
password → required|string
```

### Policies
None — auth endpoints are public or use `auth:sanctum` middleware only.

### Resources
**AuthUserResource:**
```php
// Returns: id (uuid), name, email, role, status, email_verified_at, avatar_url
// NEVER returns: password, remember_token, provider_id, internal IDs
```

### External Providers
- Google OAuth2: Verify `id_token` via `https://oauth2.googleapis.com/tokeninfo`
- Apple Sign-In: Parse and verify JWT (use `lcobucci/jwt` or similar)
- Both are HTTP calls — no SDKs imported into `AuthService`

### Testing Strategy
```bash
php artisan test --filter=Auth
```

**Feature Tests (`tests/Feature/Auth/AuthTest.php`):**
```php
it('registers a new customer successfully', fn() => ...);         // 201
it('cannot register with duplicate email', fn() => ...);          // 422
it('cannot self-register as admin', fn() => ...);                 // 422
it('logs in with correct credentials', fn() => ...);              // 200 + token
it('returns 401 with wrong password', fn() => ...);               // 401
it('returns current user on GET /me', fn() => ...);               // 200
it('returns 401 on GET /me without token', fn() => ...);          // 401
it('logout revokes token', fn() => ...);                          // 200, then 401 on next req
it('social auth creates user if not exists', fn() => ...);        // 200 (mocked HTTP)
it('social auth returns existing user', fn() => ...);             // 200 (mocked HTTP)
```

### Exit Criteria
- [ ] All auth endpoints return unified API response format
- [ ] `password` never appears in any API response
- [ ] Admin role cannot be self-registered (returns 422)
- [ ] Token is revoked after logout (subsequent requests return 401)
- [ ] `last_login_at` updated on every successful login
- [ ] Email verification email sent on register (use `Mail::fake()` in tests)
- [ ] All feature tests pass

---

## Phase 4 — Listings Management

**Status:** 🔴 Not Started

### Goal
Providers can create and manage listings. Admin approves or rejects them. Public search is available. All listing state transitions enforced in the Service layer.

### Why This Phase Exists
Listings are the core product unit. Nothing else (booking, media, reviews) makes sense without listings. The approval workflow must exist before any listing can go live.

### Dependencies
- Phase 3 complete (authentication + roles)
- Phase 2 complete (Listing model)

### Modules Involved
- `app/Http/Controllers/Api/Customer/ListingController.php` (public endpoints)
- `app/Http/Controllers/Api/Owner/ListingController.php` (owner endpoints)
- `app/Http/Controllers/Api/Admin/ListingController.php` (admin endpoints)
- `app/Services/Listing/ListingService.php`
- `app/DTOs/Listing/CreateListingDTO.php`
- `app/DTOs/Listing/UpdateListingDTO.php`
- `app/DTOs/Listing/SearchListingDTO.php`
- `app/Http/Requests/Listing/CreateListingRequest.php`
- `app/Http/Requests/Listing/UpdateListingRequest.php`
- `app/Http/Requests/Listing/SearchListingRequest.php`
- `app/Http/Resources/Listing/ListingResource.php`
- `app/Http/Resources/Listing/ListingListResource.php`
- `app/Policies/ListingPolicy.php`

### Database Impact
Uses existing `listings`, `amenities`, `listing_amenity` tables.

### API Endpoints
```
# Public (no auth)
GET    /api/v1/listings                      → Search & browse listings
GET    /api/v1/listings/{uuid}               → Listing detail

# Authenticated Provider (role: provider)
POST   /api/v1/owner/listings                → Create listing (status → pending)
PUT    /api/v1/owner/listings/{uuid}         → Update own listing
DELETE /api/v1/owner/listings/{uuid}         → Soft delete own listing
GET    /api/v1/owner/listings                → My listings

# Authenticated Admin (role: admin)
GET    /api/v1/admin/listings                → All listings (filterable by status)
POST   /api/v1/admin/listings/{uuid}/approve → Approve listing
POST   /api/v1/admin/listings/{uuid}/reject  → Reject listing
```

### Services
**ListingService:**
```php
public function search(SearchListingDTO $dto): LengthAwarePaginator
public function findByUuid(string $uuid): Listing         // throws NotFoundException
public function create(CreateListingDTO $dto, User $owner): Listing
public function update(Listing $listing, UpdateListingDTO $dto): Listing
public function approve(Listing $listing, User $admin): Listing   // DB::transaction
public function reject(Listing $listing, User $admin, string $reason): Listing
public function delete(Listing $listing): void            // soft delete
public function getOwnerListings(User $owner): LengthAwarePaginator
public function getAllForAdmin(string $status = null): LengthAwarePaginator
```

**Business Rules enforced in ListingService:**
- New listings always start as `ListingStatus::Pending`
- Only `pending` listings can be approved or rejected
- Only `published` listings appear in public search
- A rejected listing can be re-submitted (back to `pending`) by the owner
- Only admin can call `approve()` or `reject()`

### DTOs
**CreateListingDTO:**
```php
readonly class CreateListingDTO {
    public function __construct(
        public ListingType $type,
        public ?PropertyType $propertyType,
        public string $title,
        public string $description,
        public string $address,
        public string $country,
        public string $city,
        public float $latitude,
        public float $longitude,
        public int $basePriceCents,       // integer cents only
        public int $cleaningFeeCents,
        public int $extraGuestFeeCents,
        public int $maxGuests,
        public ?int $bedrooms,
        public ?float $bathrooms,
        public ?string $transmission,
        public ?string $fuelType,
        public array $amenityIds,
    ) {}
    public static function fromRequest(CreateListingRequest $request): self
}
```

**SearchListingDTO:**
```php
readonly class SearchListingDTO {
    public function __construct(
        public ?string $city,
        public ?ListingType $type,
        public ?PropertyType $propertyType,
        public ?string $checkIn,          // date string
        public ?string $checkOut,
        public ?int $guests,
        public ?int $minPriceCents,
        public ?int $maxPriceCents,
        public int $page = 1,
        public int $perPage = 20,
    ) {}
}
```

### Policies
**ListingPolicy:**
```php
public function create(User $user): bool          // role === provider
public function update(User $user, Listing $listing): bool   // owner || admin
public function delete(User $user, Listing $listing): bool   // owner || admin
public function approve(User $user): bool         // role === admin
public function reject(User $user): bool          // role === admin
public function viewAny(User $user): bool         // always true (public)
```

### Resources
**ListingResource (detail view):**
```php
// Returns: uuid, type, property_type, title, description, address, city, country,
//          lat, lng, base_price_cents, cleaning_fee_cents, extra_guest_fee_cents,
//          max_guests, bedrooms, bathrooms, status, is_instant_bookable,
//          amenities[], media[], owner (name, uuid), average_rating, reviews_count
```
**ListingListResource (list/search view):**
```php
// Returns: uuid, type, title, city, base_price_cents, is_instant_bookable,
//          primary_image_url, average_rating, reviews_count
// Never eager-load full media or amenities in list view
```

### External Providers
None in this phase.

### Testing Strategy
```bash
php artisan test --filter=Listing
```

**Feature Tests (`tests/Feature/Listing/ListingTest.php`):**
```php
it('provider can create a listing with status pending', fn() => ...);         // 201
it('customer cannot create a listing', fn() => ...);                          // 403
it('admin approves a pending listing', fn() => ...);                          // 200
it('admin rejects a listing with reason', fn() => ...);                       // 200
it('published listings appear in public search', fn() => ...);                // 200
it('pending listings do not appear in public search', fn() => ...);           // not in response
it('owner can update own listing', fn() => ...);                              // 200
it('owner cannot update another owner\'s listing', fn() => ...);             // 403
it('unauthenticated user can view published listing detail', fn() => ...);    // 200
it('search filters by city correctly', fn() => ...);                          // 200
it('search paginates results', fn() => ...);                                  // meta.pagination present
```

### Exit Criteria
- [ ] New listings always start with `status = pending`
- [ ] Public search only returns `published` listings
- [ ] Admin-only endpoints return 403 for non-admins
- [ ] Owner endpoints return 403 for other providers
- [ ] `ListingService` has no Policy/Auth checks (those belong in Controllers/Policies)
- [ ] List endpoints eager-load only necessary relations (no N+1)
- [ ] All feature tests pass

---

## Phase 5 — Availability & Booking

**Status:** 🔴 Not Started

### Goal
Customers can check availability and book published listings. Booking flow is atomic and race-condition safe. Pricing is calculated transparently in integer cents. Bookings have unique human-readable references.

### Why This Phase Exists
Booking is the core business transaction. Getting it wrong — especially around double-bookings and money calculations — is catastrophic. This phase establishes the booking engine as a hardened, tested unit.

### Dependencies
- Phase 4 complete (listings exist and are published)
- Phase 3 complete (authenticated customers)
- Phase 2 complete (Booking model with auto-generated reference)

### Modules Involved
- `app/Http/Controllers/Api/Customer/BookingController.php`
- `app/Http/Controllers/Api/Owner/BookingController.php`
- `app/Http/Controllers/Api/Owner/AvailabilityController.php`
- `app/Http/Controllers/Api/Admin/BookingController.php`
- `app/Services/Booking/BookingService.php`
- `app/Services/Booking/AvailabilityService.php`
- `app/Services/Booking/PricingService.php`
- `app/DTOs/Booking/CreateBookingDTO.php`
- `app/DTOs/Booking/CancelBookingDTO.php`
- `app/DTOs/Booking/PricingResultDTO.php`
- `app/Http/Requests/Booking/CreateBookingRequest.php`
- `app/Http/Resources/Booking/BookingResource.php`
- `app/Policies/BookingPolicy.php`
- `app/Exceptions/BookingConflictException.php`

### Database Impact
Uses existing `bookings` and `availability_blocks` tables.

### API Endpoints
```
# Customer
POST /api/v1/bookings                            → Create booking
GET  /api/v1/bookings                            → My bookings (paginated)
GET  /api/v1/bookings/{uuid}                     → Booking detail
POST /api/v1/bookings/{uuid}/cancel              → Cancel booking
GET  /api/v1/listings/{uuid}/availability        → Get blocked dates for a listing

# Owner
GET    /api/v1/owner/bookings                    → Bookings on my listings
POST   /api/v1/owner/availability/block          → Block dates manually
DELETE /api/v1/owner/availability/{id}           → Unblock dates

# Admin
GET /api/v1/admin/bookings                       → All bookings (filterable)
PUT /api/v1/admin/bookings/{uuid}/status         → Override booking status
```

### Services

**AvailabilityService:**
```php
public function isAvailable(Listing $listing, string $checkIn, string $checkOut): bool
public function getBlockedDates(Listing $listing, string $month): array
public function blockDates(Listing $listing, User $owner, string $start, string $end, string $reason): AvailabilityBlock
public function unblockDates(AvailabilityBlock $block): void
```

**PricingService:**
```php
public function calculate(Listing $listing, string $checkIn, string $checkOut, int $guests): PricingResultDTO
// Returns: nights, base_total_cents, cleaning_fee_cents, extra_guest_fee_cents,
//          platform_fee_cents, grand_total_cents
// Platform fee read from: PlatformSetting::get('platform_fee_percentage', 10)
// ALL values in integer cents. No floats anywhere.
```

**BookingService:**
```php
public function createBooking(CreateBookingDTO $dto, User $customer): Booking
public function cancelBooking(Booking $booking, User $requester, string $reason): Booking
public function confirmBooking(Booking $booking): Booking    // called by PaymentService
public function completeBooking(Booking $booking): Booking
public function findByUuid(string $uuid): Booking
```

### Critical: Booking Creation (Must be atomic)
```
1. Validate check_in is in the future
2. Validate check_out > check_in
3. Find listing by uuid — must be published
4. Calculate pricing (PricingService)

BEGIN DB::transaction()
5. SELECT listing row FOR UPDATE (prevents race condition)
6. Call AvailabilityService::isAvailable() inside the transaction
7. If NOT available → throw BookingConflictException (409)
8. Create Booking record (status: pending, payment_status: pending)
9. COMMIT

10. Return Booking with pricing breakdown
11. Payment is a separate step (Phase 6)
```

**Why `FOR UPDATE`?** Without it, two concurrent requests could both pass the availability check, then both create bookings — resulting in a double booking. `SELECT ... FOR UPDATE` locks the rows until the transaction commits.

### DTOs
**CreateBookingDTO:**
```php
readonly class CreateBookingDTO {
    public function __construct(
        public string $listingUuid,
        public string $checkInDate,
        public string $checkOutDate,
        public int $guestsCount,
        public ?string $notes,
    ) {}
    public static function fromRequest(CreateBookingRequest $request): self
}
```
**PricingResultDTO:**
```php
readonly class PricingResultDTO {
    public function __construct(
        public int $nights,
        public int $baseTotalCents,
        public int $cleaningFeeCents,
        public int $extraGuestFeeCents,
        public int $platformFeeCents,
        public int $grandTotalCents,
    ) {}
}
```

### Policies
**BookingPolicy:**
```php
public function view(User $user, Booking $booking): bool     // customer owns it OR listing owner OR admin
public function cancel(User $user, Booking $booking): bool   // customer owns it OR admin
public function create(User $user): bool                     // role === customer
```

### Resources
**BookingResource:**
```php
// Returns: uuid, booking_reference, status, payment_status, check_in_date,
//          check_out_date, guests_count, nights, total_amount_cents,
//          platform_fee_cents, currency, notes, listing (uuid, title, city),
//          pricing_breakdown (base, cleaning, extra_guest, platform_fee, total)
// NEVER expose: internal id, customer_id, listing_id
```

### Testing Strategy
```bash
php artisan test --filter=Booking
```

**Feature Tests (`tests/Feature/Booking/BookingTest.php`):**
```php
it('customer can book available listing', fn() => ...);                     // 201
it('returns 409 when dates are already booked', fn() => ...);               // 409
it('returns 422 when check_in is in the past', fn() => ...);               // 422
it('returns 422 when check_out is before check_in', fn() => ...);          // 422
it('booking reference format is VS-XXXXXXXX', fn() => ...);                // regex check
it('customer can cancel own booking', fn() => ...);                         // 200
it('non-owner cannot cancel booking', fn() => ...);                         // 403
it('provider cannot create a booking', fn() => ...);                        // 403
it('availability endpoint returns blocked dates', fn() => ...);             // 200

// Unit Tests (tests/Unit/Services/PricingServiceTest.php):
it('calculates correct total for 3-night stay', fn() => ...);
it('adds platform fee from platform_settings', fn() => ...);
it('platform fee is always integer cents', fn() => ...);
it('extra guest fee applied when guests exceed base', fn() => ...);
```

**Concurrency Test (`tests/Feature/Booking/ConcurrencyTest.php`):**
```php
it('prevents double booking under concurrent requests', function () {
    // Create a listing
    // Send 2 booking requests for same dates simultaneously
    // Assert only 1 booking created, other gets 409
});
```

### Exit Criteria
- [ ] `BookingService::createBooking()` uses `DB::transaction()` with `FOR UPDATE`
- [ ] Concurrent bookings for same dates — only one succeeds
- [ ] Booking reference matches format `VS-[A-Z0-9]{8}`
- [ ] All money values in response are integers (cents)
- [ ] `PlatformSetting::get('platform_fee_percentage')` used for fee — never hardcoded
- [ ] Listing must be `published` to be booked (returns 422 if not)
- [ ] All feature and unit tests pass

---

## Phase 6 — Payment Integration

**Status:** 🔴 Not Started

### Goal
Payments processed through `PaymentGatewayInterface`. `NullPaymentAdapter` used for tests. `PaymobAdapter` as first real adapter. Webhook handling is signature-verified and idempotent.

### Why This Phase Exists
Payment completes the booking loop. Without it, bookings stay in `pending` forever. The interface pattern here ensures we can swap Paymob for Stripe or any other gateway without touching business logic.

### Dependencies
- Phase 5 complete (bookings can be created in `pending` status)
- Phase 0 complete (`PaymentGatewayInterface` and `NullPaymentAdapter` exist)

### Modules Involved
- `app/Http/Controllers/Api/Customer/PaymentController.php`
- `app/Http/Controllers/Api/Webhook/PaymobWebhookController.php`
- `app/Services/Payment/PaymentService.php`
- `app/Services/Payment/Adapters/PaymobAdapter.php`
- `app/DTOs/Payment/InitiatePaymentDTO.php`
- `app/Http/Resources/Payment/PaymentResource.php`
- `config/payment.php`

### Database Impact
Uses existing `payments` table.

### API Endpoints
```
# Authenticated Customer
POST /api/v1/payments                    → Initiate payment for a booking
GET  /api/v1/payments/{uuid}             → Payment status
GET  /api/v1/payments/history            → My payment history (paginated)

# Webhooks (NO auth middleware — use signature verification)
POST /api/v1/webhooks/paymob             → Paymob callback
POST /api/v1/webhooks/revenuecat         → RevenueCat callback (stub for now)
```

### Services
**PaymentService:**
```php
public function __construct(private PaymentGatewayInterface $gateway) {}

public function initiatePayment(InitiatePaymentDTO $dto, User $customer): array
// Returns: ['payment' => Payment, 'checkout_url' => string]

public function handleWebhook(string $gateway, array $payload, string $signature): void
// Must be idempotent — same transaction ID = no double processing

public function processRefund(Payment $payment): Payment
```

### Critical: Payment + Booking Flow (Must be atomic)
```
INITIATE:
1. Find booking by uuid — must be pending + owned by customer
2. Calculate amount = booking.total_amount_cents
3. Create Payment record (status: pending, gateway: paymob)
4. Call gateway->charge() → get checkout_url
5. Return checkout_url to customer

WEBHOOK (on success from gateway):
BEGIN DB::transaction()
1. Verify HMAC signature → if invalid, return 400 immediately
2. Find Payment by gateway_transaction_id
3. If already processed (status != pending) → return 200 idempotently
4. Update Payment status → succeeded
5. Update Booking payment_status → paid
6. Update Booking status → confirmed
COMMIT

WEBHOOK (on failure):
1. Update Payment status → failed
2. Booking stays pending (customer can retry)
```

**Why idempotency matters?** Paymob (and all gateways) can send the same webhook multiple times. Without the idempotency check, a customer could get charged once but their booking confirmed twice, or wallet credited twice.

### DTOs
**InitiatePaymentDTO:**
```php
readonly class InitiatePaymentDTO {
    public function __construct(
        public string $bookingUuid,
        public PaymentGateway $gateway,
    ) {}
}
```

### Policies
**PaymentPolicy:**
```php
public function create(User $user, Booking $booking): bool  // booking.customer_id === user.id
public function view(User $user, Payment $payment): bool    // booking owner OR admin
```

### Resources
**PaymentResource:**
```php
// Returns: uuid, booking_uuid, amount_cents, gateway, status, payment_method, created_at
// NEVER expose: provider_response, metadata (internal), gateway_transaction_id
```

### External Providers
**PaymobAdapter** (implements `PaymentGatewayInterface`):
```php
// Credentials from config('payment.paymob') — NEVER hardcoded
// Methods: charge(), refund(), verifyWebhook()
// verifyWebhook() → HMAC-SHA512 of concatenated fields
// Install: no official SDK — HTTP calls via Http::post()
```

**Config (`config/payment.php`):**
```php
return [
    'default' => env('PAYMENT_GATEWAY', 'null'),
    'paymob' => [
        'api_key'        => env('PAYMOB_API_KEY'),
        'integration_id' => env('PAYMOB_INTEGRATION_ID'),
        'hmac_secret'    => env('PAYMOB_HMAC_SECRET'),
    ],
];
```

**AppServiceProvider — switch based on config:**
```php
$gateway = config('payment.default');
$this->app->bind(PaymentGatewayInterface::class, match($gateway) {
    'paymob' => PaymobAdapter::class,
    default  => NullPaymentAdapter::class,
});
```

### Testing Strategy
```bash
php artisan test --filter=Payment
```

All payment tests use `NullPaymentAdapter` — never real API:

**Feature Tests (`tests/Feature/Payment/PaymentTest.php`):**
```php
it('customer can initiate payment for own pending booking', fn() => ...);     // 200
it('customer cannot initiate payment for someone else\'s booking', fn() => ...); // 403
it('successful webhook marks booking as confirmed', fn() => ...);             // 200
it('failed webhook leaves booking as pending', fn() => ...);                  // 200
it('webhook with invalid signature returns 400', fn() => ...);                // 400
it('duplicate webhook is idempotent', fn() => ...);                           // 200, no double-process
it('payment history is paginated', fn() => ...);                              // meta.pagination
```

### Exit Criteria
- [ ] `PaymentService` has zero Paymob SDK imports (uses interface only)
- [ ] HMAC signature verification on every webhook (invalid → 400, no processing)
- [ ] Duplicate webhook → idempotent (no double booking confirmation)
- [ ] `config('payment.default')` controls which adapter is used — not hardcoded
- [ ] Webhook routes excluded from `auth:sanctum` middleware
- [ ] All tests pass using `NullPaymentAdapter`

---

## Phase 7 — Media Uploads

**Status:** 🔴 Not Started

### Goal
Providers can upload images for their listings. Users can upload avatars. All uploads go through `MediaStorageInterface`. Cloudinary is the real adapter. Order and primary image are manageable.

### Why This Phase Exists
Listings without photos don't convert. This phase enables the visual layer of the marketplace. The interface pattern ensures Cloudinary can be swapped for S3 or R2 without touching business logic.

### Dependencies
- Phase 4 complete (listings exist and can be owned)
- Phase 3 complete (authenticated users)
- Phase 0 complete (`MediaStorageInterface` and `LocalMediaAdapter` exist)

### Modules Involved
- `app/Http/Controllers/Api/Owner/MediaController.php`
- `app/Services/Media/MediaService.php`
- `app/Services/Media/Adapters/CloudinaryAdapter.php`
- `app/DTOs/Media/UploadMediaDTO.php`
- `app/Http/Resources/Media/MediaResource.php`
- `app/Policies/MediaPolicy.php`
- `config/media.php`

### Database Impact
Uses existing `media` table.

### API Endpoints
```
# Authenticated Provider (own listings only)
POST   /api/v1/owner/listings/{uuid}/media        → Upload image to listing
DELETE /api/v1/media/{uuid}                       → Delete media item
PUT    /api/v1/owner/listings/{uuid}/media/reorder → Reorder images
PUT    /api/v1/media/{uuid}/primary               → Set as primary image

# Authenticated User (own avatar)
POST /api/v1/users/me/avatar                      → Upload avatar
```

### Services
**MediaService:**
```php
public function __construct(private MediaStorageInterface $storage) {}

public function uploadListingImage(Listing $listing, UploadedFile $file): Media
public function uploadUserAvatar(User $user, UploadedFile $file): User
public function deleteMedia(Media $media): void
public function reorderListingMedia(Listing $listing, array $orderedUuids): void
public function setPrimary(Media $media): void
```

**Business Rules enforced in MediaService:**
- Max 20 images per listing (read from `PlatformSetting::get('max_photos_per_listing', 20)`)
- Only one image can be `is_primary = true` per listing
- Setting a new primary clears the old one in the same DB transaction
- On delete: first delete from provider (Cloudinary), then delete DB record
- On upload failure from provider: no DB record created

### DTOs
**UploadMediaDTO:**
```php
readonly class UploadMediaDTO {
    public function __construct(
        public string $entityType,  // 'listing' | 'user'
        public int $entityId,
        public string $folder,      // e.g. 'listings/uuid' or 'avatars'
    ) {}
}
```

### Policies
**MediaPolicy:**
```php
public function upload(User $user, Listing $listing): bool  // listing.owner_id === user.id
public function delete(User $user, Media $media): bool      // entity owner OR admin
public function reorder(User $user, Listing $listing): bool // listing.owner_id === user.id
```

### Resources
**MediaResource:**
```php
// Returns: uuid, url, type, is_primary, order, provider
// NEVER expose: public_id (Cloudinary internal), entity_id (internal int)
```

### External Providers
**CloudinaryAdapter** (implements `MediaStorageInterface`):
```php
// Install: composer require cloudinary-labs/cloudinary-laravel
// Credentials from config('media.cloudinary') — NEVER hardcoded
// upload() → uploads file, returns ['url' => string, 'public_id' => string]
// delete() → calls Cloudinary API with public_id
// CloudinaryAdapter has zero imports in MediaService
```

**Config (`config/media.php`):**
```php
return [
    'default' => env('MEDIA_PROVIDER', 'local'),
    'cloudinary' => [
        'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
        'api_key'    => env('CLOUDINARY_API_KEY'),
        'api_secret' => env('CLOUDINARY_API_SECRET'),
    ],
];
```

### Testing Strategy
```bash
php artisan test --filter=Media
```

All tests use `LocalMediaAdapter` — never real Cloudinary:

**Feature Tests (`tests/Feature/Media/MediaTest.php`):**
```php
it('provider can upload image to own listing', fn() => ...);              // 201
it('provider cannot upload to another\'s listing', fn() => ...);          // 403
it('upload fails gracefully when provider errors', fn() => ...);          // 500 / no DB record
it('delete removes media record and calls provider', fn() => ...);        // 200
it('reorder updates order field correctly', fn() => ...);                 // 200
it('setting primary clears previous primary', fn() => ...);               // 200
it('cannot upload more than max_photos_per_listing', fn() => ...);        // 422
```

### Exit Criteria
- [ ] `MediaService` has zero Cloudinary SDK imports (uses interface only)
- [ ] Provider deletion called before DB record deleted (if provider fails, abort)
- [ ] `max_photos_per_listing` read from `platform_settings` — not hardcoded
- [ ] Only one `is_primary = true` per listing at any time
- [ ] `public_id` never exposed in API responses
- [ ] All tests pass using `LocalMediaAdapter`

---

## Phase 8 — Reviews

**Status:** 🔴 Not Started

### Goal
Customers can review listings after a completed stay. One review per booking. Admin can moderate. Owner can reply once.

### Why This Phase Exists
Reviews are the primary trust mechanism. Without them, customers have no way to evaluate listings. The verification gate (completed booking only) ensures authenticity.

### Dependencies
- Phase 5 complete (bookings with `completed` status exist)
- Phase 3 complete (authenticated customers)

### Modules Involved
- `app/Http/Controllers/Api/Customer/ReviewController.php`
- `app/Http/Controllers/Api/Owner/ReviewController.php`
- `app/Http/Controllers/Api/Admin/ReviewController.php`
- `app/Services/Listing/ReviewService.php`
- `app/DTOs/Review/CreateReviewDTO.php`
- `app/Http/Requests/Review/CreateReviewRequest.php`
- `app/Http/Resources/Review/ReviewResource.php`
- `app/Policies/ReviewPolicy.php`

### Database Impact
Uses existing `reviews` table.

### API Endpoints
```
# Customer
POST /api/v1/reviews                            → Create review (completed booking required)

# Public
GET  /api/v1/listings/{uuid}/reviews            → Paginated approved reviews for listing

# Owner
POST /api/v1/reviews/{uuid}/reply               → Reply to review on own listing (once only)

# Admin
GET  /api/v1/admin/reviews                      → All reviews (filterable by status)
PUT  /api/v1/admin/reviews/{uuid}/moderate      → Approve or hide review
```

### Services
**ReviewService:**
```php
public function createReview(CreateReviewDTO $dto, User $customer): Review
// Enforces: booking.status === completed
// Enforces: no existing review for this booking (one per booking)
// Enforces: reviewer === booking.customer

public function ownerReply(Review $review, string $reply, User $owner): Review
// Enforces: owner of listing === user
// Enforces: reply not already set (one reply only)

public function moderate(Review $review, ReviewStatus $status, User $admin): Review
public function getListingReviews(Listing $listing): LengthAwarePaginator  // approved only
public function getAverageRating(Listing $listing): float
```

### DTOs
**CreateReviewDTO:**
```php
readonly class CreateReviewDTO {
    public function __construct(
        public string $bookingUuid,
        public int $rating,          // 1–5
        public ?string $comment,
    ) {}
}
```

### Policies
**ReviewPolicy:**
```php
public function create(User $user, Booking $booking): bool
// booking.customer_id === user.id AND booking.status === completed

public function reply(User $user, Review $review): bool
// review.listing.owner_id === user.id

public function moderate(User $user): bool   // role === admin
```

### Resources
**ReviewResource:**
```php
// Returns: uuid, rating, comment, owner_reply, owner_reply_at,
//          reviewer (name, avatar_url), created_at
// Moderation status NOT shown to public (admin only)
```

### External Providers
None.

### Testing Strategy
```bash
php artisan test --filter=Review
```

**Feature Tests (`tests/Feature/Review/ReviewTest.php`):**
```php
it('customer can review completed booking', fn() => ...);                   // 201
it('cannot review non-completed booking', fn() => ...);                     // 422
it('cannot leave two reviews for same booking', fn() => ...);               // 422
it('non-booking-owner cannot review', fn() => ...);                         // 403
it('owner can reply to review on own listing', fn() => ...);                // 200
it('owner cannot reply twice', fn() => ...);                                // 422
it('admin can approve and hide reviews', fn() => ...);                      // 200
it('hidden reviews not returned in public listing', fn() => ...);           // not in response
it('average rating is calculated correctly', fn() => ...);                  // numeric check
```

### Exit Criteria
- [ ] Only customers with `booking.status === completed` can leave reviews
- [ ] One review per booking enforced (422 on duplicate)
- [ ] Owner reply is immutable after set (422 on second attempt)
- [ ] Public listing reviews endpoint returns only `approved` reviews
- [ ] Average rating computed from approved reviews only
- [ ] All feature tests pass

---

## Phase 9 — Notifications

**Status:** 🔴 Not Started

### Goal
Key platform events trigger push (Expo) and email notifications. All notifications are dispatched as queued jobs. Notification failures never affect business operations.

### Why This Phase Exists
Notifications drive re-engagement and keep users informed. Without them, users miss bookings, confirmations, and reminders. The isolation rule (queued, never synchronous) ensures notification bugs don't break booking or payment flows.

### Dependencies
- Phase 5 complete (bookings trigger notification events)
- Phase 6 complete (payment confirms bookings)
- Phase 0 complete (`PushNotificationInterface` and `NullNotificationAdapter` exist)

### Modules Involved
- `app/Services/Notification/NotificationService.php`
- `app/Services/Notification/Adapters/ExpoAdapter.php`
- `app/Jobs/SendPushNotification.php`
- `app/Mail/BookingConfirmedMail.php`
- `app/Mail/BookingCancelledMail.php`
- `app/Mail/CheckInReminderMail.php`
- `app/Console/Commands/SendCheckInReminders.php`
- `config/notification.php`

### Database Impact
None (no notification log table in MVP — logged to file only).

### API Endpoints
None. Notifications are triggered internally by other services.

### Services
**NotificationService:**
```php
public function __construct(private PushNotificationInterface $push) {}

public function notifyBookingConfirmed(Booking $booking): void
// Notifies: customer (push + email) + listing owner (push)

public function notifyBookingCancelled(Booking $booking): void
// Notifies: customer (push + email) + listing owner (push)

public function notifyNewBooking(Booking $booking): void
// Notifies: listing owner only (new booking received)

public function notifyCheckInReminder(Booking $booking): void
// Notifies: customer (push + email) — 24h before check-in
```

### Critical: Isolation Rule
```
NotificationService.notify*() MUST:
1. Persist any DB record (if applicable) synchronously
2. Dispatch a queued Job for push delivery
3. Dispatch a queued Mailable for email
4. Return immediately

The Job MUST:
1. Catch ALL exceptions from push provider
2. Log failures (Log::warning) — never rethrow
3. Never affect the booking/payment state

Notification bugs must NEVER surface as 500 errors to the customer.
```

### Integration with Other Services
- `PaymentService::handleWebhook()` → calls `NotificationService::notifyBookingConfirmed()`
- `BookingService::cancelBooking()` → calls `NotificationService::notifyBookingCancelled()`
- Scheduler → calls `NotificationService::notifyCheckInReminder()` via `SendCheckInReminders` command

### External Providers
**ExpoAdapter** (implements `PushNotificationInterface`):
```php
// Install: composer require ctwillie/expo-server-sdk-php
// Credentials: none needed for Expo (uses Expo push tokens from device)
// Never import ExpoAdapter in NotificationService — use interface
```

**Config (`config/notification.php`):**
```php
return [
    'default' => env('NOTIFICATION_PROVIDER', 'null'),
    'expo' => [
        'access_token' => env('EXPO_ACCESS_TOKEN'),
    ],
];
```

**Scheduler (`app/Console/Commands/SendCheckInReminders.php`):**
```php
// Registered in routes/console.php via Schedule
// Runs daily at 08:00
// Finds all bookings where check_in_date = tomorrow AND status = confirmed
// Dispatches SendCheckInReminderJob for each
```

### Testing Strategy
```bash
php artisan test --filter=Notification
```

**Unit Tests (`tests/Unit/Services/NotificationServiceTest.php`):**
```php
it('dispatches push job when booking is confirmed', function () {
    Queue::fake();
    // trigger notifyBookingConfirmed()
    Queue::assertPushed(SendPushNotification::class);
});

it('push failure does not throw exception to caller', function () {
    // NullAdapter configured to throw
    // notifyBookingConfirmed() should still return without throwing
    expect(fn() => $service->notifyBookingConfirmed($booking))->not->toThrow();
});

it('check-in reminder command dispatches jobs for tomorrow bookings', fn() => ...);
```

### Exit Criteria
- [ ] All notifications dispatched as queued Jobs (never synchronous)
- [ ] Push failure is caught and logged — never rethrown
- [ ] Scheduler command registered and runs without errors
- [ ] `NotificationService` has zero ExpoAdapter imports (uses interface only)
- [ ] Email notifications use Laravel `Mailable` classes
- [ ] `config('notification.default')` controls adapter — not hardcoded
- [ ] All unit tests pass

---

## Phase 10 — Admin & Owner Dashboard APIs

**Status:** 🔴 Not Started

### Goal
Complete REST API for the React dashboard. Admins can manage users, listings, bookings, reviews, and platform settings. Owners can view their business summary.

### Why This Phase Exists
The dashboard is the operational interface of the platform. Without it, admins cannot approve listings, manage disputes, or configure the platform. Owners cannot track their business.

### Dependencies
- All previous phases complete (all data exists)

### Modules Involved
- `app/Http/Controllers/Api/Admin/DashboardController.php`
- `app/Http/Controllers/Api/Admin/UserController.php`
- `app/Http/Controllers/Api/Admin/SettingsController.php`
- `app/Http/Controllers/Api/Owner/DashboardController.php`
- `app/Services/Admin/AdminDashboardService.php`
- `app/Services/Admin/PlatformSettingsService.php`
- `app/Http/Resources/Admin/DashboardStatsResource.php`
- `app/Http/Resources/Admin/UserAdminResource.php`

### Database Impact
None. Uses existing tables.

### API Endpoints
```
# Admin Dashboard
GET /api/v1/admin/dashboard/stats           → Aggregate stats

# Admin: Users
GET /api/v1/admin/users                     → All users (filterable by role, status)
PUT /api/v1/admin/users/{uuid}/status       → Suspend or activate user

# Admin: Listings (already in Phase 4, ensure consistency)
GET  /api/v1/admin/listings
POST /api/v1/admin/listings/{uuid}/approve
POST /api/v1/admin/listings/{uuid}/reject

# Admin: Bookings
GET /api/v1/admin/bookings                  → All bookings (filterable by status)

# Admin: Reviews
GET /api/v1/admin/reviews
PUT /api/v1/admin/reviews/{uuid}/moderate

# Admin: Platform Settings
GET /api/v1/admin/settings                  → All settings
PUT /api/v1/admin/settings/{key}            → Update single setting

# Owner Dashboard
GET /api/v1/owner/dashboard/stats           → My revenue, booking count summary
GET /api/v1/owner/listings                  → My listings
GET /api/v1/owner/bookings                  → Bookings on my listings
GET /api/v1/owner/reviews                   → Reviews on my listings
```

### Services
**AdminDashboardService:**
```php
public function getStats(): array
// Returns: total_users, total_listings (by status), total_bookings (by status),
//          total_revenue_cents, pending_approvals_count
// All aggregated via Eloquent — no raw SQL

public function updateUserStatus(User $user, UserStatus $status): User
```

**PlatformSettingsService:**
```php
public function all(): array
public function update(string $key, mixed $value): PlatformSetting
// Validates key exists before updating — no arbitrary key insertion
```

### Policies
```php
// All admin/* endpoints: role === admin middleware group
// All owner/* endpoints: role === provider middleware group
// Separate middleware applied at route group level — not in controllers
```

### Resources
**DashboardStatsResource:**
```php
// Returns aggregated counts, never raw model data
```

**UserAdminResource:**
```php
// Returns: uuid, name, email, role, status, created_at, last_login_at
// Adds: listings_count, bookings_count for context
// NEVER expose: password, remember_token, provider_id
```

### Testing Strategy
```bash
php artisan test --filter=Admin
php artisan test --filter=Owner
```

**Feature Tests:**
```php
it('admin can view dashboard stats', fn() => ...);                 // 200
it('non-admin cannot access admin routes', fn() => ...);           // 403
it('admin can suspend a user', fn() => ...);                       // 200
it('admin can update platform settings', fn() => ...);             // 200
it('owner can view own bookings only', fn() => ...);               // 200
it('owner cannot view other owners listings', fn() => ...);        // 403
```

### Exit Criteria
- [ ] All `/admin/*` endpoints return 403 for non-admins
- [ ] All `/owner/*` endpoints return 403 for non-providers
- [ ] Platform settings update validates key exists
- [ ] Dashboard stats use eager loading — no N+1
- [ ] All list endpoints paginated with `meta.pagination`
- [ ] All feature tests pass

---

## Phase 11 — Seeders, Factories & Demo Data

**Status:** 🔴 Not Started

### Goal
Realistic seed data for development and QA. All factories complete. Default platform settings seeded. Demo data enables immediate manual testing.

### Why This Phase Exists
Without good factories, tests become brittle and hard to write. Without seeders, developers waste time creating test data manually. This phase ensures a consistent, reproducible development environment.

### Dependencies
- All previous phases complete (all models and relationships defined)

### Modules Involved
- `database/factories/`
- `database/seeders/`

### Database Impact
Data inserted — no schema changes.

### Factories to Create

```
UserFactory          → states: customer(), provider(), admin(), suspended()
ListingFactory       → states: property(), car(), published(), pending(), rejected()
BookingFactory       → states: confirmed(), completed(), cancelled()
PaymentFactory       → states: succeeded(), failed(), refunded()
ReviewFactory        → states: approved(), hidden()
MediaFactory
AmenityFactory
AvailabilityBlockFactory
```

**Example — ListingFactory states:**
```php
public function published(): static {
    return $this->state(['status' => ListingStatus::Published]);
}
public function car(): static {
    return $this->state(['type' => ListingType::Car, 'property_type' => null]);
}
```

### Seeders
```
DatabaseSeeder
├── AdminUserSeeder          → 1 admin (admin@vistastay.com / password)
├── PlatformSettingsSeeder   → Default platform settings
├── AmenitySeeder            → Standard amenities (WiFi, Pool, Parking...)
└── DemoDataSeeder           → Sample providers, listings, bookings (dev only)
```

**PlatformSettingsSeeder — default values:**
```
platform_fee_percentage    = 10
cancellation_window_days   = 7
max_photos_per_listing     = 20
max_guests_default         = 10
```

**DemoDataSeeder (dev/staging only):**
```php
// Guard: if (app()->isProduction()) return;
// Creates: 3 providers, 10 listings (mix of property+car), 20 bookings, 15 reviews
```

### Testing Strategy
```bash
php artisan migrate:fresh --seed
php artisan test
```

All tests must pass on a freshly seeded database.

### Exit Criteria
- [ ] `php artisan migrate:fresh --seed` → zero errors
- [ ] Admin user seeded with known credentials
- [ ] All platform settings seeded with correct default values
- [ ] All factories have type-safe states using Enums
- [ ] `DemoDataSeeder` skips in production (`app()->isProduction()` guard)
- [ ] Full test suite passes after fresh seed

---

## Phase 12 — Security Hardening & Production Readiness

**Status:** 🔴 Not Started

### Goal
Harden the API for production. Rate limiting, CORS, security headers, final test coverage verification, and API documentation.

### Why This Phase Exists
Shipping without security hardening exposes the platform to abuse, brute force, and data leaks. This phase is the last gate before production.

### Dependencies
- All previous phases complete and tested

### Modules Involved
- `bootstrap/app.php` — middleware configuration
- `app/Http/Middleware/`
- `routes/api.php` — throttle groups

### Security Checklist

**Rate Limiting:**
```
Auth endpoints (login, register, social): 5 req/min per IP
API endpoints (general):                  60 req/min per user
Payment initiation:                       10 req/hour per user
Webhook endpoints:                        100 req/min (high volume expected)
```

**CORS:**
```php
// Configure for dashboard domain + mobile app
// Allowed origins: configured per environment in config/cors.php
```

**Security Headers Middleware:**
```
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
X-XSS-Protection: 1; mode=block
Strict-Transport-Security: max-age=31536000 (production only)
```

**Authorization Audit:**
- Every route must have either: `auth:sanctum` middleware OR explicit public justification
- Every controller method must call `$this->authorize()` or use Policy
- No endpoint should rely on "security by obscurity"

**Webhook Security:**
- All webhook controllers verify HMAC signature before processing
- Invalid signature → 400 immediately, no processing, no logging of payload

**Input Validation:**
- All Form Requests use `declare(strict_types=1)` and typed rules
- No raw `$request->input()` in controllers without prior validation

### Performance Checklist
- [ ] All list endpoints use eager loading (no N+1)
- [ ] All list endpoints paginate (default 20/page, max 50)
- [ ] Heavy jobs queued (notifications, media deletion)
- [ ] DB indexes verified against actual query patterns

### API Documentation
Generate a Postman collection covering all endpoints with:
- Example requests and responses
- Authentication setup
- Environment variables (base URL, token)

### Final Test Suite
```bash
php artisan migrate:fresh --seed
php artisan test --coverage
```

**Target:**
- Service layer: **>80% coverage**
- All API endpoints: ≥1 happy path + ≥1 sad path test

### Production .env Checklist
```
APP_ENV=production
APP_DEBUG=false
QUEUE_CONNECTION=database   # or Redis if available
CACHE_STORE=database        # or Redis if available
PAYMENT_GATEWAY=paymob      # switch from null
MEDIA_PROVIDER=cloudinary   # switch from local
NOTIFICATION_PROVIDER=expo  # switch from null
```

### Exit Criteria
- [ ] `APP_DEBUG=false` → no stack traces in any API response
- [ ] Rate limiting active on auth and payment endpoints
- [ ] CORS configured for correct origins
- [ ] Every route has documented authorization intent
- [ ] HMAC verification active on all webhooks
- [ ] `php artisan test --coverage` → Service layer >80%
- [ ] All tests pass on fresh seeded production-like environment

---

## Phase Summary Table

| Phase | Name | Status | Key Dependency |
|---|---|---|---|
| 0 | Foundation | 🔴 Not Started | None |
| 1 | Database Migrations | 🔴 Not Started | Phase 0 |
| 2 | Models & Relationships | 🔴 Not Started | Phase 1 |
| 3 | Authentication | 🔴 Not Started | Phase 2 |
| 4 | Listings Management | 🔴 Not Started | Phase 3 |
| 5 | Availability & Booking | 🔴 Not Started | Phase 4 |
| 6 | Payment Integration | 🔴 Not Started | Phase 5 |
| 7 | Media Uploads | 🔴 Not Started | Phase 4 |
| 8 | Reviews | 🔴 Not Started | Phase 5 |
| 9 | Notifications | 🔴 Not Started | Phase 6 |
| 10 | Admin & Owner APIs | 🔴 Not Started | All previous |
| 11 | Seeders & Factories | 🔴 Not Started | All previous |
| 12 | Security & Production | 🔴 Not Started | All previous |

---

## Ordering Rationale

| Phase | Why Here |
|---|---|
| 0 before everything | Interfaces, enums, and adapters are prerequisites for all other code |
| Migrations (1) before models (2) | Models need tables to exist |
| Models (2) before auth (3) | Auth depends on User model |
| Auth (3) before listings (4) | Listings need authenticated owners |
| Listings (4) before booking (5) | Can't book without listings |
| Booking (5) before payment (6) | Payments confirm bookings |
| Media (7) parallel to booking (5+) | Can start after listings (Phase 4) |
| Reviews (8) after booking (5) | Reviews require completed bookings |
| Notifications (9) after payment (6) | Booking confirmation triggers notification |
| Dashboard (10) last feature | Aggregates everything from all phases |
| Seeders (11) after all models | Factories need all models defined |
| Hardening (12) last | Security audit requires complete feature set |

---

## Implementation Constraints (Absolute Rules)

1. **Never skip a phase.** The order exists for architectural reasons.
2. **Never implement business features in Phase 0.**
3. **Update `PROJECT_STATUS.md` after every phase — before starting the next.**
4. **Update `DATABASE_SCHEMA.md` if any schema change is made.**
5. **Every migration must be backward-compatible.**
6. **Never move to Phase N+1 until all Exit Criteria of Phase N are met.**
7. **Never call real payment/media/notification APIs in tests — use null adapters.**
8. **All money calculations use integer cents. No floats. Ever.**
