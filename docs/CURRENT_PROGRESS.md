# CURRENT PROGRESS: INTENTGUARD

> **Current Status as of:** 2026-11-10  
> **Active Framework:** Framework 01 — Project Foundation (Completed)  
> **Completed Modules:** Module A, Module B, Module C, Module D, Module E, **Module F — Semantic Purchase Integrity Engine (SPIE)**  
> **Next Active Module:** Module G — Transaction Decision Engine (TDE)  
> **Last Updated By:** Lead Architect / Antigravity AI Assistant

---

## 1. Development Snapshot

| Dimension | Current State |
| :--- | :--- |
| **Current Framework** | Framework 01: Project Foundation (Completed) |
| **Current Modules** | Module A: Auth, Module B: Merchants, Module C: Agreements, Module D: Intents, Module E: Evidence, **Module F: SPIE (Completed)** |
| **Database Status** | 11 Migrations executed (`+ verification_decisions`) |
| **Automated Tests** | **61 tests passing, 272 assertions, 100% pass rate** (`php artisan test`) |
| **Frontend Status** | Next.js 16 App Router, TypeScript, Tailwind CSS (`npm run build` passing) |
| **Known Bugs** | None |
| **Next Milestone** | Module G: Transaction Decision Engine (TDE) & Payment Capture Policy |

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

#### Module D: Purchase Intent Management
- [x] **Database Migration & Model:**
  - `purchase_intents` table with product identity, specification JSON, quantity, unit price in cents, currency, shipping conditions, substitution tolerances, and SHA-256 `intent_hash`.
  - `PurchaseIntent` model with price decimal helpers, casts, and relationships to `User` and `Merchant`.
- [x] **Domain Service & Enums:**
  - `PurchaseIntentStatus` enum (`pending`, `confirmed`, `verified`, `mismatched`, `cancelled`, `expired`).
  - `PurchaseIntentService`: Deterministic canonical payload hashing (SHA-256), `record`, `find`, `cancel`, `expireOverdue`, `verifyIntegrity`.
- [x] **API Endpoints & Validation:**
  - `POST /api/v1/intents` (`RecordPurchaseIntentRequest`).
  - `GET /api/v1/intents` (Paginated user intents).
  - `GET /api/v1/intents/{id}` (Lookup by ID or hash).
  - `POST /api/v1/intents/{id}/cancel` (Customer intent cancellation).
  - `GET /api/v1/intents/{id}/verify-integrity` (Cryptographic tamper check).
- [x] **Frontend:** `types/intent.ts`, `services/intentService.ts`.

#### Module E: Evidence & Provenance
- [x] **Database Migration & Model:**
  - `purchase_evidence` table: Immutable append-only audit log with `evidence_type`, `source_url`, `content_snapshot`, `content_snapshot_blob_path`, `content_hash` (SHA-256), and `metadata_json`.
  - `PurchaseEvidence` model: `UPDATED_AT = null` (strictly append-only), `verifyContentIntegrity()` SHA-256 checker.
- [x] **Domain Service & Enums:**
  - `EvidenceType` enum (`product_page`, `checkout_screen`, `payment_quote`, `receipt`).
  - `EvidenceService`: `record` (inline hashing), `recordBlobReference` (large payloads), `forIntent` (with type filter), `verifyIntegrity` (tamper detection), `buildProvenanceChain` (chain of custody summary).
- [x] **API Endpoints & Validation:**
  - `POST /api/v1/intents/{intentId}/evidence` (`RecordEvidenceRequest`).
  - `GET /api/v1/intents/{intentId}/evidence` (Filterable list).
  - `GET /api/v1/intents/{intentId}/evidence/provenance-chain` (Grouped provenance chain).
  - `GET /api/v1/evidence/{id}` (Audit retrieval with content snapshot).
  - `GET /api/v1/evidence/{id}/verify-integrity` (Per-record cryptographic check).
- [x] **Frontend:** `types/evidence.ts`, `services/evidenceService.ts`.

#### Module F: Semantic Purchase Integrity Engine (SPIE)
- [x] **Database Migration & Model:**
  - `verification_decisions` table: Storing `purchase_intent_id`, `checkout_payload_json`, `discrepancy_flags_json`, `confidence_score`, `verdict`, and `hold_payment_capture`.
  - `VerificationDecision` model with enum casts, helper methods (`isMatch()`, `isCaptureHeld()`), and relationships.
  - Linked `verificationDecisions()` and `latestVerificationDecision()` relations to `PurchaseIntent`.
- [x] **Domain Engine & Enums:**
  - `VerificationVerdict` enum (`MATCH`, `MISMATCH`, `UNCERTAIN`, `REQUIRE_CUSTOMER_CONFIRMATION`) with `shouldHoldCapture()`.
  - `DiscrepancyCategory` enum (`merchant`, `price`, `currency`, `quantity`, `condition`, `specification`, `shipping`, `substitution`).
  - `DiscrepancySeverity` enum (`critical`, `warning`, `info`).
  - `DeterministicEvaluator`: Evaluates exact rules (merchant ID, currency code, line item quantity, price surges, discounts, hidden shipping fee surges, SKU).
  - `SemanticAttributeEvaluator`: Evaluates condition (new vs refurbished/used), brand, model, colour variations, storage capacities (upgrades vs downgrades), and applies customer-defined substitution tolerances.
  - `SemanticIntegrityEngine`: Orchestrates both evaluation layers, computes normalized confidence score (0.0000–1.0000), and outputs `IntegrityReport`.
- [x] **API Endpoints & Validation:**
  - `POST /api/v1/intents/{intentId}/verify` (`VerifyCheckoutRequest`): Evaluates checkout, sets verdict, syncs intent status, records decision.
  - `GET /api/v1/intents/{intentId}/verifications`: Historical verification decisions.
  - `GET /api/v1/verifications/{id}`: Detailed decision and discrepancy inspection.
- [x] **Frontend:** `types/verification.ts`, `services/verificationService.ts`.

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
| `2026_11_10_000005_create_purchase_intents_table.php` | `purchase_intents` | Applied |
| `2026_11_10_000006_create_purchase_evidence_table.php` | `purchase_evidence` | Applied |
| `2026_11_10_000007_create_verification_decisions_table.php` | `verification_decisions` | Applied |

---

## 4. Tests Executed & Results

Ran via `php artisan test --testdox`:
```
Runtime: PHP 8.4.26
Configuration: phpunit.xml (SQLite in-memory)

Authentication (Tests\Feature\Authentication)
 ✔ User can login with valid credentials
 ✔ Login fails with invalid credentials
 ✔ Unauthenticated request returns 401 json instead of missing route exception
 ✔ Authenticated user can access profile and logout

Deterministic Evaluator (Tests\Unit\DeterministicEvaluator)
 ✔ Clean matching payload has no discrepancies
 ✔ Detects merchant mismatch
 ✔ Detects currency mismatch
 ✔ Detects unauthorized price surge
 ✔ Tolerates favorable price discount
 ✔ Detects hidden shipping fee surge

Example (Tests\Feature\Example)
 ✔ The application returns a successful response

Example (Tests\Unit\Example)
 ✔ That true is true

Integrity Verification (Tests\Feature\IntegrityVerification)
 ✔ Customer can verify checkout and receive match verdict
 ✔ Price surge produces mismatch and updates intent status
 ✔ Disallowed colour variation requires customer confirmation
 ✔ Can list verification decisions for intent
 ✔ Can view single verification decision
 ✔ Customer cannot verify someone elses intent
 ✔ Unauthenticated verification requests are rejected

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

Purchase Evidence (Tests\Feature\PurchaseEvidence)
 ✔ Customer can record inline evidence for intent
 ✔ Customer can record blob reference evidence
 ✔ Recording fails when neither content nor blob path provided
 ✔ Recording fails for invalid evidence type
 ✔ Customer cannot record evidence for another customers intent
 ✔ Can list evidence for intent
 ✔ Can filter evidence by type
 ✔ Can show single evidence record including raw content
 ✔ Can retrieve provenance chain for intent
 ✔ Evidence integrity verification passes for intact record
 ✔ Evidence integrity verification fails when tampered
 ✔ Unauthenticated evidence requests are rejected

Purchase Intent (Tests\Feature\PurchaseIntent)
 ✔ Customer can record purchase intent
 ✔ Recording intent fails without required fields
 ✔ Can retrieve intent by id
 ✔ Can retrieve intent by hash
 ✔ Retrieving nonexistent intent returns 404
 ✔ Customer can cancel pending intent
 ✔ Cannot cancel already terminal intent
 ✔ Integrity verification passes for unmodified intent
 ✔ Unauthenticated access to intents is rejected
 ✔ Price helpers return correct decimal values

Semantic Attribute Evaluator (Tests\Unit\SemanticAttributeEvaluator)
 ✔ Detects unauthorized refurbished substitution
 ✔ Tolerates refurbished substitution when pre approved
 ✔ Detects brand mismatch
 ✔ Colour variation without permission produces warning
 ✔ Colour variation with permission is tolerated
 ✔ Storage downgrade is critical violation
 ✔ Storage upgrade is tolerated when policy permits

Semantic Integrity Engine (Tests\Unit\SemanticIntegrityEngine)
 ✔ Perfect match yields 1 confidence and match verdict
 ✔ Critical failure yields 0 confidence and mismatch verdict
 ✔ Untolerated warning triggers confirmation verdict

OK (61 tests, 272 assertions) — 100% Passed (Duration: ~690ms)
```

Frontend build test:
```
next build (Turbopack) — Compiled successfully in 275ms. 0 errors.
```

---

## 5. Next Development Tasks

1. **Module G: Transaction Decision Engine (TDE):**
   - Fine-tune capture hold policy and pre-capture enforcement rules.
   - Implement customer confirmation resolution workflow (`POST /api/v1/verifications/{id}/confirm` and `/reject`).
   - Transaction state transitions and webhook trigger notifications.
2. **Module H: Merchant Compliance & Accountability:**
   - Automated compliance scoring deduction algorithms.
   - Merchant dispute adjudication and compliance event logging.
