# API DOCUMENTATION: INTENTGUARD REST API

> **API Version:** v1  
> **Base URL:** `/api/v1`  
> **Status:** Active / Foundational  
> **Last Updated:** 2026-11-09

---

## 1. General API Conventions

- **Protocol:** HTTPS only.
- **Content-Type:** `application/json` for all requests with bodies.
- **Accept:** `application/json` must be sent on all requests.
- **Standard Response Envelope:**
  ```json
  {
    "success": true,
    "data": { ... },
    "message": "Human readable description",
    "meta": { ... }
  }
  ```
- **Error Response Envelope:**
  ```json
  {
    "success": false,
    "error": {
      "code": "VALIDATION_FAILED",
      "message": "The given data was invalid.",
      "details": {
        "contact_email": ["The contact email has already been taken."]
      }
    }
  }
  ```
- **HTTP Status Codes:**
  - `200 OK`: Request succeeded.
  - `201 Created`: Resource successfully created.
  - `400 Bad Request`: Invalid request syntax or business rule violation.
  - `401 Unauthorized`: Missing or invalid authentication token.
  - `403 Forbidden`: Authenticated user lacks permission.
  - `404 Not Found`: Resource does not exist.
  - `422 Unprocessable Content`: Validation failure.
  - `429 Too Many Requests`: Rate limit exceeded.
  - `500 Internal Server Error`: Unhandled server exception.

---

## 2. Authentication & Authorization

- **Token Authentication:** Laravel Sanctum bearer tokens (`Authorization: Bearer <token>`).
- **Roles:**
  - `admin`: IntentGuard platform administrators (verification, policy publication, appeals).
  - `merchant`: Registered e-commerce merchants (agreement acceptance, profile, telemetry).
  - `customer`: End-consumers (purchase intent capture, dispute submission, confirmation).

### 2.1 User Login
Authenticates an administrator, merchant, or customer account and returns a Sanctum Bearer token.

- **Method:** `POST`
- **Path:** `/api/v1/auth/login`
- **Auth Required:** No
- **Request Body:**
  ```json
  {
    "email": "admin@intentguard.internal",
    "password": "IntentGuardAdminPass!2026"
  }
  ```
- **Response (200 OK):**
  ```json
  {
    "success": true,
    "data": {
      "token": "1|ntS2PXNwACVEwq7wnLAYE59XX8YUXiSObE1nXjrB94b0b5cf",
      "token_type": "Bearer",
      "user": {
        "id": 1,
        "name": "IntentGuard System Administrator",
        "email": "admin@intentguard.internal",
        "role": "admin"
      }
    },
    "message": "Authenticated successfully."
  }
  ```

### 2.2 Get Current User
- **Method:** `GET`
- **Path:** `/api/v1/auth/me`
- **Auth Required:** Yes (`Bearer <token>`)

### 2.3 User Logout
- **Method:** `POST`
- **Path:** `/api/v1/auth/logout`
- **Auth Required:** Yes (`Bearer <token>`)

---

## 3. Endpoints: Module B & C (Merchant Registration & Agreements)

### 3.1 Register Merchant
Registers a new e-commerce merchant for onboarding. Automatically generates a unique IntentGuard identifier (`IG-MCH-XXXXXXXX`) and places the merchant in `pending_review` status.

- **Method:** `POST`
- **Path:** `/api/v1/merchants/register`
- **Auth Required:** No (Public onboarding endpoint)
- **Request Body:**
  ```json
  {
    "business_name": "Apex Electronics Ltd",
    "legal_name": "Apex Electronics Limited",
    "business_registration_number": "UK-12345678",
    "tax_id": "GB987654321",
    "business_type": "corporation",
    "contact_name": "Sarah Jenkins",
    "contact_email": "compliance@apexelectronics.co.uk",
    "contact_phone": "+44 20 7946 0912",
    "website_url": "https://apexelectronics.co.uk",
    "platform_type": "shopify"
  }
  ```
- **Response (201 Created):**
  ```json
  {
    "success": true,
    "data": {
      "id": 1,
      "intentguard_id": "IG-MCH-8F2B9D1A",
      "business_name": "Apex Electronics Ltd",
      "legal_name": "Apex Electronics Limited",
      "business_registration_number": "UK-12345678",
      "tax_id": "GB987654321",
      "business_type": "corporation",
      "contact_name": "Sarah Jenkins",
      "contact_email": "compliance@apexelectronics.co.uk",
      "contact_phone": "+44 20 7946 0912",
      "website_url": "https://apexelectronics.co.uk",
      "platform_type": "shopify",
      "status": "pending_review",
      "compliance_score": 100.00,
      "agreement_accepted": false,
      "created_at": "2026-11-09T04:50:00Z"
    },
    "message": "Merchant registration submitted successfully. Please review and accept the Merchant Agreement."
  }
  ```

---

### 3.2 Get Merchant Profile
Retrieves merchant details, verification status, and agreement acceptance history.

- **Method:** `GET`
- **Path:** `/api/v1/merchants/{intentguard_id}`
- **Auth Required:** Yes (`admin` or owner `merchant`)
- **Response (200 OK):**
  ```json
  {
    "success": true,
    "data": {
      "intentguard_id": "IG-MCH-8F2B9D1A",
      "business_name": "Apex Electronics Ltd",
      "website_url": "https://apexelectronics.co.uk",
      "platform_type": "shopify",
      "status": "verified",
      "compliance_score": 98.50,
      "verified_at": "2026-11-09T05:00:00Z",
      "active_agreement": {
        "version": "1.0.0",
        "accepted_at": "2026-11-09T04:55:00Z",
        "signatory_name": "Sarah Jenkins"
      }
    }
  }
  ```

---

### 3.3 Update Merchant Verification Status (Admin Workflow)
Transitions merchant onboarding status (`under_review`, `verified`, `rejected`, `suspended`, `reinstated`) with mandatory reason logging. Pre-condition: Verification requires accepted active agreement.

- **Method:** `POST`
- **Path:** `/api/v1/merchants/{id}/status`
- **Auth Required:** Yes (`admin` role)
- **Request Body:**
  ```json
  {
    "status": "verified",
    "reason": "Business registration number verified via Companies House; store policies and identity validated."
  }
  ```
- **Response (200 OK):**
  ```json
  {
    "success": true,
    "data": {
      "intentguard_id": "IG-MCH-8F2B9D1A",
      "previous_status": "pending_review",
      "new_status": "verified",
      "verified_at": "2026-11-09T05:00:00Z",
      "actioned_by": 1
    },
    "message": "Merchant verification status updated successfully."
  }
  ```

---

### 3.4 Get Current Active Merchant Agreement
Retrieves the active, legally binding version of the IntentGuard Merchant Agreement and its SHA-256 cryptographic digest.

- **Method:** `GET`
- **Path:** `/api/v1/agreements/active`
- **Auth Required:** No (Publicly viewable contract terms)
- **Response (200 OK):**
  ```json
  {
    "success": true,
    "data": {
      "id": 1,
      "version": "1.0.0",
      "title": "IntentGuard Standard Merchant Accountability Agreement",
      "content_hash": "e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855",
      "policies": {
        "product_accuracy": "Merchants agree to guarantee that delivered items strictly conform...",
        "shipping_delivery": "Merchants must transmit valid tracking information within...",
        "substitutions": "Merchants shall not substitute any product without prior verified consent...",
        "refund_replacement": "In the event of an unapproved discrepancy, the merchant guarantees..."
      },
      "published_at": "2026-11-01T00:00:00Z"
    }
  }
  ```

---

### 3.5 Accept Merchant Agreement
Submits an authorized digital signature accepting the specified agreement version. Verifies that the client's submitted `terms_digest_snapshot` matches the server's current agreement hash.

- **Method:** `POST`
- **Path:** `/api/v1/merchants/{id}/agreements/accept`
- **Auth Required:** Yes (`merchant` owner or onboarding representative)
- **Request Body:**
  ```json
  {
    "agreement_id": 1,
    "signatory_name": "Sarah Jenkins",
    "signatory_title": "Managing Director",
    "terms_digest_snapshot": "e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855"
  }
  ```
- **Response (201 Created):**
  ```json
  {
    "success": true,
    "data": {
      "acceptance_id": 1,
      "merchant_id": 1,
      "agreement_version": "1.0.0",
      "signatory_name": "Sarah Jenkins",
      "signatory_title": "Managing Director",
      "ip_address": "192.0.2.1",
      "terms_digest_snapshot": "e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855",
      "accepted_at": "2026-11-09T04:55:00Z"
    },
    "message": "Merchant Agreement accepted successfully."
  }
  ```

---

## 4. Planned Endpoints (Modules D, F, G, I)

- `POST /api/v1/intents` — Record customer purchase intent snapshot.
- `GET /api/v1/intents/{intent_id}` — Fetch intent details and evidence digest.
- `POST /api/v1/verify` — Execute pre-capture integrity check comparing intent against checkout.
- `POST /api/v1/payments/paypal/create-order` — Create pre-authorized PayPal order.
- `POST /api/v1/payments/paypal/authorize-capture` — Trigger gateway capture contingent on decision engine verdict.
- `POST /api/v1/webhooks/paypal` — Secure webhook endpoint with digital signature validation.
