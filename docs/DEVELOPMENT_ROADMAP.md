# DEVELOPMENT ROADMAP: INTENTGUARD

> **Document Version:** 1.0.0  
> **Status:** Completed (100% Roadmap Execution)  
> **Last Updated:** 2026-11-11

This document outlines the complete 16-stage roadmap for the research, design, implementation, verification, and commercial deployment of **IntentGuard**.

---

## Roadmap Overview

```
[Phase 01: Foundation] ────> [Phase 02: Research & Novelty] ────> [Phase 03: Architecture & Design]
                                                                               │
                                                                               v
[Phase 06: Intent & Provenance] <──── [Phase 05: Agreements] <──── [Phase 04: Merchant Registration]
            │
            v
[Phase 07: Integrity Engine] ───────> [Phase 08: Decision Engine] ────> [Phase 09: Gateway & PayPal]
                                                                               │
                                                                               v
[Phase 12: Admin & Auditing] <─────── [Phase 11: Compliance] <───────── [Phase 10: Frontend Dashboards]
            │
            v
[Phase 13: Security Hardening] ─────> [Phase 14: Quality Assurance] ───> [Phase 15: Commercialisation]
```

---

## Phase 01: Project Foundation & AI Context System
- [x] Inspect development environment (PHP 8.4+, Composer, Node.js v24+, npm, MySQL).
- [x] Establish portable AI development context system (`AGENTS.md`, `GEMINI.md`, `CLAUDE.md`, `.github/copilot-instructions.md`, `README.md`).
- [x] Establish documentation directory and core specification files (`docs/`).
- [x] Initialize decoupled Laravel REST API backend (`backend/`).
- [x] Initialize Next.js App Router frontend with TypeScript and Tailwind CSS (`frontend/`).
- [x] Establish Git repository with comprehensive `.gitignore`.
- [x] Verify baseline execution of both backend and frontend applications.

---

## Phase 02: Problem Definition & Originality Research
- [x] Define core problem statement: Purchase Intent Mismatch, Spec Drift, and Unilateral Checkout Alteration.
- [x] Conduct prior art research against existing transaction verification patents and fraud systems (Stripe Radar, Visa 3-D Secure, Sift, Riskified).
- [x] Document technical differentiation: pre-capture multi-attribute intent verification vs identity fraud detection.
- [x] Form measurable technical hypotheses regarding mismatch detection accuracy and false-positive rates.
- [x] Preserve originality findings in `docs/invention-research/01-prior-art-and-technical-differentiation.md`.

---

## Phase 03: System Architecture & Technical Specifications
- [x] Design multi-tier decoupled architecture (`docs/SYSTEM_ARCHITECTURE.md`).
- [x] Specify relational schemas, constraints, and audit log tables (`docs/DATABASE_DESIGN.md`).
- [x] Formulate initial Architectural Decision Records (`docs/TECHNICAL_DECISIONS.md`).
- [x] Specify REST API v1 contracts (`docs/API_DOCUMENTATION.md`).
- [x] Design event-driven messaging contracts between modules.

---

## Phase 04: Module B — Merchant Registration & Onboarding
- [x] Create `merchants` database migration with ULID/IntentGuard ID (`IG-MCH-XXXXXXXX`), business registry, and platform fields.
- [x] Implement `Merchant` Eloquent model with typed enums, relationships, and scopes.
- [x] Build `RegisterMerchantRequest` with strict legal validation rules.
- [x] Implement `MerchantService` handling collision-resistant ID generation and status lifecycle.
- [x] Create `MerchantApprovalService` for administrative verification and rejection workflows.
- [x] Build `MerchantController` REST API endpoints (`/api/v1/merchants/register`, `/api/v1/merchants/{id}`, `/api/v1/merchants/{id}/status`).
- [x] Write comprehensive automated Feature tests (`tests/Feature/MerchantRegistrationTest.php`).

---

## Phase 05: Module C — Merchant Agreements & Policy Governance
- [x] Create `merchant_agreements` database migration with versioning and SHA-256 `content_hash`.
- [x] Create `merchant_agreement_acceptances` migration with digital signatory metadata and `terms_digest_snapshot`.
- [x] Implement `MerchantAgreement` and `MerchantAgreementAcceptance` models.
- [x] Seed initial legally compliant policies (Product Accuracy, Shipping & Delivery, Substitution Rules, Refund & Replacement).
- [x] Build `AgreementService` verifying cryptographic digests upon digital signing.
- [x] Build `MerchantAgreementController` endpoints (`/api/v1/agreements/active`, `/api/v1/merchants/{id}/agreements/accept`).
- [x] Write automated tests verifying tamper-detection and acceptance verification.

---

## Phase 06: Modules D & E — Purchase Intent Management & Provenance
- [x] Design and migrate `purchase_intents` and `purchase_evidence` tables.
- [x] Build intent capture API recording product specifications, price, currency, quantity, and allowed substitution criteria.
- [x] Implement cryptographic hashing for evidence snapshots (product catalog page snapshot, checkout parameters).
- [x] Implement automated expiration for stale purchase intents.
- [x] Write automated tests for intent capture and evidence hash verification.

---

## Phase 07: Module F — Semantic Purchase Integrity Engine
- [x] Implement Tier 1 Deterministic Comparison Engine (exact numeric price, currency, quantity, SKU, and fee boundaries).
- [x] Implement Tier 2 Semantic & Attribute Comparison Engine (brand, model, condition, variant tolerances).
- [x] Implement Customer-Approved Substitution Evaluator.
- [x] Measure engine execution latency (target: < 250ms per evaluation).
- [x] Write unit tests with comprehensive positive and adversarial test vectors.

---

## Phase 08: Module G — Transaction Decision Engine
- [x] Implement decision classification: `MATCH`, `MISMATCH`, `UNCERTAIN`, `HOLD_PENDING`.
- [x] Implement gateway hold recommendation logic.
- [x] Build customer confirmation trigger for `UNCERTAIN` verdicts.
- [x] Write append-only decision audit logging.
- [x] Test decision state machines against edge cases.

---

## Phase 09: Module I — PayPal Gateway Integration
- [x] Implement PayPal Developer API client (OAuth2 token caching).
- [x] Support Orders v2 creation with `intent: AUTHORIZE`.
- [x] Implement conditional payment capture contingent on IntentGuard Decision Engine output.
- [x] Build PayPal Webhook ingestion controller with cryptographic certificate/signature validation.
- [x] Implement idempotency tables to prevent duplicate captures or replay attacks.

---

## Phase 10: Frontend User Interfaces (Next.js)
- [x] Build shared UI component library (design system, Tailwind CSS).
- [x] Build Customer Dashboard:
  - Active purchase intents with SHA-256 tamper verification.
  - Real-time SPIE verification results & mismatch warnings.
  - One-click confirmation modal for held transactions.
- [x] Build Merchant Dashboard:
  - Onboarding and profile management.
  - Digital policy review and cryptographic signing modal.
  - Fulfilment telemetry and compliance score display.
- [x] Build Administrator Dashboard:
  - Merchant vetting queue with document verification.
  - System-wide transaction integrity monitor.
  - Linear SHA-256 audit log explorer with tamper-detection verification.

---

## Phase 11: Module H — Merchant Compliance & Dispute Adjudication
- [x] Build merchant compliance telemetry service (tracking verified mismatches, shipping delays, chargebacks).
- [x] Implement automated 100-point compliance scoring algorithm.
- [x] Build structured dispute submission workflow for customers.
- [x] Build merchant appeal workflow with evidence attachment.
- [x] Enforce contractual status changes (warning, suspension, removal) with due process audit logging.

---

## Phase 12: Module J — System-Wide Audit & Security Hardening
- [x] Implement central immutable audit logging across all entities with linear SHA-256 blockchain-style chaining.
- [x] Configure role-based access control (Sanctum + Laravel Policies).
- [x] Implement API rate limiting and brute-force protection.
- [x] Enforce strict HTTPS and CSP security headers (`EnforceSecurityHeadersMiddleware`).
- [x] Audit dependencies for known CVEs.

---

## Phase 13: Quality Assurance & Integration Testing
- [x] Run complete automated backend test suite (target: > 85% code coverage).
- [x] Run frontend integration and component tests.
- [x] Execute end-to-end sandbox purchase flow with PayPal sandbox credentials.
- [x] Simulate adversarial attacks (tampered checkout payloads, replay attacks, forged webhooks, audit log tampering).

---

## Phase 14: Pilot Deployment & Operational Readiness
- [x] Containerize applications with multi-stage Dockerfiles (`backend/Dockerfile`, `frontend/Dockerfile`).
- [x] Build root `docker-compose.yml` orchestrating API, UI, MySQL 8.0, and Redis 7.
- [x] Configure CI/CD automated test pipelines (`.github/workflows/ci.yml`).
- [x] Prepare production environment templates (`.env.production.example`).
- [x] Perform latency and load testing on the Integrity Engine (`EnginePerformanceBenchmarkTest`: 0.38ms mean latency).

---

## Phase 15: Commercialisation & Merchant Adoption
- [x] Publish IntentGuard Trust Seal widget for e-commerce checkouts (`intentguard-seal.js` & `IntentGuardSeal.tsx`).
- [x] Build interactive Trust Seal showcase and embed generator (`/seal-demo`).
- [x] Package integration guides for major platforms (`docs/merchant-adoption/ECOMMERCE_INTEGRATION_GUIDE.md`).
- [x] Publish merchant compliance certification standards (`docs/merchant-adoption/MERCHANT_COMPLIANCE_STANDARDS.md`).
- [x] Conduct empirical effectiveness study comparing dispute rates of IntentGuard-verified transactions vs baseline e-commerce transactions (`docs/invention-research/02-empirical-dispute-reduction-study.md`).
