# AGENTS.md — IntentGuard AI Development Instructions

> **PRIMARY OPERATIONAL DIRECTIVE FOR ALL AI AGENTS**  
> This file is the single primary source of truth for all AI coding assistants, models, and development agents (including Google Gemini, Anthropic Claude, GitHub Copilot, OpenAI ChatGPT, and others) working on the **IntentGuard** project.  
> **Mandatory Rule:** Any AI agent entering this workspace MUST read and adhere to this file and referenced documentation in `docs/` before proposing, creating, or modifying any code or architecture.

---

## 1. Project Identity and Vision

**IntentGuard** is an AI-assisted transaction authorization, purchase integrity, and merchant accountability platform.

Unlike ordinary e-commerce software or reactive fraud detection tools that look only for stolen credit cards, IntentGuard solves the fundamental e-commerce mismatch problem:
1. **Purchase Integrity:** Cryptographically and semantically verifying that what a customer *actually checks out and gets charged for* strictly matches what the customer *originally intended to purchase* (specifications, price, condition, seller, fees, and shipping terms).
2. **Merchant Accountability:** Registering participating merchants into binding, digitally signed fulfilment and accuracy agreements (product accuracy, shipping, allowed substitutions, refund/replacement), monitoring fulfilment telemetry, managing customer disputes, and enforcing contractual compliance.

> [!IMPORTANT]
> **Invention and Originality Standard:**  
> Do not claim that IntentGuard is unprecedented, patentable, or novel without empirical evidence. Technical originality must be researched, benchmarked against prior art, measured, and documented in `docs/invention-research/`. AI authorization must be defensible; never delegate financial debit/capture authorization blindly to an unconstrained LLM.

---

## 2. Business Objectives

- **Eliminate Intent-Mismatch Fraud & Deceptive Checkouts:** Prevent bait-and-switch checkout manipulation, hidden fee surges, unwanted subscription roll-ins, and unauthorized item substitutions.
- **Establish a Trusted Merchant Network:** Onboard reputable e-commerce merchants who contractually guarantee accuracy, backed by automated compliance scoring and dispute resolution.
- **Pre-Capture Payment Protection:** Intercept and authorize transactions prior to final capture whenever supported by payment gateways (e.g. PayPal two-step Authorization & Capture), holding capture when mismatch or ambiguity is detected until the customer explicitly confirms.
- **Maintain Cryptographic Provenance:** Maintain immutable, timestamped audit trails of customer purchase intent and merchant agreement versions suitable for dispute adjudication.

---

## 3. Technology Stack

Keep backend and frontend decoupled as two distinct applications within the repository:

### Backend:
- **Framework:** Laravel 11+ (PHP 8.2+)
- **Architecture:** RESTful API, Domain Services, Modular Engines
- **Authentication & Authorization:** Laravel Sanctum / Gates & Policies (RBAC)
- **Database:** MySQL 8.0+ (Production & Staging), SQLite in-memory (Automated Tests)
- **Testing:** PHPUnit / Pest (Unit, Feature, Integration tests)

### Frontend:
- **Framework:** Next.js 14+ (App Router)
- **Language:** TypeScript (Strict mode)
- **Styling:** Tailwind CSS (Consistent, responsive design system)
- **State & Data Fetching:** React Hooks, Server/Client components, Fetch API / Axios

### Payment & Third-Party Integrations:
- **Primary Payment Gateway:** PayPal Developer API (Orders v2, Payments v2 Authorization & Capture, Webhooks)
- **Security & Integrity:** OpenSSL / SHA-256 cryptographic digests for evidence records and agreements

### Tooling & Environment:
- **Version Control:** Git
- **Containerization / Environment:** Docker / Docker Compose / Environment-specific `.env` configuration

---

## 4. System Architecture & Modular Structure

The codebase is organized into independent, loosely coupled modules across backend and frontend:

```
INTENTGUARD/
├── backend/                       # Laravel REST API Application
│   ├── app/
│   │   ├── Engines/               # Core Verification & Decision Engines
│   │   │   ├── SemanticIntegrityEngine/  # Attribute & Semantic evaluation
│   │   │   └── TransactionDecisionEngine/ # Match, Mismatch, Uncertain, Hold
│   │   ├── Enums/                 # Strongly typed domain enums
│   │   ├── Events/                # Domain events (e.g., MerchantRegistered, IntentRecorded)
│   │   ├── Http/
│   │   │   ├── Controllers/Api/V1/ # REST API Controllers
│   │   │   ├── Requests/          # Form Requests / Validation rules
│   │   │   └── Resources/         # JSON API transformation layers
│   │   ├── Models/                # Eloquent models & relationships
│   │   ├── Policies/              # Authorization policies (RBAC)
│   │   └── Services/              # Business logic & 3rd-party integrations (PayPal, etc.)
│   ├── database/
│   │   ├── migrations/            # Versioned relational database migrations
│   │   ├── factories/             # Model factories for testing
│   │   └── seeders/               # Database seeders
│   ├── routes/
│   │   └── api.php                # Versioned API routes (/api/v1/...)
│   └── tests/
│       ├── Feature/               # API endpoint tests, workflows
│       ├── Unit/                  # Engine logic, calculations, hash verification
│       └── Integration/           # Payment gateway, webhook simulations
│
├── frontend/                      # Next.js App Router Application
│   ├── src/
│   │   ├── app/                   # App Router pages & layouts
│   │   ├── components/            # Reusable UI primitives (Button, Modal, Input, Badge)
│   │   ├── features/              # Feature modules:
│   │   │   ├── authentication/    # Auth state, login, registration
│   │   │   ├── customers/         # Customer purchase dashboard & dispute submission
│   │   │   ├── merchants/         # Merchant onboarding, verification status, agreements
│   │   │   ├── purchase-intents/  # Intent recording & inspection
│   │   │   ├── verification/      # Integrity check summaries & mismatch alerts
│   │   │   ├── compliance/        # Compliance history, scores, dispute reviews
│   │   │   └── administration/    # Admin approval console, audit log explorer
│   │   ├── services/              # API client services
│   │   ├── hooks/                 # Custom React hooks
│   │   ├── types/                 # Shared TypeScript models and API DTOs
│   │   └── lib/                   # Utility functions, crypto helpers, formatters
│
├── docs/                          # Comprehensive Technical & Project Documentation
│   ├── CURRENT_PROGRESS.md        # Live status, completed/pending tasks
│   ├── DEVELOPMENT_ROADMAP.md     # 15-stage project roadmap
│   ├── PROJECT_VISION.md          # Vision, problem statement, commercial goals
│   ├── SYSTEM_ARCHITECTURE.md     # In-depth architectural design
│   ├── TECHNICAL_DECISIONS.md     # Architectural Decision Records (ADRs)
│   ├── DATABASE_DESIGN.md         # Relational schemas, integrity constraints
│   ├── API_DOCUMENTATION.md       # API endpoints, specifications, payloads
│   ├── architecture/              # Diagrams and flow specifications
│   ├── frameworks/                # Framework stage documentation
│   ├── invention-research/        # Prior art, technical gap, novel mechanisms
│   └── security/                  # Threat modeling, audit logs, HMAC/crypto
│
├── .github/
│   └── copilot-instructions.md    # GitHub Copilot instructions referencing this file
├── AGENTS.md                      # This master instruction file
├── GEMINI.md                      # Gemini CLI / Assistant specific entry point
├── CLAUDE.md                      # Claude / Anthropic specific entry point
└── README.md                      # Repository overview & setup instructions
```

---

## 5. Main Application Modules

- **MODULE A: User Management** — Customer, Merchant, and Administrator accounts; role-based access control (RBAC), multi-factor safety, secure tokens.
- **MODULE B: Merchant Registration** — Business verification, tax ID/business registry validation, e-commerce platform profile, approval workflows, unique IntentGuard ID (`IG-MCH-XXXXXXXX`).
- **MODULE C: Merchant Agreements & Policies** — Versioned digital contracts (Product Accuracy, Shipping & Delivery, Substitution Rules, Refund/Replacement), cryptographic digest (`content_hash`), and legally traceable digital acceptance records.
- **MODULE D: Purchase Intent Management** — Immutable capture of the customer's intended purchase: product specifications, quantity, agreed price, merchant identity, acceptable substitution boundaries, and cryptographic snapshot.
- **MODULE E: Evidence & Provenance** — Digital chain of custody: snapshot hashes of product pages, checkout screens, payment receipts, and tamper-evident audit logs.
- **MODULE F: Semantic Purchase Integrity Engine** — Hybrid verification engine:
  - *Deterministic Rule Layer:* Exact price, currency, SKU, merchant ID, quantity, and fee boundaries.
  - *Semantic Attribute Layer:* Brand, model specifications, condition (new vs refurbished), and substitution tolerances.
- **MODULE G: Transaction Decision Engine** — Outputs deterministic verdicts: `MATCH`, `MISMATCH`, `UNCERTAIN`, `REQUIRE_CUSTOMER_CONFIRMATION`. Controls capture hold where supported.
- **MODULE H: Merchant Compliance** — Fulfilment telemetry tracking, dispute adjudication, automated compliance scoring (100-point basis), contractual warnings, penalties, and appeals.
- **MODULE I: PayPal Integration** — Server-to-server Orders v2 and Payments v2; Authorization & Capture flows; webhook signature verification; idempotency and replay protection.
- **MODULE J: Audit & Security** — Tamper-resistant audit logging, rate limiting, encryption at rest and in transit, zero-trust API validation.

---

## 6. Coding Standards & Conventions

### General:
- Write clean, expressive, self-documenting code with clear types.
- Never hardcode credentials, URLs, API keys, or secrets. Always use environment variables (`.env`).
- Never delete existing functional code without explicit justification and recorded approval.
- Maintain backwards compatibility for API contracts.

### Laravel / PHP:
- Follow **PSR-12** styling guidelines and modern PHP 8.2+ practices (strict types `declare(strict_types=1);`, typed properties, enums, match expressions).
- Keep Controllers skinny: delegate business logic to dedicated `Services` and `Engines`.
- Use Form Request classes (`app/Http/Requests`) for all input validation.
- Use API Resources (`app/Http/Resources`) to format JSON responses; never expose raw Eloquent models directly to API consumers.
- Always wrap database state transitions involving multiple tables in database transactions (`DB::transaction`).

### Next.js / TypeScript:
- Strict TypeScript (`"strict": true` in `tsconfig.json`). No `any` types unless strictly unavoidable and commented.
- Use Next.js App Router idioms. Server Components by default; use `'use client'` only when interactive state, effects, or browser APIs are required.
- Organize features by domain (`src/features/<domain>`) keeping UI components, hooks, and types co-located.
- Use Tailwind CSS utility classes; avoid inline styles.

---

## 7. Database Conventions & Integrity

- Database table names must be plural snake_case (e.g. `merchants`, `merchant_agreements`, `purchase_intents`).
- Foreign keys must follow `<singular_table>_id` with foreign key constraints and indexed columns.
- State machines (e.g. merchant status, intent status, transaction verdict) must be enforced by typed PHP Enums and database string/enum columns.
- Critical financial and legal records (agreements, acceptances, verification decisions, audit logs) must be **append-only** (immutable). Never delete or overwrite audit history.
- Migrations must be reversible (`down()` method implemented) and runnable cleanly on both MySQL and SQLite.

---

## 8. Security & Cybersecurity Requirements

- **Zero-Trust Input Validation:** Validate every incoming parameter via strict Form Requests.
- **Cryptographic Provenance:** Generate SHA-256 hashes for all critical contract texts, intent payloads, and evidence snapshots.
- **Webhook Protection:** Verify webhook digital signatures (e.g. PayPal Webhook signature verification) before processing any event.
- **Replay & Idempotency Protection:** Implement idempotency keys for transaction processing and webhook ingestion.
- **Role-Based Authorization:** Every mutating endpoint must be guarded by Laravel Policies or middleware checking user role (`admin`, `merchant`, `customer`).

---

## 9. Testing & Quality Assurance Requirements

- **No Code Without Tests:** Every new endpoint, service method, and engine rule must be accompanied by automated tests.
- **Test Categories:**
  - `Unit`: Isolated engine logic, hash calculations, enum states, score algorithms.
  - `Feature`: HTTP API endpoints, validation rules, authentication, database assertions.
  - `Integration`: Payment gateway flows (mocked sandbox), webhook verification.
- **Determinism:** Tests must be deterministic and runnable via `php artisan test` in isolated environments using SQLite in-memory without requiring live third-party network access.
- **Verification Gate:** Never mark a development task as complete until all tests pass cleanly.

---

## 10. Development Methodology & Agent Rules

When you (an AI agent) are tasked with continuing development on IntentGuard:

1. **Inspect Before Changing:** Always inspect existing directory structures, migrations, models, and docs before proposing or implementing changes.
2. **Consult Progress & Roadmap:** Read `docs/CURRENT_PROGRESS.md` to identify the active framework and next task. Read `docs/DEVELOPMENT_ROADMAP.md` to understand where the task fits in the broader architecture.
3. **Plan Architecturally:** For non-trivial modifications, verify relational constraints, error handling, and security implications.
4. **Step-by-Step Execution:** Do not generate monolithic blocks of unverified code. Implement migrations -> models -> services -> controllers -> tests.
5. **Execute & Verify:** Run the automated tests (`php artisan test`, `npm run build`). Fix any regressions immediately.
6. **Update Progress & Decisions:** Update `docs/CURRENT_PROGRESS.md` and record any significant design decision in `docs/TECHNICAL_DECISIONS.md`.
7. **Transparent Reporting:** Report exact files modified, database changes made, tests executed, and clear recommendations for the next development step.

---

## 11. Current Development Stage

- **Framework:** Framework 01 — Project Foundation
- **Module Under Active Development:** Module B — Merchant Registration & Onboarding
- **Refer to:** [CURRENT_PROGRESS.md](docs/CURRENT_PROGRESS.md) for live feature states and next milestones.
