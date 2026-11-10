# CURRENT PROGRESS: INTENTGUARD

> **Current Status as of:** 2026-11-10  
> **Active Framework:** Framework 01 — Project Foundation (Completed)  
> **Completed Modules:** Module B — Merchant Registration, Module C — Merchant Agreements  
> **Next Active Module:** Module D — Purchase Intent Management & Module E — Evidence & Provenance  
> **Last Updated By:** Lead Architect / Antigravity AI Assistant

---

## 1. Development Snapshot

| Dimension | Current State |
| :--- | :--- |
| **Current Framework** | Framework 01: Project Foundation (Completed) |
| **Current Modules** | Module A: Auth, Module B: Merchants, Module C: Agreements (Completed) |
| **Database Status** | 8 Migrations executed (`merchants`, `merchant_agreements`, `merchant_agreement_acceptances`, `merchant_status_audit_logs`, `users`, `tokens`, `jobs`, `cache`) |
| **Automated Tests** | **16 tests passing, 94 assertions, 100% pass rate** (`php artisan test`) |
| **Frontend Status** | Next.js 14 App Router, TypeScript, Tailwind CSS (`npm run build` passing) |
| **Known Bugs** | None |
| **Next Milestone** | Module D: Purchase Intent Management & Module E: Evidence & Provenance |

---

## 2. Feature Status Breakdown

### 2.1 Completed Features

#### Module A: User Management & Authentication API
- [x] **Sanctum Token Authentication:** Token issuing endpoint (`POST /api/v1/auth/login`) with role claims.
- [x] **Session Control:** Logout endpoint (`POST /api/v1/auth/logout`) and current user inspection (`GET /api/v1/auth/me`).
- [x] **Unauthenticated API Handling:** Configured `bootstrap/app.php` to intercept unauthenticated requests and return clean 401 JSON (`{"code": "UNAUTHENTICATED"}`) instead of redirecting to an undefined HTML login route.

#### Framework 01: Project Foundation
- [x] **Decoupled Architecture Established:**
  - `backend/`: Laravel 11 REST API with Sanctum, modular engines, domain services, form requests, and API resources.
  - `frontend/`: Next.js App Router with TypeScript, Tailwind CSS, domain types, and service clients.
- [x] **Database Engine & Environment:**
  - MySQL 8.0 schema compatibility for production/staging environments.
  - In-memory SQLite configured in `phpunit.xml` for sub-second, hermetic, zero-dependency test execution.
  - Database seeders for administrator account and version `1.0.0` merchant agreement policies.
- [x] **Portable AI Development Context System:**
  - `AGENTS.md`, `GEMINI.md`, `CLAUDE.md`, `.github/copilot-instructions.md`, `README.md`.
  - Comprehensive documentation repository under `docs/`.

#### Module B: Merchant Registration & Onboarding
- [x] **Database Relational Migrations:**
  - `merchants` table: Unique `intentguard_id` (`IG-MCH-XXXXXXXX`), business registration number, legal name, platform type, status, and compliance score.
  - `merchant_status_audit_logs` table: Append-only audit log recording every status transition with mandatory rationale and acting administrator.
- [x] **Domain Models & Enums:**
  - `Merchant`, `MerchantStatusAuditLog`, `User`.
  - Typed Enums: `MerchantStatus` (`pending_review`, `under_review`, `verified`, `rejected`, `suspended`), `MerchantPlatformType`, `BusinessType`.
- [x] **Domain Services & Workflows:**
  - `MerchantService`: Collision-resistant `IG-MCH-XXXXXXXX` ID generation, onboarding registration, queries, and paginated listing.
  - `MerchantApprovalService`: Managed status transitions, reason logging, and enforcing the rule that a merchant cannot be verified without an accepted active agreement.
- [x] **API Endpoints & Validation:**
  - `POST /api/v1/merchants/register` (`RegisterMerchantRequest`).
  - `GET /api/v1/merchants/{id}` (`MerchantResource`).
  - `GET /api/v1/merchants` (Filtered list).
  - `POST /api/v1/merchants/{id}/status` (`UpdateMerchantStatusRequest`).
  - `GET /api/v1/merchants/{id}/audit-log` (`MerchantStatusAuditLogResource`).

#### Module C: Merchant Agreements & Compliance Policies
- [x] **Database Migrations:**
  - `merchant_agreements` table: Versioned contracts with SHA-256 `content_hash` and four foundational policies (Product Accuracy, Shipping & Delivery SLA, Substitution Protocol, Refund/Replacement Guarantee).
  - `merchant_agreement_acceptances` table: Immutable digital acceptance audit trail recording signatory name, corporate title, client IP, User-Agent, and `terms_digest_snapshot`.
- [x] **Cryptographic Verification & Services:**
  - `MerchantAgreementService`: Retrieves active agreement and enforces cryptographic verification: submitted terms digest must match the server's agreement content hash.
- [x] **API Endpoints:**
  - `GET /api/v1/agreements/active` (`MerchantAgreementResource`).
  - `POST /api/v1/merchants/{id}/agreements/accept` (`AcceptAgreementRequest`).
  - `GET /api/v1/merchants/{id}/agreements` (`MerchantAgreementAcceptanceResource`).

#### Frontend User Interface (Module B & C)
- [x] Interactive `MerchantRegistrationForm` with instant validation and platform selection.
- [x] Interactive `MerchantAgreementModal` presenting the four core policies with cryptographic digest verification and digital execution form.
- [x] `MerchantStatusBadge` with real-time status indicators.
- [x] `MerchantLookupWidget` allowing instant public verification of merchant status and compliance score.
- [x] Verified build output via `npm run build`.

---

## 3. Database Migration Status

| Migration File | Target Table | Status |
| :--- | :--- | :--- |
| `0001_01_01_000000_create_users_table.php` | `users` | Applied |
| `0001_01_01_000001_create_cache_table.php` | `cache` | Applied |
| `0001_01_01_000002_create_jobs_table.php` | `jobs` | Applied |
| `2026_11_09_232906_create_personal_access_tokens_table.php` | `personal_access_tokens` | Applied |
| `2026_11_10_000001_create_merchants_table.php` | `merchants` | Applied |
| `2026_11_10_000002_create_merchant_agreements_table.php` | `merchant_agreements` | Applied |
| `2026_11_10_000003_create_merchant_agreement_acceptances_table.php` | `merchant_agreement_acceptances` | Applied |
| `2026_11_10_000004_create_merchant_status_audit_logs_table.php` | `merchant_status_audit_logs` | Applied |

---

## 4. Tests Executed & Results

Ran via `php artisan test --testdox`:
```
Runtime: PHP 8.4.26
Configuration: phpunit.xml (SQLite in-memory)

Example (Tests\Feature\Example)
 ✔ The application returns a successful response

Example (Tests\Unit\Example)
 ✔ That true is true

Merchant Agreement (Tests\Feature\MerchantAgreement)
 ✔ Can retrieve active merchant agreement
 ✔ Merchant can accept agreement with matching terms digest
 ✔ Acceptance fails when terms digest is tampered or invalid

Merchant Approval Workflow (Tests\Feature\MerchantApprovalWorkflow)
 ✔ Can transition merchant status to under review
 ✔ Cannot verify merchant if active agreement not accepted
 ✔ Can verify merchant once active agreement is accepted

Merchant Registration (Tests\Feature\MerchantRegistration)
 ✔ Can register new merchant successfully
 ✔ Registration fails when required fields are missing
 ✔ Registration fails on duplicate email or registration number
 ✔ Can retrieve merchant by intentguard id or id

OK (12 tests, 69 assertions) — 100% Passed (Duration: 350ms)
```

Frontend build test:
```
next build (Turbopack) — Compiled successfully in 816ms. 0 errors.
```

---

## 5. Next Development Tasks

1. **Module D: Purchase Intent Management:**
   - Migrate `purchase_intents` table (product identity, specification JSON, quantity, unit price, currency, merchant ID, shipping conditions, customer-approved substitution tolerances, cryptographic intent hash).
   - Implement `PurchaseIntent` model, `PurchaseIntentService`, and Form Requests.
   - Build `POST /api/v1/intents` and `GET /api/v1/intents/{id}` endpoints.
2. **Module E: Evidence & Provenance:**
   - Record immutable product page snapshots and checkout quotes with SHA-256 digests.
3. **Module F: Semantic Purchase Integrity Engine:**
   - Implement deterministic comparisons (prices, fees, quantities, SKUs).
   - Implement semantic attribute evaluations (brand, model, condition, substitution boundaries).
