# CLAUDE.md — Anthropic Claude Agent Guidelines

This file provides specific operational guidelines for Anthropic Claude (Claude Code, Claude desktop, and Claude-powered extensions) working on **IntentGuard**.

> [!IMPORTANT]
> **Primary Source of Instructions:**  
> The single primary source of truth for the project vision, technology stack, architecture, modules, security standards, and coding conventions is **[AGENTS.md](file:///home/user/Documents/Projects/INTENTGUARD/AGENTS.md)**.  
> You MUST consult `AGENTS.md` and `docs/CURRENT_PROGRESS.md` before starting any development work.

---

## Tool-Specific Guidance for Claude Agents

1. **Context & Project Orientation:**
   - Always read `AGENTS.md` first, followed by `docs/CURRENT_PROGRESS.md`.
   - Never assume the project starts from scratch. Inspect existing directory layouts, Laravel migrations, and Next.js components before making changes.
2. **Commands & Execution:**
   - Run backend tests with `php artisan test` inside the `backend/` directory.
   - Run frontend builds with `npm run build` or linting with `npm run lint` inside the `frontend/` directory.
   - Format PHP files according to PSR-12 standard.
3. **Documentation Maintenance:**
   - After any meaningful implementation, update `docs/CURRENT_PROGRESS.md`.
   - If an architectural trade-off or decision is made, document it in `docs/TECHNICAL_DECISIONS.md`.
4. **Security & Data Integrity:**
   - Never commit `.env` files or API secrets.
   - Enforce cryptographic validation (SHA-256 digests) and strict Form Requests for all input data.
