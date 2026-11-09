# TECHNICAL DECISIONS & ARCHITECTURAL DECISION RECORDS (ADRs)

> **Document Version:** 1.0.0  
> **Status:** Active  
> **Last Updated:** 2026-11-09

This document records the critical architectural and engineering decisions made during the lifecycle of IntentGuard, including the context, considered alternatives, decisions reached, rationale, and consequences.

---

## Index of Architectural Decision Records

- [ADR-001: Decoupled Dual-Application Architecture (Laravel + Next.js)](#adr-001-decoupled-dual-application-architecture-laravel--nextjs)
- [ADR-002: Relational Data Model with SQLite In-Memory Testing](#adr-002-relational-data-model-with-sqlite-in-memory-testing)
- [ADR-003: Two-Tier Transaction Integrity & Verification Mechanism](#adr-003-two-tier-transaction-integrity--verification-mechanism)
- [ADR-004: Cryptographic Provenance for Agreements and Intent Snapshots](#adr-004-cryptographic-provenance-for-agreements-and-intent-snapshots)
- [ADR-005: Strict Idempotency and Signature Verification for Payment Gateways](#adr-005-strict-idempotency-and-signature-verification-for-payment-gateways)

---

### ADR-001: Decoupled Dual-Application Architecture (Laravel + Next.js)
- **Date:** 2026-11-09
- **Status:** Approved
- **Context:**
  IntentGuard requires a robust, secure backend capable of relational data management, background jobs, cryptographic hashing, and external API integrations (e.g. PayPal), alongside an interactive, fast, and accessible user interface for customers, merchants, and administrators.
- **Alternatives Considered:**
  1. *Monolithic Laravel with Blade / Inertia.js:* Simpler single repository setup, but less flexible for potential mobile clients, browser extensions, or independent frontend deployments.
  2. *Full-stack Next.js with Serverless Functions:* Node.js backend handles frontend well, but lacks the enterprise-grade ecosystem of Laravel for complex transactional workflows, relational migrations, policies, and job queues.
  3. *Decoupled Laravel REST API + Next.js Frontend:* Independent development, strict contract-driven API boundaries, decoupled deployments, and full leverage of Laravel's security ecosystem and React/Next.js UI capabilities.
- **Decision:**
  Adopt a Decoupled Dual-Application Architecture: `backend/` powered by Laravel 11+ and `frontend/` powered by Next.js 14+ (App Router, TypeScript, Tailwind CSS).
- **Consequences:**
  Backend and frontend maintain independent lifecycles. Requires explicit CORS configuration and API contract documentation (`docs/API_DOCUMENTATION.md`).

---

### ADR-002: Relational Data Model with SQLite In-Memory Testing
- **Date:** 2026-11-09
- **Status:** Approved
- **Context:**
  IntentGuard manages complex financial and legal relationships (Merchants, Agreements, Acceptances, Purchase Intents, Audit Logs). Automated CI and developer environments often run inside isolated containers or sandboxes where persistent MySQL root services may not be running.
- **Alternatives Considered:**
  1. *MySQL Only:* Accurate for production, but creates heavy external dependencies that fail in lightweight sandboxes or fast unit test environments.
  2. *NoSQL / Document Store (MongoDB):* Weak relational integrity; unacceptable for audit trails and financial dispute records.
  3. *MySQL for Production/Staging + SQLite In-Memory for Automated Tests:* Standard Laravel pattern ensuring full relational schema integrity while providing zero-dependency, sub-second test execution.
- **Decision:**
  Use MySQL 8.0+ as the target production database, and configure SQLite in-memory (`:memory:`) in `phpunit.xml` for all automated tests. Ensure all migrations are compatible with both database dialects.
- **Consequences:**
  Avoid database-specific proprietary SQL expressions. Enforce constraints at the Schema Builder level.

---

### ADR-003: Two-Tier Transaction Integrity & Verification Mechanism
- **Date:** 2026-11-09
- **Status:** Approved
- **Context:**
  IntentGuard must compare original customer purchase intent against actual checkout payloads. Financial authorization decisions cannot be delegated blindly to a black-box AI model.
- **Alternatives Considered:**
  1. *Pure Generative AI Assessment:* High hallucination risk, non-deterministic outputs, and lack of mathematical auditability.
  2. *Pure Exact-Match Regex/String Matching:* Brittle; fails when merchants use minor title formatting differences, legitimate variant names, or acceptable substitutions.
  3. *Two-Tier Hybrid Engine:*
     - Tier 1: Deterministic evaluation for exact scalar attributes (Price, Currency, Quantity, Seller ID, Mandatory Fees).
     - Tier 2: Semantic evaluation for descriptive attributes (Brand, Model, Condition, Customer-Approved Substitution Tolerances).
- **Decision:**
  Implement the Two-Tier Hybrid Engine (Module F) feeding into a deterministic Transaction Decision Engine (Module G) that yields explicit status codes (`MATCH`, `MISMATCH`, `UNCERTAIN`).
- **Consequences:**
  Every financial mismatch has a verifiable mathematical explanation. AI is used strictly for descriptive similarity within bounded tolerance thresholds.

---

### ADR-004: Cryptographic Provenance for Agreements and Intent Snapshots
- **Date:** 2026-11-09
- **Status:** Approved
- **Context:**
  In dispute mediation and legal enforcement, merchants or customers could claim terms or item specifications were modified after checkout.
- **Alternatives Considered:**
  1. *Plain Database Timestamps:* Can be tampered with or disputed as unilateral database modifications.
  2. *Blockchain Smart Contracts:* Excessive gas costs, public ledger privacy concerns, and latency unacceptable for sub-second checkouts.
  3. *SHA-256 Cryptographic Digests & Snapshot Snapshots:* High performance, standardized, mathematically tamper-evident.
- **Decision:**
  Compute SHA-256 digests (`content_hash`) of all agreement policy texts. When a merchant accepts an agreement, store the exact `terms_digest_snapshot` along with signatory metadata, client IP, and UTC timestamp. Apply the same digest mechanism to Purchase Intent records.
- **Consequences:**
  Provides incontrovertible digital evidence for dispute resolution without blockchain complexity.

---

### ADR-005: Strict Idempotency and Signature Verification for Payment Gateways
- **Date:** 2026-11-09
- **Status:** Approved
- **Context:**
  Payment gateway webhooks and API calls (e.g. PayPal) are subject to network retries, duplicate deliveries, and malicious spoofing attacks.
- **Alternatives Considered:**
  1. *Simple Secret Tokens:* Inadequate for PayPal webhooks, which use asymmetric RSA certificates.
  2. *Asymmetric Signature Validation with Idempotency Table:* Verify digital signatures against the gateway's public keys, and record event IDs in an append-only idempotency table with unique constraints.
- **Decision:**
  Enforce PayPal webhook signature validation on all incoming webhooks and require unique `idempotency_key` attributes on capture authorization requests.
- **Consequences:**
  Eliminates double-capture vulnerabilities and rejects spoofed webhook payloads.
