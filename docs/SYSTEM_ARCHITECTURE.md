# SYSTEM ARCHITECTURE: INTENTGUARD

> **Document Version:** 1.0.0  
> **Status:** Active / Foundational  
> **Last Updated:** 2026-11-09

---

## 1. Architectural Overview

IntentGuard is architected as a decoupled, multi-tier system composed of an API-driven backend, a modern single-page frontend application, a structured relational database, dedicated evaluation engines, and secure external payment/e-commerce integrations.

```
                           +------------------------------------------------+
                           |            CLIENT BROWSER / CONSUMER           |
                           +-----------------------+------------------------+
                                                   |
                                                   v
                           +------------------------------------------------+
                           |            NEXT.JS FRONTEND (App Router)       |
                           |   TypeScript, Tailwind CSS, Feature Modules    |
                           +-----------------------+------------------------+
                                                   | HTTPS / JSON REST API
                                                   v
+-------------------------------------------------------------------------------------------------------+
|                                    LARAVEL BACKEND (REST API)                                         |
|                                                                                                       |
|  +-------------------------------------------------------------------------------------------------+  |
|  |                                HTTP / CONTROLLER & ROUTING LAYER                                |  |
|  |      /api/v1/auth    /api/v1/merchants    /api/v1/intents    /api/v1/verify    /api/v1/webhooks     |  |
|  +-------------------------------------------------------------------------------------------------+  |
|                                                  |                                                    |
|                                                  v                                                    |
|  +-------------------------------------------------------------------------------------------------+  |
|  |                            DOMAIN SERVICE & BUSINESS LOGIC LAYER                                |  |
|  |   - MerchantService                 - PurchaseIntentService         - MerchantComplianceService    |  |
|  |   - MerchantApprovalService         - EvidenceProvenanceService     - AuditLoggingService          |  |
|  |   - MerchantAgreementService        - PayPalPaymentService          - CryptographicEvidenceService |  |
|  +-------------------------------------------------------------------------------------------------+  |
|                                                  |                                                    |
|                   +------------------------------+------------------------------+                     |
|                   v                                                             v                     |
|  +---------------------------------+                           +---------------------------------+    |
|  |   SEMANTIC PURCHASE INTEGRITY   |                           |       TRANSACTION DECISION      |    |
|  |             ENGINE              |                           |              ENGINE             |    |
|  |  - Deterministic Attribute Comp |-------------------------->|  - Match Verdict                |    |
|  |  - Semantic Tolerance Validator |                           |  - Mismatch Verdict             |    |
|  |  - Substitution Rule Evaluator  |                           |  - Hold / Customer Confirm      |    |
|  +---------------------------------+                           +---------------------------------+    |
+-------------------------------------------------------------------------------------------------------+
                |                                                                           |
                v                                                                           v
+------------------------------------+                                     +------------------------------------+
|          DATABASE LAYER            |                                     |        EXTERNAL INTEGRATIONS       |
|  MySQL 8.0 (Production / Staging)  |                                     |  - PayPal Developer API (v2)       |
|  SQLite (In-memory Automated Test) |                                     |    (Authorize & Capture, Webhooks) |
|  - Relational Schema & Constraints |                                     |  - E-Commerce Webhooks (Shopify,   |
|  - Immutable Audit & Provenance    |                                     |    WooCommerce, Custom stores)     |
+------------------------------------+                                     +------------------------------------+
```

---

## 2. Decoupled Subsystems

### 2.1 Backend: Laravel API Application (`backend/`)
- **Responsibilities:**
  - Expose versioned RESTful JSON API endpoints (`/api/v1/...`).
  - Enforce user authentication (Laravel Sanctum) and role-based access control (RBAC Policies).
  - Execute database transactions with strict consistency.
  - Coordinate the **Semantic Purchase Integrity Engine** and **Transaction Decision Engine**.
  - Process asynchronous jobs (e.g. webhook ingestion, notifications, audit logging).
  - Integrate with PayPal Orders and Payments API.
- **Key Directories:**
  - `app/Engines/`: Domain-specific analysis and decision engines.
  - `app/Services/`: Reusable, single-responsibility domain services.
  - `app/Http/Controllers/Api/V1/`: Thin API controllers.
  - `app/Http/Requests/`: Strict validation and data sanitization.
  - `app/Http/Resources/`: JSON representation transforms.
  - `app/Models/`: Eloquent models with typed properties and relationships.

### 2.2 Frontend: Next.js Web Application (`frontend/`)
- **Responsibilities:**
  - Provide fast, responsive, accessible dashboards for Customers, Merchants, and Administrators.
  - Present the Merchant Onboarding & Agreement Signing workflow.
  - Provide real-time transaction verification feedback and mismatch alerts.
  - Offer compliance and dispute adjudication interfaces.
- **Key Directories:**
  - `src/app/`: App Router route pages, layouts, and route handlers.
  - `src/features/`: Feature-grouped modules (`merchants`, `purchase-intents`, `verification`, `compliance`, `administration`).
  - `src/components/`: Reusable UI components styled with Tailwind CSS.
  - `src/services/`: Typed API client wrappers.
  - `src/types/`: Domain DTOs and interface definitions.

### 2.3 Database Layer: MySQL 8.0 (`database/`)
- **Responsibilities:**
  - Enforce referential integrity through foreign keys, indices, and constraints.
  - Provide ACID transactions for checkout authorizations and status transitions.
  - Maintain append-only, tamper-evident audit tables for agreements, intents, and verification decisions.
  - Support seamless SQLite in-memory execution for rapid, hermetic automated test suites.

---

## 3. Core Engine Architecture

### 3.1 Module F: Semantic Purchase Integrity Engine
The Integrity Engine evaluates discrepancies between the Customer's **Purchase Intent Record** ($I$) and the Merchant's **Actual Checkout Payload** ($C$):

$$\text{Verdict} = \mathcal{E}(I, C)$$

The engine operates in two decoupled stages:
1. **Deterministic Attribute Comparison:**
   - Evaluates exact numeric and scalar fields: Total Price, Currency Code, Unit Quantities, Line Item SKUs, Merchant Identifier, Shipping Fees, Tax Charges.
   - Any financial discrepancy exceeding zero tolerance immediately flags a mismatch or triggers hold requirements.
2. **Semantic & Specification Comparison:**
   - Evaluates descriptive attributes: Product Title, Brand, Model Variant, Storage/Memory/Size specifications, Product Condition (e.g., "Brand New" vs "Refurbished").
   - Evaluates customer-approved substitution criteria (e.g. *customer accepts Black or Space Gray color variants, but rejects storage deviations*).
   - Produces a normalized confidence score and categorized discrepancy flags.

### 3.2 Module G: Transaction Decision Engine
The Decision Engine maps the Integrity Engine's output to an actionable financial authorization verdict:

| Verdict Code | Description | Payment Gateway Action | Customer Interaction |
| :--- | :--- | :--- | :--- |
| **`MATCH`** | Intent matches checkout within all exact and approved tolerances. | Execute Payment Capture immediately. | Transparent confirmation receipt. |
| **`MISMATCH`** | Critical attribute violation (price increase, forbidden spec downgrade, unapproved fee). | Void or block Payment Capture. Prevent unauthorized charge. | Alert customer with exact mismatch details; notify merchant. |
| **`UNCERTAIN`** | Minor or ambiguous specification variation not covered by explicit policy. | Hold Payment Capture (within gateway expiration window). | Send high-priority confirmation prompt to customer for 1-click approve or cancel. |
| **`HOLD_PENDING`** | Merchant fulfilment delay or compliance check required. | Maintain authorization hold. | Provide status tracking in customer dashboard. |

---

## 4. Payment Gateway Integration Architecture (Module I: PayPal)

IntentGuard interacts with payment gateways utilizing a **Two-Step Authorization & Capture** model:

```
[Customer Browser]               [IntentGuard API]               [Merchant Store]               [PayPal API]
       |                                |                               |                            |
       | 1. Record Purchase Intent      |                               |                            |
       |------------------------------->| (Save Intent + SHA-256 Hash)  |                            |
       |                                |                               |                            |
       | 2. Proceed to Merchant Checkout                                |                            |
       |--------------------------------------------------------------->|                            |
       |                                                                | 3. Create PayPal Order     |
       |                                                                | (intent="AUTHORIZE")       |
       |                                                                |--------------------------->|
       |                                                                |<---------------------------|
       | 4. Customer Authorizes Payment in PayPal Modal                 | (Returns Order ID)         |
       |<===========================================================================================>|
       |                                                                |                            |
       |                                | 5. Transmit Checkout Payload  |                            |
       |                                |<------------------------------|                            |
       |                                |                                                            |
       |                                | 6. Run Semantic Integrity Engine                           |
       |                                |    & Transaction Decision Engine                           |
       |                                |                                                            |
       |                  [Verdict: MATCH]                                                           |
       |                                | 7. Authorize Capture                                       |
       |                                |----------------------------------------------------------->|
       |                                |                                                            |
       |                  [Verdict: MISMATCH / UNCERTAIN]                                            |
       |                                | 8. Hold Capture / Request Customer Approval                |
       |<-------------------------------|                                                            |
```

> [!IMPORTANT]
> **Gateway Authority Constraint:**  
> IntentGuard only intervenes in payment capture when integrated through authorization-capable workflows where the merchant or platform routes capture authorization through IntentGuard's decision engine. IntentGuard never claims arbitrary interception of standard closed third-party payments.

---

## 5. Security & Cryptographic Provenance (Module J)

1. **Cryptographic Hashing:**
   - Every Merchant Agreement version is digested with SHA-256 (`content_hash`).
   - Every Merchant Agreement Acceptance records a snapshot hash of the terms signed by the authorized signatory.
   - Every Purchase Intent is hashed upon creation to guarantee tamper-evidence.
2. **Webhook Verification:**
   - All inbound webhooks (PayPal, e-commerce stores) must pass cryptographic signature verification (e.g. PayPal Webhook certificate and signature headers) before payload processing.
3. **Audit Immutability:**
   - Audit logs (`merchant_status_audit_logs`, `decision_audit_logs`) use append-only database designs without edit or delete routes.
