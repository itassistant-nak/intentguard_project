# DATABASE DESIGN & SCHEMA SPECIFICATIONS: INTENTGUARD

> **Document Version:** 1.0.0  
> **Target Database Engine:** MySQL 8.0+ / InnoDB (Production), SQLite (Automated Tests)  
> **Status:** Active / Foundational  
> **Last Updated:** 2026-11-09

---

## 1. Design Principles & Conventions

1. **Strict Relational Integrity:** Enforce referential integrity via foreign key constraints (`ON DELETE RESTRICT` or `ON DELETE CASCADE` where appropriate) and index all foreign keys and queryable criteria.
2. **Explicit Enums & State Tracking:** Use strictly typed PHP Enums mirrored in database columns to prevent invalid state transitions.
3. **Immutability for Audit & Provenance:** Critical records (agreements, acceptances, intent snapshots, status audit logs, decision records) must be strictly append-only.
4. **Cryptographic Digests:** Store SHA-256 digests (char(64)) for legal agreement texts, intent payloads, and evidence files.
5. **Portability:** Write Laravel migrations using schema builder primitives compatible with both MySQL and SQLite in-memory test databases.

---

## 2. Entity-Relationship Overview

```
+------------------+         +-------------------------------+
|      users       |         |           merchants           |
+------------------+         +-------------------------------+
| id (PK)          |<---+    | id (PK)                       |<----+
| name             |    |    | intentguard_id (UQ, indexed)  |     |
| email (UQ)       |    |    | business_name                 |     |
| password         |    |    | legal_name                    |     |
| role (enum)      |    |    | business_registration_number  |     |
| timestamps       |    |    | contact_email (UQ)            |     |
+------------------+    |    | website_url                   |     |
                        |    | platform_type (enum)          |     |
                        |    | status (enum)                 |     |
                        |    | compliance_score (decimal)    |     |
                        |    | timestamps, soft_deletes      |     |
                        |    +-------------------------------+     |
                        |                    |                     |
                        |                    v                     |
                        |    +-------------------------------+     |
                        |    |  merchant_status_audit_logs   |     |
                        |    +-------------------------------+     |
                        |    | id (PK)                       |     |
                        |    | merchant_id (FK) ------------>|     |
                        +----| actioned_by (FK, nullable)    |     |
                             | previous_status               |     |
                             | new_status                    |     |
                             | reason                        |     |
                             | created_at                    |     |
                             +-------------------------------+     |
                                                                   |
+-----------------------------------+                              |
|        merchant_agreements        |                              |
+-----------------------------------+                              |
| id (PK)                           |<----+                        |
| version (string, e.g. "1.0.0")    |     |                        |
| title (string)                    |     |                        |
| content_hash (char(64) SHA-256)   |     |                        |
| product_accuracy_policy (text)    |     |                        |
| shipping_delivery_policy (text)   |     |                        |
| substitution_policy (text)        |     |                        |
| refund_replacement_policy (text)  |     |                        |
| is_active (boolean)               |     |                        |
| published_at (timestamp)          |     |                        |
| timestamps                        |     |                        |
+-----------------------------------+     |                        |
                                          |                        |
                                          |                        |
+-------------------------------------------------+                |
|          merchant_agreement_acceptances         |                |
+-------------------------------------------------+                |
| id (PK)                                         |                |
| merchant_id (FK) -------------------------------+----------------+
| merchant_agreement_id (FK) ---------------------+
| signatory_name (string)                         |
| signatory_title (string)                        |
| ip_address (string)                             |
| user_agent (text)                               |
| terms_digest_snapshot (char(64) SHA-256)        |
| accepted_at (timestamp)                         |
| created_at (timestamp)                          |
+-------------------------------------------------+
```

---

## 3. Core Database Schemas (Modules A, B, C)

### 3.1 `merchants` Table
Stores registered merchant business entities, onboarding state, verification details, and compliance scores.

| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | `unsignedBigInteger` | `PK`, `auto_increment` | Internal surrogate primary key. |
| `intentguard_id` | `string(32)` | `unique`, `indexed` | Public identifier (e.g. `IG-MCH-XXXXXXXX`). |
| `business_name` | `string(191)` | `not null` | Operating trade name of the merchant. |
| `legal_name` | `string(191)` | `nullable` | Registered legal corporate entity name. |
| `business_registration_number`| `string(64)` | `unique`, `indexed` | Company/tax registration number. |
| `tax_id` | `string(64)` | `nullable` | VAT/EIN/Tax identification number. |
| `business_type` | `string(32)` | `not null` | `sole_proprietorship`, `corporation`, `llc`, `partnership`. |
| `contact_name` | `string(191)` | `not null` | Primary business contact / account owner. |
| `contact_email` | `string(191)` | `unique`, `indexed` | Verified merchant email for notices & alerts. |
| `contact_phone` | `string(32)` | `not null` | Merchant contact phone number. |
| `website_url` | `string(255)` | `not null` | Public store/e-commerce website URL. |
| `platform_type` | `string(32)` | `not null` | `shopify`, `woocommerce`, `magento`, `custom`, `paypal_direct`. |
| `status` | `string(32)` | `not null`, `indexed` | `pending_review`, `under_review`, `verified`, `rejected`, `suspended`. |
| `compliance_score` | `decimal(5,2)`| `default 100.00` | Current merchant accountability score (0.00–100.00). |
| `verification_notes` | `text` | `nullable` | Administrative review rationale or rejection notes. |
| `verified_at` | `timestamp` | `nullable` | Timestamp of successful administrator verification. |
| `verified_by` | `unsignedBigInteger` | `nullable`, `FK -> users.id` | Administrator user who performed verification. |
| `created_at` / `updated_at` | `timestamps` | `not null` | Standard Eloquent timestamps. |
| `deleted_at` | `timestamp` | `nullable` | Soft delete support. |

### 3.2 `merchant_agreements` Table
Stores versioned legal contracts, including product accuracy, shipping, substitution, and refund policies.

| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | `unsignedBigInteger` | `PK`, `auto_increment` | Primary key. |
| `version` | `string(32)` | `unique`, `indexed` | SemVer contract version (e.g. `1.0.0`, `1.1.0`). |
| `title` | `string(191)` | `not null` | Document title (e.g. "IntentGuard Merchant Fulfilment Agreement"). |
| `content_hash` | `char(64)` | `not null` | SHA-256 digest of concatenated policy texts. |
| `product_accuracy_policy` | `text` | `not null` | Mandatory product accuracy & specification terms. |
| `shipping_delivery_policy`| `text` | `not null` | Shipping timeframe, carrier tracking, and delivery terms. |
| `substitution_policy` | `text` | `not null` | Rules for customer approval of item replacements. |
| `refund_replacement_policy`| `text`| `not null` | Guaranteed return, replacement, and refund terms. |
| `is_active` | `boolean` | `default true`, `indexed` | Flags the current legally binding agreement for new signers. |
| `published_at` | `timestamp` | `not null` | Timestamp when this agreement version took legal effect. |
| `created_at` / `updated_at` | `timestamps` | `not null` | Standard timestamps. |

### 3.3 `merchant_agreement_acceptances` Table
Immutable audit trail of digital agreement executions by authorized merchant representatives.

| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | `unsignedBigInteger` | `PK`, `auto_increment` | Primary key. |
| `merchant_id` | `unsignedBigInteger` | `not null`, `FK -> merchants.id` | The merchant entity executing the agreement. |
| `merchant_agreement_id`| `unsignedBigInteger` | `not null`, `FK -> merchant_agreements.id` | The specific agreement version accepted. |
| `signatory_name` | `string(191)` | `not null` | Legal full name of individual executing acceptance. |
| `signatory_title` | `string(100)` | `not null` | Corporate title (e.g. CEO, Director, Owner). |
| `ip_address` | `string(45)` | `not null` | IP address at the moment of digital acceptance. |
| `user_agent` | `text` | `not null` | Browser client User-Agent string. |
| `terms_digest_snapshot` | `char(64)` | `not null` | Cryptographic SHA-256 digest verified at time of signing. |
| `accepted_at` | `timestamp` | `not null` | Official legal acceptance timestamp. |
| `created_at` | `timestamp` | `not null` | Timestamp record was persisted. |

### 3.4 `merchant_status_audit_logs` Table
Append-only log recording every status change of a merchant (approval, suspension, rejection, reinstatement).

| Column | Type | Constraints | Description |
| :--- | :--- | :--- | :--- |
| `id` | `unsignedBigInteger` | `PK`, `auto_increment` | Primary key. |
| `merchant_id` | `unsignedBigInteger` | `not null`, `FK -> merchants.id` | Merchant entity undergoing transition. |
| `previous_status` | `string(32)` | `not null` | Status prior to transition. |
| `new_status` | `string(32)` | `not null` | Status after transition. |
| `reason` | `text` | `not null` | Administrative or automated justification for transition. |
| `actioned_by` | `unsignedBigInteger` | `nullable`, `FK -> users.id` | User ID of administrator (null if system action). |
| `created_at` | `timestamp` | `not null` | Immutability timestamp. |

---

## 4. Future Planned Schemas (Modules D, E, F, G, H, I)

- **`purchase_intents` (Module D):** `id`, `customer_id`, `merchant_id`, `product_identity`, `specification_json`, `intended_amount`, `currency`, `shipping_conditions_json`, `allowed_substitutions_json`, `intent_hash`, `expires_at`, `status`.
- **`purchase_evidence` (Module E):** `id`, `purchase_intent_id`, `evidence_type` (product_page, checkout_screen, quote), `source_url`, `content_snapshot_blob_path`, `content_hash`, `captured_at`.
- **`verification_decisions` (Modules F, G):** `id`, `purchase_intent_id`, `checkout_payload_json`, `discrepancy_flags_json`, `confidence_score`, `verdict` (`MATCH`, `MISMATCH`, `UNCERTAIN`), `hold_payment_capture`, `decided_at`.
- **`merchant_compliance_events` (Module H):** `id`, `merchant_id`, `event_type` (`mismatch_confirmed`, `delivery_delayed`, `chargeback_received`), `score_delta`, `evidence_reference`, `appeal_status`.
- **`payment_authorizations` (Module I):** `id`, `purchase_intent_id`, `gateway` (`paypal`), `order_id`, `authorization_id`, `capture_id`, `amount`, `currency`, `status`, `idempotency_key`.
