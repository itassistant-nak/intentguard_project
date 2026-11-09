# GEMINI.md — Google Gemini & Antigravity Agent Guidelines

This file provides specific operational guidelines for Google Gemini models, the Google Antigravity CLI/IDE, and Gemini Code Assist working on **IntentGuard**.

> [!IMPORTANT]
> **Primary Source of Instructions:**  
> The single primary source of truth for the project vision, technology stack, architecture, modules, security standards, and coding conventions is **[AGENTS.md](file:///home/user/Documents/Projects/INTENTGUARD/AGENTS.md)**.  
> You MUST consult `AGENTS.md` and `docs/CURRENT_PROGRESS.md` before starting any development work.

---

## Tool-Specific Guidance for Gemini / Antigravity Agents

1. **Adhere to Planning Mode & Workflows:**
   - Always check `docs/CURRENT_PROGRESS.md` to identify the active framework and next tasks.
   - When introducing architectural changes, consult `docs/SYSTEM_ARCHITECTURE.md` and record decisions in `docs/TECHNICAL_DECISIONS.md`.
2. **Terminal Sandbox Guidelines:**
   - Execute internal commands (file management, local PHP unit testing, linting) in Standard Sandbox Mode (`BypassSandbox: false`).
   - If downloading external dependencies via Composer or npm requires network connectivity that is isolated in standard mode, explain the need clearly and use `BypassSandbox: true` strictly for that specific network operation.
3. **Artifact Usage:**
   - Use Markdown artifacts for structured plans, multi-file architectural breakdowns, and comprehensive walkthroughs.
   - Do not duplicate content between artifacts and chat responses. Point the user to the relevant documents.
4. **Code Quality & Testing:**
   - Run tests (`php artisan test`, `npm run build`) before declaring any task finished.
   - Never mock test results or assume code works without executing the test runner.
   - Never write code outside the project root (`/home/user/Documents/Projects/INTENTGUARD/`).
