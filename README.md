# IntentGuard

> **AI-Assisted Transaction Authorization, Purchase Integrity & Merchant Accountability Platform**

IntentGuard is an advanced fintech and transaction governance platform designed to protect consumers and online businesses from transaction mismatches, deceptive checkouts, and fulfilment breaches.

---

## 1. Overview

Unlike legacy fraud tools that focus almost exclusively on identity theft and stolen card detection, IntentGuard solves the **Purchase Intent Mismatch** problem:

1. **Purchase Integrity:** Verifying that the items, prices, specifications, fees, and conditions captured during checkout match what the customer originally intended to purchase.
2. **Merchant Accountability:** Governing registered e-commerce merchants under legally binding, digitally signed fulfilment and accuracy policies, backed by automated compliance scoring and dispute resolution.
3. **Pre-Capture Protection:** Intercepting payment captures (via gateway two-step authorization models, such as PayPal Authorize & Capture) when discrepancies or unconfirmed substitutions are detected.

---

## 2. Portable AI Development Context System

IntentGuard uses an AI-agnostic development context architecture so that any AI coding assistant or engineer can step in and continue development seamlessly without loss of context:

- **[`AGENTS.md`](file:///home/user/Documents/Projects/INTENTGUARD/AGENTS.md)** — The primary master instruction set for all AI agents.
- **[`GEMINI.md`](file:///home/user/Documents/Projects/INTENTGUARD/GEMINI.md)** — Tool-specific instructions for Google Gemini / Antigravity agents.
- **[`CLAUDE.md`](file:///home/user/Documents/Projects/INTENTGUARD/CLAUDE.md)** — Tool-specific instructions for Anthropic Claude agents.
- **[`.github/copilot-instructions.md`](file:///home/user/Documents/Projects/INTENTGUARD/.github/copilot-instructions.md)** — Instructions for GitHub Copilot.
- **[`docs/`](file:///home/user/Documents/Projects/INTENTGUARD/docs/)** — Detailed documentation repository (Architecture, Database Design, Roadmap, Progress, Technical Decisions, APIs).

---

## 3. Technology Stack

- **Backend:** Laravel 11 (PHP 8.2+), RESTful API, Sanctum, Domain Engines
- **Frontend:** Next.js 14 (App Router), TypeScript, Tailwind CSS
- **Database:** MySQL 8.0 (Production / Staging), SQLite in-memory (Unit & Feature Testing)
- **Payment Integration:** PayPal Developer API (Orders v2, Payments v2 Authorization & Capture, Webhooks)
- **Testing:** PHPUnit / Pest (Backend), Jest / React Testing Library (Frontend)

---

## 4. Repository Structure

```
INTENTGUARD/
├── backend/                       # Laravel REST API Application
├── frontend/                      # Next.js App Router Web Application
├── docs/                          # Comprehensive Technical & Project Documentation
│   ├── CURRENT_PROGRESS.md        # Current framework, module, completed & pending tasks
│   ├── DEVELOPMENT_ROADMAP.md     # 15-stage comprehensive project roadmap
│   ├── PROJECT_VISION.md          # Business vision, customer problem & objectives
│   ├── SYSTEM_ARCHITECTURE.md     # Architectural blueprints & component communication
│   ├── TECHNICAL_DECISIONS.md     # Architectural Decision Records (ADRs)
│   ├── DATABASE_DESIGN.md         # Relational schemas, models, and integrity rules
│   └── API_DOCUMENTATION.md       # API endpoint contracts and specifications
├── .github/
│   └── copilot-instructions.md    # GitHub Copilot directives
├── AGENTS.md                      # Primary development instructions for AI assistants
├── GEMINI.md                      # Gemini specific guidelines
├── CLAUDE.md                      # Claude specific guidelines
└── README.md                      # This document
```

---

## 5. Development Workflow & Rules

1. Before starting any work, consult **[`AGENTS.md`](file:///home/user/Documents/Projects/INTENTGUARD/AGENTS.md)** and **[`docs/CURRENT_PROGRESS.md`](file:///home/user/Documents/Projects/INTENTGUARD/docs/CURRENT_PROGRESS.md)**.
2. Maintain clean separation between backend and frontend applications.
3. Every new feature, endpoint, or engine rule requires automated tests.
4. Document all architectural decisions in **[`docs/TECHNICAL_DECISIONS.md`](file:///home/user/Documents/Projects/INTENTGUARD/docs/TECHNICAL_DECISIONS.md)**.
5. Update **[`docs/CURRENT_PROGRESS.md`](file:///home/user/Documents/Projects/INTENTGUARD/docs/CURRENT_PROGRESS.md)** after each significant development session.

---

## 6. License & Confidentiality

Proprietary and Confidential. Unauthorized copying, distribution, or decompilation is strictly prohibited.
