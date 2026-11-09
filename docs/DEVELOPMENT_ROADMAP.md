# DEVELOPMENT ROADMAP: INTENTGUARD

> **Document Version:** 1.0.0  
> **Status:** Active / Master Roadmap  
> **Last Updated:** 2026-11-09

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
- [ ] Initialize decoupled Laravel REST API backend (`backend/`).
- [ ] Initialize Next.js App Router frontend with TypeScript and Tailwind CSS (`frontend/`).
- [ ] Establish Git repository with comprehensive `.gitignore`.
- [ ] Verify baseline execution of both backend and frontend applications.

---

## Phase 02: Problem Definition & Originality Research
- [x] Define core problem statement: Purchase Intent Mismatch, Spec Drift, and Unilateral Checkout Alteration.
- [ ] Conduct prior art research against existing transaction verification patents and fraud systems (Stripe Radar, Visa 3-D Secure, Sift, Riskified).
- [ ] Document technical differentiation: pre-capture multi-attribute intent verification vs identity fraud detection.
- [ ] Form measurable technical hypotheses regarding mismatch detection accuracy and false-positive rates.
- [ ] Preserve originality findings in `docs/invention-research/01-prior-art-and-technical-differentiation.md`.

---

## Phase 03: System Architecture & Technical Specifications
- [x] Design multi-tier decoupled architecture (`docs/SYSTEM_ARCHITECTURE.md`).
- [x] Specify relational schemas, constraints, and audit log tables (`docs/DATABASE_DESIGN.md`).
- [x] Formulate initial Architectural Decision Records (`docs/TECHNICAL_DECISIONS.md`).
- [x] Specify REST API v1 contracts (`docs/API_DOCUMENTATION.md`).
- [ ] Design event-driven messaging contracts between modules.

---

## Phase 04: Module B — Merchant Registration & Onboarding
- [ ] Create `merchants` database migration with ULID/IntentGuard ID (`IG-MCH-XXXXXXXX`), business registry, and platform fields.
- [ ] Implement `Merchant` Eloquent model with typed enums, relationships, and scopes.
- [ ] Build `RegisterMerchantRequest` with strict legal validation rules.
- [ ] Implement `MerchantService` handling collision-resistant ID generation and status lifecycle.
- [ ] Create `MerchantApprovalService` for administrative verification and rejection workflows.
- [ ] Build `MerchantController` REST API endpoints (`/api/v1/merchants/register`, `/api/v1/merchants/{id}`, `/api/v1/merchants/{id}/status`).
- [ ] Write comprehensive automated Feature tests (`tests/Feature/MerchantRegistrationTest.php`).

---

## Phase 05: Module C — Merchant Agreements & Policy Governance
- [ ] Create `merchant_agreements` database migration with versioning and SHA-256 `content_hash`.
- [ ] Create `merchant_agreement_acceptances` migration with digital signatory metadata and `terms_digest_snapshot`.
- [ ] Implement `MerchantAgreement` and `MerchantAgreementAcceptance` models.
- [ ] Seed initial legally compliant policies (Product Accuracy, Shipping & Delivery, Substitution Rules, Refund & Replacement).
- [ ] Build `AgreementService` verifying cryptographic digests upon digital signing.
- [ ] Build `MerchantAgreementController` endpoints (`/api/v1/agreements/active`, `/api/v1/merchants/{id}/agreements/accept`).
- [ ] Write automated tests verifying tamper-detection and acceptance verification.

---

## Phase 06: Modules D & E — Purchase Intent Management & Provenance
- [ ] Design and migrate `purchase_intents` and `purchase_evidence` tables.
- [ ] Build intent capture API recording product specifications, price, currency, quantity, and allowed substitution criteria.
- [ ] Implement cryptographic hashing for evidence snapshots (product catalog page snapshot, checkout parameters).
- [ ] Implement automated expiration for stale purchase intents.
- [ ] Write automated tests for intent capture and evidence hash verification.

---

## Phase 07: Module F — Semantic Purchase Integrity Engine
- [ ] Implement Tier 1 Deterministic Comparison Engine (exact numeric price, currency, quantity, SKU, and fee boundaries).
- [ ] Implement Tier 2 Semantic & Attribute Comparison Engine (brand, model, condition, variant tolerances).
- [ ] Implement Customer-Approved Substitution Evaluator.
- [ ] Measure engine execution latency (target: < 250ms per evaluation).
- [ ] Write unit tests with comprehensive positive and adversarial test vectors.

---

## Phase 08: Module G — Transaction Decision Engine
- [ ] Implement decision classification: `MATCH`, `MISMATCH`, `UNCERTAIN`, `HOLD_PENDING`.
- [ ] Implement gateway hold recommendation logic.
- [ ] Build customer confirmation trigger for `UNCERTAIN` verdicts.
- [ ] Write append-only decision audit logging.
- [ ] Test decision state machines against edge cases.

---

## Phase 09: Module I — PayPal Gateway Integration
- [ ] Implement PayPal Developer API client (OAuth2 token caching).
- [ ] Support Orders v2 creation with `intent: AUTHORIZE`.
- [ ] Implement conditional payment capture contingent on IntentGuard Decision Engine output.
- [ ] Build PayPal Webhook ingestion controller with cryptographic certificate/signature validation.
- [ ] Implement idempotency tables to prevent duplicate captures or replay attacks.

---

## Phase 10: Frontend User Interfaces (Next.js)
- [ ] Build shared UI component library (design system, Tailwind CSS).
- [ ] Build Customer Dashboard:
  - Active purchase intents.
  - Real-time verification results & mismatch warnings.
  - One-click confirmation modal for held transactions.
- [ ] Build Merchant Dashboard:
  - Onboarding and profile management.
  - Digital policy review and cryptographic signing modal.
  - Fulfilment telemetry and compliance score display.
- [ ] Build Administrator Dashboard:
  - Merchant vetting queue with document verification.
  - System-wide transaction integrity monitor.
  - Audit log explorer.

---

## Phase 11: Module H — Merchant Compliance & Dispute Adjudication
- [ ] Build merchant compliance telemetry service (tracking verified mismatches, shipping delays, chargebacks).
- [ ] Implement automated 100-point compliance scoring algorithm.
- [ ] Build structured dispute submission workflow for customers.
- [ ] Build merchant appeal workflow with evidence attachment.
- [ ] Enforce contractual status changes (warning, suspension, removal) with due process audit logging.

---

## Phase 12: Module J — System-Wide Audit & Security Hardening
- [ ] Implement central immutable audit logging across all entities.
- [ ] Configure role-based access control (Sanctum + Laravel Policies).
- [ ] Implement API rate limiting and brute-force protection.
- [ ] Enforce strict HTTPS and CSP headers.
- [ ] Audit dependencies for known CVEs.

---

## Phase 13: Quality Assurance & Integration Testing
- [ ] Run complete automated backend test suite (target: > 85% code coverage).
- [ ] Run frontend integration and component tests.
- [ ] Execute end-to-end sandbox purchase flow with PayPal sandbox credentials.
- [ ] Simulate adversarial attacks (tampered checkout payloads, replay attacks, forged webhooks).

---

## Phase 14: Pilot Deployment & Operational Readiness
- [ ] Containerize applications with Docker & Docker Compose.
- [ ] Configure CI/CD automated test pipelines.
- [ ] Deploy to staging environment with live MySQL database.
- [ ] Perform latency and load testing on the Integrity Engine.

---

## Phase 15: Commercialisation & Merchant Adoption
- [ ] Publish IntentGuard Trust Seal widget for e-commerce checkouts.
- [ ] Package SDKs/plugins for major platforms (Shopify, WooCommerce).
- [ ] Publish merchant compliance certification standards.
- [ ] Conduct empirical effectiveness study comparing dispute rates of IntentGuard-verified transactions vs baseline e-commerce transactions.
