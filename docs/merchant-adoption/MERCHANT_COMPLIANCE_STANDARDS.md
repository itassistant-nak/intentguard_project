# IntentGuard Merchant Compliance & Integrity Certification Standards

> **Document Version:** 1.0.0  
> **Status:** Officially Adopted Platform Standard  
> **Governance:** IntentGuard Platform Integrity Board  
> **Applies to:** All Registered Merchants (`IG-MCH-XXXXXXXX`)

---

## 1. Executive Summary

IntentGuard is an e-commerce purchase integrity and merchant accountability platform. Participating merchants enter into legally binding, cryptographically signed fulfillment agreements that guarantee what a customer selects and agrees to purchase is what is fulfilled and charged.

To maintain consumer trust and protect payment integrity, IntentGuard enforces automated, continuous telemetry monitoring backed by a dynamic 100-point compliance scoring model and pre-capture payment interception.

---

## 2. Four Foundational Merchant Agreements (v1.0.0)

Every merchant must digitally sign and accept Agreement Version `1.0.0` (SHA-256 Digest: `e3b0c442...`) during onboarding prior to platform verification.

### Policy 1: Product Specification & Accuracy SLA
- **Requirement:** The SKU, model, brand, technical specifications, and condition (e.g. Brand New in Sealed Box) delivered to the customer must strictly conform to the purchase intent captured at order creation.
- **Prohibited Conduct:** Bait-and-switch checkout manipulation, silent model downgrades, counterfeit or altered merchandise, and misleading listing descriptions.

### Policy 2: Shipping & Delivery Timeline SLA
- **Requirement:** Orders must be dispatched within the merchant's stated handling window and delivered within the agreed delivery estimate.
- **Telemetry Monitoring:** Carriers must provide verifiable tracking numbers. Failure to ship within 5 business days of the SLA without customer consent triggers a telemetry breach.

### Policy 3: Permitted vs. Prohibited Item Substitutions
- **Strict Prohibition:** Merchants may NEVER substitute condition (e.g., fulfilling refurbished or open-box goods when "new" was selected), brand, or downgraded storage/capacity.
- **Tolerated Substitutions (With Pre-Consent):** Minor cosmetic color variations or upgraded specifications (e.g., shipping 256GB instead of 128GB at no extra charge) are permitted only if the customer enabled the corresponding substitution toggle in their purchase intent.

### Policy 4: Refund & Replacement Guarantee Protocol
- **Requirement:** If a mismatch is verified by the Semantic Purchase Integrity Engine (SPIE) or an administrative dispute is resolved in the customer's favor, the merchant must fulfill an immediate replacement or full refund within 48 business hours.

---

## 3. Dynamic Compliance Scoring System (100-Point Model)

Each merchant begins onboarding with a baseline score of **100.00**.

### 3.1 Telemetry Deductions & Recovery Curve

| Event Code | Description | Score Delta | Immediate Operational Impact |
| :--- | :--- | :--- | :--- |
| `MISMATCH_CONFIRMED` | Confirmed checkout parameter drift or item mismatch | **-15.00** | Telemetry recorded; dispute flag raised |
| `UNAUTHORIZED_SUBSTITUTION` | Shipping refurbished or altered goods without consent | **-10.00** | Telemetry recorded; customer alerted |
| `SHIPPING_DELAY_BREACH` | Dispatch failure beyond guaranteed handling SLA | **-5.00** | Telemetry recorded |
| `CHARGEBACK_RECEIVED` | Unresolved customer payment chargeback at gateway | **-20.00** | High severity audit logged |
| `POSITIVE_FULFILMENT_RECOVERY` | Consecutive 50 verified clean transactions | **+2.00** | Gradual score rehabilitation (cap: 100.00) |

### 3.2 Contractual Tier Status & Interception Thresholds

```
[ 100.00 ──────── 85.00 ] : GOLD SEAL CERTIFIED (Immediate Capture, Priority Badge)
[  84.99 ──────── 70.00 ] : VERIFIED MERCHANT   (Standard SPIE Interception)
[  69.99 ──────── 50.00 ] : UNDER REVIEW        (MANDATORY PRE-CAPTURE HOLD ON ALL ORDERS)
[  < 50.00              ] : SUSPENDED           (Gateway Capture Voided, Trust Seal Revoked)
```

1. **Tier 1: Gold Seal (Score 85.00 – 100.00):**
   - High-trust standing.
   - Clean match transactions are captured immediately by the Transaction Decision Engine (TDE).
   - Display of the Gold IntentGuard Trust Seal on storefront checkouts.

2. **Tier 2: Verified (Score 70.00 – 84.99):**
   - Standard operational standing.
   - Exact matches captured immediately; any attribute warning triggers pre-capture hold.

3. **Tier 3: Under Review (Score 50.00 – 69.99):**
   - **Mandatory Escalation:** The Transaction Decision Engine escalates ALL transactions—even clean matches—to `CAPTURE_HOLD`.
   - The customer must explicitly confirm the transaction before funds can be captured.
   - Merchant receives formal warning and must submit a mitigation plan within 14 calendar days.

4. **Tier 4: Suspended (Score < 50.00):**
   - Immediate revocation of IntentGuard API credentials.
   - All pending authorizations are voided.
   - Trust Seal badge displays "Suspended" in red.

---

## 4. Due Process & Appeal Protocol

Merchants maintain the contractual right to appeal telemetry penalties within 30 days of occurrence:

1. **Submission:** Merchant submits formal appeal via `POST /api/v1/compliance/events/{id}/appeal` with supporting documentary evidence (carrier manifest, customer waiver, or supplier invoice).
2. **Review:** IntentGuard Platform Administrators adjudicate the appeal within 5 business days.
3. **Reversal:** Upon approval, the penalty score delta is immediately refunded to the merchant's compliance score. If the revised score crosses above 70.00%, status is restored to `verified`.
