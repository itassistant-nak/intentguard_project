# CURRENT PROGRESS: INTENTGUARD

> **Current Status as of:** 2026-11-10  
> **Active Framework:** Framework 01 — Project Foundation (Completed)  
> **Completed Modules:** Module A, Module B, Module C, Module D, Module E, Module F, Module G, Module H, Module I, **Module J — System-Wide Audit & Security (Completed)**  
> **Next Active Milestone:** Phase 10: Frontend User Interfaces (Customer, Merchant, Admin Portals)  
> **Last Updated By:** Lead Architect / Antigravity AI Assistant

---

## 1. Development Snapshot

| Dimension | Current State |
| :--- | :--- |
| **Current Framework** | Framework 01: Project Foundation (Completed) |
| **Current Modules** | Module A: Auth, Module B: Merchants, Module C: Agreements, Module D: Intents, Module E: Evidence, Module F: SPIE, Module G: TDE, Module H: Compliance, Module I: PayPal, **Module J: Audit & Provenance (Completed)** |
| **Database Status** | 17 Migrations executed (`+ system_audit_logs`) |
| **Automated Tests** | **130 tests passing, 537 assertions, 100% pass rate** (`php artisan test`) |
| **Frontend Status** | Next.js 16 App Router, TypeScript, Tailwind CSS (`npm run build` passing) |
| **Known Bugs** | None |
| **Next Milestone** | Phase 10: Frontend User Interfaces (Customer, Merchant, Admin Portals) |

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

#### Module G: Transaction Decision Engine (TDE)
- [x] **Database Migration & Models:**
  - Added `resolution_status`, `resolved_at`, and `resolved_by` to `verification_decisions`.
  - Created `decision_resolution_audit_logs` table (append-only: `verification_decision_id`, `action`, `actor_type`, `actor_id`, `reason`, `metadata_json`, `created_at`).
  - `DecisionResolutionAuditLog` model with enum casts (`ResolutionAuditAction`, `ResolutionActorType`).
  - Added resolution audit history and resolver relations on `VerificationDecision`.
- [x] **Domain Engine & Enums:**
  - `DecisionResolutionStatus` enum (`unresolved`, `customer_confirmed`, `customer_rejected`, `hold_expired`).
  - `ResolutionAuditAction` enum (`hold_placed`, `customer_confirmed`, `customer_rejected`, `capture_released`, `capture_voided`, `hold_expired`).
  - `ResolutionActorType` enum (`customer`, `system`, `admin`, `merchant`).
  - `CaptureAction` enum (`capture_immediate`, `capture_hold`, `capture_void`).
  - `TransactionDecisionEngine`:
    * Determines gateway capture directives with merchant compliance escalation (< 70.00 score triggers hold).
    * `recordInitialHold`: Records system audit trail upon initial hold placement.
    * `resolveCustomerConfirmation`: Releases payment capture hold, updates intent to confirmed, logs audit trail (`customer_confirmed`, `capture_released`).
    * `resolveCustomerRejection`: Voids payment capture, updates intent to mismatched, logs audit trail (`customer_rejected`, `capture_voided`).
    * State machine guards against resolving non-held or already resolved decisions.
- [x] **API Endpoints & Validation:**
  - `POST /api/v1/verifications/{id}/confirm` (`ConfirmResolutionRequest`): Customer 1-click confirmation releasing capture hold.
  - `POST /api/v1/verifications/{id}/reject` (`RejectResolutionRequest`): Customer rejection voiding capture.
  - `GET /api/v1/verifications/{id}/audit-log`: Retrieves append-only resolution history.
  - `GET /api/v1/verifications/pending-holds`: Lists decisions currently on capture hold awaiting customer confirmation.
- [x] **Frontend:** Updated `types/verification.ts` and `services/verificationService.ts` (`confirmResolution`, `rejectResolution`, `getResolutionAuditLogs`, `listPendingHolds`).

#### Module H: Merchant Compliance Telemetry & Customer Dispute Adjudication
- [x] **Database Relational Migrations:**
  - `merchant_compliance_events` table: Immutable append-only audit log tracking compliance score deltas, severity, evidence references, appeal status, and adjudication trail.
  - `customer_disputes` table: Dispute lifecycle tracking (`purchase_intent_id`, `customer_id`, `merchant_id`, category, claimed amount, evidence reference, status, merchant response, administrative resolution notes).
- [x] **Domain Models & Enums:**
  - `MerchantComplianceEvent` model with enum casts (`ComplianceEventType`, `ComplianceAppealStatus`) and append-only constraints (`UPDATED_AT = null`).
  - `CustomerDispute` model with enum casts (`DisputeCategory`, `DisputeStatus`) and terminal state checkers (`isTerminal()`).
  - `ComplianceEventType` enum (`mismatch_confirmed`: -15.00, `unauthorized_substitution`: -10.00, `shipping_delay_breach`: -5.00, `chargeback_received`: -20.00, `positive_fulfilment_recovery`: +2.00).
  - `ComplianceAppealStatus` enum (`none`, `pending_review`, `approved`, `rejected`).
  - `DisputeCategory` enum (`item_not_as_described`, `unauthorized_substitution`, `excessive_shipping_delay`, `pricing_discrepancy`, `counterfeit_or_altered`, `other`).
  - `DisputeStatus` enum (`opened`, `under_merchant_response`, `escalated_to_admin`, `resolved_refunded`, `resolved_replaced`, `resolved_rejected`).
  - Updated `Merchant` model with `complianceEvents()` and `disputes()` relationships.
- [x] **Compliance Service & Dynamic Scoring:**
  - `ComplianceService`:
    * `recordComplianceEvent`: Applies dynamic scoring curve (0.00–100.00 scale) and evaluates contractual thresholds:
      - Score < 50.00%: Triggers automated contractual suspension (`suspended`) and audit trail.
      - Score < 70.00%: Triggers placement under administrative review (`under_review`) and audit trail.
    * `submitAppeal`: Allows merchants to appeal compliance score penalties with supporting evidence.
    * `adjudicateAppeal`: Admin approval reverses penalty delta, restores compliance score, and automatically restores status to `verified` if score recovers >= 70.00%.
    * `openDispute`: Customers file disputes against purchased intents, automatically logging a `DISPUTE_FILED` compliance event.
    * `respondToDispute`: Merchants submit formal responses and mitigation details.
    * `resolveDispute`: Administrative resolution (`resolved_refunded`, `resolved_replaced`, `resolved_rejected`) with mandatory resolution notes.
- [x] **API Endpoints & Validation:**
  - `GET /api/v1/merchants/{id}/compliance/events`: Paginated compliance history and score audit.
  - `POST /api/v1/merchants/{id}/compliance/events` (`RecordComplianceEventRequest`): Admin telemetry recording.
  - `POST /api/v1/compliance/events/{id}/appeal` (`SubmitAppealRequest`): Merchant appeal submission.
  - `POST /api/v1/compliance/events/{id}/adjudicate` (`AdjudicateAppealRequest`): Admin adjudication.
  - `POST /api/v1/intents/{id}/disputes` (`OpenDisputeRequest`): Customer dispute filing.
  - `GET /api/v1/disputes`: Filtered list of disputes.
  - `GET /api/v1/disputes/{id}`: Dispute detail view.
  - `POST /api/v1/disputes/{id}/respond` (`RespondDisputeRequest`): Merchant response.
  - `POST /api/v1/disputes/{id}/resolve` (`ResolveDisputeRequest`): Admin formal resolution.
- [x] **Frontend:** `types/compliance.ts` and `services/complianceService.ts`.

#### Module I: PayPal Gateway Integration (Orders v2 & Payments v2)
- [x] **Database Relational Migrations:**
  - `payment_authorizations` table: Tracking two-step authorization (`gateway`, `order_id`, `authorization_id`, `capture_id`, `amount_cents`, `currency`, `status`, `idempotency_key`, `customer_payer_id`, `authorized_at`, `captured_at`, `voided_at`).
  - `webhook_events` table: Gateway webhook event store with cryptographic signature verification flag and idempotency replay deduplication.
- [x] **Domain Models & Enums:**
  - `PaymentAuthorization` model with `PaymentAuthorizationStatus` enum (`created`, `authorized`, `captured`, `voided`, `held`).
  - `WebhookEvent` model with `WebhookEventStatus` enum (`received`, `processed`, `failed`, `ignored`).
  - Linked `paymentAuthorizations()` and `latestPaymentAuthorization()` relationships to `PurchaseIntent`.
  - Added factories: `PaymentAuthorizationFactory`, `WebhookEventFactory`.
- [x] **PayPal Client & Domain Services:**
  - `PayPalClient`: Server-to-server HTTP client using `Illuminate\Support\Facades\Http`:
    * OAuth2 token retrieval with automatic memory caching and expiration buffer.
    * Orders v2 creation (`POST /v2/checkout/orders`) with `intent="AUTHORIZE"`.
    * Orders v2 authorization (`POST /v2/checkout/orders/{id}/authorize`).
    * Payments v2 capture (`POST /v2/payments/authorizations/{id}/capture`).
    * Payments v2 void (`POST /v2/payments/authorizations/{id}/void`).
    * Webhook transmission signature verification (`/v1/notifications/verify-webhook-signature`).
  - `PayPalGatewayService`:
    * `createOrderForIntent`: Generates PayPal order with strict idempotency protection.
    * `processAuthorization`: Executes payment authorization, runs SPIE + TDE, and intercepts capture:
      - Clean `MATCH`: Executes immediate capture, updates status to `captured`.
      - `CAPTURE_HOLD` (`REQUIRE_CUSTOMER_CONFIRMATION` / `UNCERTAIN`): Holds payment uncaptured, records audit hold.
      - Severe `MISMATCH`: Executes immediate void on PayPal, updates intent to `mismatched`.
    * `executeHeldCapture`: Releases capture hold on PayPal upon verified customer confirmation.
    * `executeHeldVoid`: Voids authorized payment upon customer rejection.
  - `PayPalWebhookService`:
    * Webhook signature verification and replay attack prevention (deduplication by `event_id`).
    * State machine synchronization (`PAYMENT.AUTHORIZATION.CREATED`, `PAYMENT.CAPTURE.COMPLETED`, `PAYMENT.AUTHORIZATION.VOIDED`).
- [x] **REST API Endpoints & Form Requests:**
  - `POST /api/v1/intents/{id}/paypal/order` (`CreatePayPalOrderRequest`): Initialize order with idempotency key.
  - `POST /api/v1/intents/{id}/paypal/authorize` (`AuthorizePayPalOrderRequest`): Authorize and evaluate with TDE interception.
  - `GET /api/v1/payments/{id}` (`PaymentAuthorizationResource`): Detailed payment inspection.
  - `POST /api/v1/payments/{id}/capture-held` (`CaptureHeldPaymentRequest`): Customer release of held funds.
  - `POST /api/v1/payments/{id}/void-held` (`VoidHeldPaymentRequest`): Customer rejection voiding funds.
  - `POST /api/v1/webhooks/paypal`: Cryptographically verified webhook ingestion.
- [x] **Frontend:** `types/payment.ts` and `services/paymentService.ts`.

#### Module J: System-Wide Audit, Provenance & Administrative Reporting
- [x] **Database Relational Migrations:**
  - `system_audit_logs` table: Centralized immutable audit trail (`auditable_type`, `auditable_id`, `event`, `actor_type`, `actor_id`, `actor_email`, `ip_address`, `user_agent`, `old_values_json`, `new_values_json`, `metadata_json`, `previous_hash`, `tamper_hash`, `created_at`). Strictly append-only (`UPDATED_AT = null`).
- [x] **Domain Models & Enums:**
  - `SystemAuditLog` model with deterministic SHA-256 linear hash chain calculation (`SystemAuditLog::computeDigest`).
  - `SystemAuditEvent` enum: Comprehensive event taxonomy covering merchants, agreements, intents, verifications, payments, disputes, compliance, security logins, and rate limits.
  - Added factory: `SystemAuditLogFactory`.
- [x] **Audit & Reporting Services:**
  - `AuditLogService`:
    * `record`: Automatically chains each new audit record to the preceding record's `tamper_hash`, forming a tamper-evident blockchain-style hash chain.
    * `verifyChain`: Traverses sequential audit records, recalculates payload digests, and mathematically verifies that no records have been altered, injected, or deleted.
  - `AdminReportingService`:
    * Aggregates platform-wide fraud prevention impact ($ value and count of price surges and mismatches intercepted).
    * Calculates intent distributions, payment capture volumes (captured vs held vs voided), merchant health averages, and dispute metrics.
- [x] **Security Middleware:**
  - `EnforceSecurityHeadersMiddleware`: Injects OWASP-standard security headers (`X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY`, `X-XSS-Protection: 1; mode=block`, `Referrer-Policy: strict-origin-when-cross-origin`, CSP) across all HTTP responses.
- [x] **REST API Endpoints & Resources:**
  - `GET /api/v1/admin/audit-logs` (`SystemAuditLogResource`): Filterable paginated system audit log explorer.
  - `GET /api/v1/admin/audit-logs/verify-chain`: Cryptographic chain verification endpoint.
  - `GET /api/v1/admin/metrics`: Platform analytics and fraud prevention metrics.
  - Access control: Strict zero-trust restriction to users with `role === 'admin'`.
- [x] **Frontend:** `types/audit.ts` and `services/auditService.ts`.

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
| `2026_11_10_000008_create_decision_resolution_audit_logs_table.php` | `decision_resolution_audit_logs` | Applied |
| `2026_11_10_000009_create_merchant_compliance_events_table.php` | `merchant_compliance_events` | Applied |
| `2026_11_10_000010_create_customer_disputes_table.php` | `customer_disputes` | Applied |
| `2026_11_10_000011_create_payment_authorizations_table.php` | `payment_authorizations` | Applied |
| `2026_11_10_000012_create_webhook_events_table.php` | `webhook_events` | Applied |
| `2026_11_10_000013_create_system_audit_logs_table.php` | `system_audit_logs` | Applied |

---

## 4. Tests Executed & Results

Ran via `php artisan test --testdox`:
```
Runtime: PHP 8.4.26
Configuration: phpunit.xml (SQLite in-memory)

Admin Audit (Tests\Feature\AdminAudit)
 ✔ Admin can view system audit logs
 ✔ Admin can filter audit logs by event
 ✔ Admin can verify audit chain integrity
 ✔ Non admin is forbidden from audit logs
 ✔ Security headers are enforced on api responses

Admin Metrics (Tests\Feature\AdminMetrics)
 ✔ Admin can retrieve platform metrics
 ✔ Non admin is forbidden from platform metrics

Admin Reporting Service (Tests\Unit\AdminReportingService)
 ✔ Get platform metrics calculates totals and fraud prevention

Audit Log Service (Tests\Unit\AuditLogService)
 ✔ Record creates audit log with cryptographic hash
 ✔ Subsequent records form a linear hash chain
 ✔ Verify chain passes for untampered logs
 ✔ Verify chain detects tampered payload data
 ✔ Verify chain detects severed hash link

Authentication (Tests\Feature\Authentication)
 ✔ User can login with valid credentials
 ✔ Login fails with invalid credentials
 ✔ Unauthenticated request returns 401 json instead of missing route exception
 ✔ Authenticated user can access profile and logout

Compliance Service (Tests\Unit\ComplianceService)
 ✔ Record compliance event deducts merchant score
 ✔ Score cannot drop below zero or exceed 100
 ✔ Score drop below 70 triggers under review status
 ✔ Score drop below 50 triggers suspended status
 ✔ Merchant can submit appeal
 ✔ Approved appeal reverses score deduction and restores status
 ✔ Rejected appeal sustains penalty

Customer Dispute (Tests\Feature\CustomerDispute)
 ✔ Customer can open dispute against intent
 ✔ Customer can list their disputes
 ✔ Merchant can respond to dispute
 ✔ Admin can resolve dispute with refund
 ✔ Unauthenticated user cannot access disputes

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

Merchant Compliance (Tests\Feature\MerchantCompliance)
 ✔ Can list compliance events for merchant
 ✔ Admin can record compliance event
 ✔ Non admin cannot record compliance event
 ✔ Merchant can submit appeal
 ✔ Admin can adjudicate appeal

Merchant Registration (Tests\Feature\MerchantRegistration)
 ✔ Can register new merchant successfully
 ✔ Registration fails when required fields are missing
 ✔ Registration fails on duplicate email or registration number
 ✔ Can retrieve merchant by intentguard id or id

Pay Pal Client (Tests\Unit\PayPalClient)
 ✔ Get access token retrieves and caches token
 ✔ Create order sends authorize intent
 ✔ Authorize order calls paypal authorize endpoint
 ✔ Capture authorization calls capture endpoint
 ✔ Void authorization calls void endpoint
 ✔ Verify webhook signature validates headers

Pay Pal Gateway Service (Tests\Unit\PayPalGatewayService)
 ✔ Create order for intent creates payment authorization
 ✔ Process authorization clean match captures immediately
 ✔ Process authorization mismatch voids authorization
 ✔ Process authorization discrepancy holds authorization
 ✔ Execute held capture captures held authorization
 ✔ Execute held void voids held authorization

Pay Pal Payment (Tests\Feature\PayPalPayment)
 ✔ Customer can create paypal order for their intent
 ✔ Customer cannot create order for another customers intent
 ✔ Customer can authorize order and triggers interception
 ✔ Customer can view payment authorization
 ✔ Customer can release held capture
 ✔ Customer can void held capture
 ✔ Unauthenticated requests are rejected

Pay Pal Webhook (Tests\Feature\PayPalWebhook)
 ✔ Webhook with valid signature is processed
 ✔ Webhook with invalid signature is rejected
 ✔ Duplicate webhook is deduplicated
 ✔ Capture completed webhook synchronizes payment authorization

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

Transaction Decision (Tests\Feature\TransactionDecision)
 ✔ Customer can confirm pending hold decision
 ✔ Customer can reject pending hold decision
 ✔ Confirming already resolved decision returns 422
 ✔ Rejection requires mandatory reason
 ✔ Can view resolution audit logs
 ✔ Can list pending holds for customer
 ✔ Customer cannot confirm another customers decision
 ✔ Unauthenticated resolution is rejected

Transaction Decision Engine (Tests\Unit\TransactionDecisionEngine)
 ✔ Clean match determines immediate capture
 ✔ Mismatch determines void capture
 ✔ Uncertain determines hold capture
 ✔ Require confirmation determines hold capture
 ✔ Low compliance score merchant escalates match to hold
 ✔ Resolve customer confirmation releases hold and updates status
 ✔ Resolve customer rejection voids capture and updates status
 ✔ Resolving already resolved decision throws exception

OK (130 tests, 537 assertions) — 100% Passed (Duration: ~1.44s)
```

Frontend build test:
```
next build (Turbopack) — Compiled successfully in 277ms. Finished TypeScript in 970ms. 0 errors.
```

---

## 5. Next Development Tasks

1. **Phase 10: Frontend User Interfaces (Next.js):**
   - Customer Portal: purchase intent creation, interactive mismatch warnings, one-click confirmation for held transactions.
   - Merchant Portal: onboarding & verification status, cryptographic agreement signing modal, fulfilment telemetry, and dispute response.
   - Administrator Console: merchant vetting queue, transaction integrity monitor, audit log explorer, and cryptographic provenance verifier.
2. **Phase 13: End-to-End Quality Assurance & Sandboxing:**
   - Adversarial attack simulations (tampered checkout payloads, replay attacks, forged webhooks).
   - PayPal Sandbox live purchase flow verification.
