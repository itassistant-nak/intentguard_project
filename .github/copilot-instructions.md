# GitHub Copilot Instructions — IntentGuard

These instructions guide GitHub Copilot when assisting developers within the **IntentGuard** repository.

> [!IMPORTANT]
> **Primary Source of Instructions:**  
> The single primary source of truth for the project vision, technology stack, architecture, modules, security standards, and coding conventions is **[AGENTS.md](../AGENTS.md)**.

---

## Copilot Working Principles

1. **Stack & Style Adherence:**
   - **Backend:** Laravel 11+, PHP 8.2+ with strict typing (`declare(strict_types=1);`), PSR-12, Form Requests for validation, API Resources for output formatting.
   - **Frontend:** Next.js 14+ (App Router), TypeScript (strict mode, no `any`), Tailwind CSS.
   - **Database:** MySQL 8.0+ production schema, SQLite in-memory for testing. Foreign key constraints and typed Enums for all state machines.
2. **Architecture Decoupling:**
   - Keep `backend/` and `frontend/` strictly decoupled.
   - Delegate business logic to dedicated `Services` and `Engines` (`app/Services`, `app/Engines`). Never put heavy logic directly into controllers.
3. **Security by Design:**
   - Never generate placeholder code that bypasses authentication, CSRF/token verification, or input validation.
   - All critical purchase intent records, merchant agreements, and digital acceptances require SHA-256 cryptographic digests.
4. **Testing:**
   - Suggest tests alongside implementation code (PHPUnit/Pest for backend, React component tests for frontend).
