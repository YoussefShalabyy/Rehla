# PROJECT_STATUS.md

**Project:** VistaStay Travel Marketplace MVP
**Version:** 1.0
**Last Updated:** July 1, 2026
**Maintained By:** Engineering Team / AI Agent

> This file is the live source of truth for project progress.
> Every AI agent **must** read this before starting any work.
> Every AI agent **must** update this after completing a phase or major task.

---

## Current Phase

> **Phase 0 — Foundation & Project Setup**
> Status: 🔴 Not Started

---

## Tech Stack

| Layer | Technology |
|---|---|
| Backend Framework | Laravel 13.x |
| Language | PHP 8.3 |
| Database | MySQL (SQLite for local dev) |
| Authentication | Laravel Sanctum |
| Authorization | Spatie Laravel Permission |
| Mobile App | React Native (Expo) |
| Dashboard | React (role-based single app) |
| Payment (MVP) | Paymob / RevenueCat (via interface) |
| Media (MVP) | Cloudinary (via interface) |
| Push Notifications | Expo Push (via interface) |
| Email | SMTP / Mailgun (via Laravel Mail) |
| Queue | Database queue (default) |
| Testing | Pest PHP |

---

## Phase Progress

| Phase | Name | Status | Completed At |
|---|---|---|---|
| 0 | Foundation & Project Setup | 🔴 Not Started | — |
| 1 | Database Migrations | 🔴 Not Started | — |
| 2 | Models & Relationships | 🔴 Not Started | — |
| 3 | Authentication | 🔴 Not Started | — |
| 4 | Listings Management | 🔴 Not Started | — |
| 5 | Availability & Booking | 🔴 Not Started | — |
| 6 | Payment Integration | 🔴 Not Started | — |
| 7 | Media Uploads | 🔴 Not Started | — |
| 8 | Reviews | 🔴 Not Started | — |
| 9 | Notifications | 🔴 Not Started | — |
| 10 | Admin & Owner APIs | 🔴 Not Started | — |
| 11 | Seeders & Factories | 🔴 Not Started | — |
| 12 | Final Hardening & Pre-Launch | 🔴 Not Started | — |

**Legend:** 🔴 Not Started &nbsp;|&nbsp; 🟡 In Progress &nbsp;|&nbsp; 🟢 Complete &nbsp;|&nbsp; 🔵 Blocked

---

## What's Done

### Documentation (Pre-Implementation)
- [x] `docs/PRD.md` — Product Requirements Document (v1.3 FINAL)
- [x] `docs/BUSINESS_RULES.md` — Business Rules (v1.0 FINAL)
- [x] `docs/DATABASE_SCHEMA.md` — Database Schema (v1.2 FINAL)
- [x] `docs/CODING_STANDARDS.md` — Coding Standards (v1.1 FINAL, fixed format)
- [x] `docs/AI_ENGINEERING_GUIDE.md` — AI Engineering Guide (v1.1 FINAL)
- [x] `docs/IMPLEMENTATION_ROADMAP.md` — Full implementation blueprint (NEW)
- [x] `PROJECT_STATUS.md` — This file (NEW)
- [x] `.agents/AGENTS.md` — AI agent operating rules (NEW)

### Infrastructure
- [x] Laravel 13.x project initialized
- [x] PHP 8.3 target configured
- [x] Default Laravel folder structure in place
- [x] Default migrations: users, cache, jobs (need modification per schema)
- [x] `.env` configured (needs DB credentials)

---

## What's Left (All Phases)

- [ ] Phase 0: Install packages (Sanctum, Spatie Permission, Pest), folder structure, enums, interfaces
- [ ] Phase 1: All database migrations per `DATABASE_SCHEMA.md`
- [ ] Phase 2: All Eloquent models with relationships and casts
- [ ] Phase 3: Full auth system (register, login, logout, social auth stubs, email verification)
- [ ] Phase 4: Listings CRUD + approval workflow + search
- [ ] Phase 5: Availability engine + booking flow (conflict-safe, transactional)
- [ ] Phase 6: Payment processing through `PaymentGatewayInterface` + webhooks
- [ ] Phase 7: Media uploads through `MediaStorageInterface` (Cloudinary)
- [ ] Phase 8: Reviews system (post-stay only)
- [ ] Phase 9: Notifications (push + email, queued)
- [ ] Phase 10: Admin + Owner API endpoints for dashboard
- [ ] Phase 11: Seeders, factories, demo data
- [ ] Phase 12: Security hardening, rate limiting, final test suite

---

## Known Issues / Blockers

| # | Issue | Severity | Status |
|---|---|---|---|
| 1 | `CODING_STANDARDS.md` was wrapped in a code block — **Fixed** | Low | ✅ Resolved |
| 2 | DB is currently SQLite (default); must switch to MySQL before Phase 1 | Medium | 🔴 Open |
| 3 | Sanctum not installed yet | High | 🔴 Open |
| 4 | Spatie Permission not installed yet | High | 🔴 Open |
| 5 | Pest PHP not installed yet | High | 🔴 Open |
| 6 | No `routes/api.php` file yet | High | 🔴 Open |

---

## Architecture Decisions Log

| Date | Decision | Reason |
|---|---|---|
| 2026-06-30 | Laravel Monolith (not microservices) | MVP simplicity, speed to market |
| 2026-06-30 | No Repository Pattern by default | Unnecessary abstraction for MVP |
| 2026-06-30 | All external providers behind interfaces | Easy swap, no business logic coupling |
| 2026-06-30 | Integer cents for all money | Prevent float precision bugs |
| 2026-06-30 | UUIDs as public identifiers | Security, no sequential ID guessing |
| 2026-06-30 | Soft deletes on all business entities | Preserve history, support disputes |
| 2026-06-30 | Instant Booking (no owner approval per booking) | Simpler UX, faster conversions |
| 2026-07-01 | API versioned under `/api/v1/` | Allows future `/api/v2/` without breakage |
| 2026-07-01 | Pest PHP for testing (not PHPUnit directly) | Better DX, cleaner test syntax |

---

## Environment Setup (For New Developer / Agent)

```bash
# 1. Clone the repo
git clone <repo-url>
cd Rehla

# 2. Install PHP dependencies
composer install

# 3. Copy environment file
cp .env.example .env

# 4. Generate application key
php artisan key:generate

# 5. Configure your DB in .env (MySQL recommended)
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=vistastay
# DB_USERNAME=root
# DB_PASSWORD=

# 6. Run migrations
php artisan migrate

# 7. Run tests
php artisan test

# 8. Start dev server
php artisan serve
```

---

## API Base URL

```
Local:   http://localhost:8000/api/v1
Staging: https://api-staging.vistastay.com/api/v1
Prod:    https://api.vistastay.com/api/v1
```

---

> **Rule for AI Agents:** After completing any phase, update the Phase Progress table above and move the Current Phase section to the next phase. Log any architecture decisions made during implementation in the Architecture Decisions Log.
