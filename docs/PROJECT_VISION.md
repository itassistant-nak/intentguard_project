# PROJECT VISION: INTENTGUARD

> **Document Version:** 1.0.0  
> **Status:** Active / Foundational  
> **Last Updated:** 2026-11-09

---

## 1. Executive Summary

**IntentGuard** is an AI-assisted transaction authorization, purchase integrity, and merchant accountability platform.

In modern e-commerce, billions of dollars are lost or disputed annually not because of stolen credit cards, but because of **transaction mismatch**: what arrives at the consumer's door or what appears on their final invoice does not match the product, specifications, price, or terms they originally agreed to purchase.

Existing fraud tools (such as 3D Secure, Stripe Radar, or PayPal Fraud Protection) look almost exclusively backward or at payment identity (e.g. *Is this the authorized cardholder?*). They do not verify the substantive content of the transaction (e.g. *Did the customer intend to buy a brand-new 256GB device with free shipping, but the merchant's checkout charged them for a refurbished 64GB device with a recurring subscription?*).

IntentGuard bridges this gap by introducing **Pre-Capture Purchase Intent Verification** and **Binding Merchant Fulfilment Governance**.

---

## 2. The Core Problem Space

### 2.1 E-Commerce Bait-and-Switch & Spec Drift
Unscrupulous or careless online merchants frequently alter items between product discovery and checkout:
- Substituting product variants (downgrading storage, material, edition, or condition).
- Adding unexpected recurring fees or membership charges during final checkout.
- Charging excessive shipping fees not disclosed on the product presentation.
- Delivering refurbished, returned, or counterfeit goods labeled as "Brand New".

### 2.2 Ineffective Dispute Mechanisms
When a mismatch occurs, current resolution mechanisms (chargebacks, PayPal claims) are slow, adversarial, and costly for all honest parties:
- Banks and payment processors handle disputes weeks after the funds have already settled.
- Chargeback fees penalize merchants indiscriminately, while customers endure lengthy bureaucratic dispute processes.
- Dispute evidence is unstructured, unauthenticated, and prone to fabrication.

### 2.3 The Merchant Credibility Gap
Legitimate, quality-focused merchants struggle to differentiate themselves from fly-by-night dropshippers. Consumers are hesitant to buy from independent e-commerce stores due to lack of trust and fear of mismatch fraud.

---

## 3. The IntentGuard Solution

IntentGuard establishes a dual-sided trust protocol:

```
+--------------------------------------------------------------------------------+
|                                 INTENTGUARD                                    |
+---------------------------------------+----------------------------------------+
|        1. PURCHASE INTEGRITY          |       2. MERCHANT ACCOUNTABILITY       |
|                                       |                                        |
| - Captures customer purchase intent   | - Merchant registration & vetting      |
| - Cryptographic evidence provenance   | - Binding digital policy agreements    |
| - Deterministic attribute comparison  | - Fulfilment & delivery tracking       |
| - Semantic spec integrity evaluation  | - Automated compliance scoring (0-100) |
| - Pre-capture payment authorization   | - Due-process dispute arbitration      |
|   (Hold / Require Customer Approval)  | - Contractual warnings & suspensions   |
+---------------------------------------+----------------------------------------+
```

### 3.1 Pre-Capture Purchase Integrity
1. **Intent Registration:** Prior to final checkout, the customer's intended purchase is captured with cryptographic immutability (product attributes, quantity, price, currency, merchant ID, shipping conditions, and allowed substitutions).
2. **Checkout Interception:** When the checkout payload is submitted to the payment gateway (e.g. PayPal), IntentGuard inspects the actual order data before payment capture.
3. **Semantic Integrity Engine:** Compares original purchase intent against actual checkout data using deterministic rules for financial terms and semantic models for specifications and substitutions.
4. **Decision Engine:**
   - **`MATCH`:** Payment capture proceeds seamlessly.
   - **`MISMATCH`:** Capture is blocked; merchant and customer are notified.
   - **`UNCERTAIN`:** Payment capture is held; customer receives an instant prompt to review and approve the discrepancy.

### 3.2 Merchant Accountability & Compliance
1. **Binding Legal Agreements:** Registered merchants digitally execute enforceable compliance policies covering:
   - Product Accuracy Guarantee.
   - Shipping & Delivery Timeframes.
   - Authorized Substitution Protocols.
   - Standardized Refund and Replacement Guarantees.
2. **Compliance Scoring:** Merchants maintain an objective, evidence-based Compliance Score (0–100) based on verified mismatch rates, delivery accuracy, and customer complaint resolutions.
3. **Due Process Enforcement:** Violations are addressed through transparent, auditable stages: warnings, evidence review, contractual penalties where permitted, temporary suspension, or removal from the network.

---

## 4. Technical Originality & Invention Principles

IntentGuard is designed as an engineered, defensible invention:
- **No Unverifiable Claims:** We do not claim IntentGuard is unprecedented without empirical prior art research and measurable benchmarks.
- **Defensible Decision Engine:** Critical financial decisions are never left to unconstrained black-box LLMs. IntentGuard uses a two-tier evaluation architecture: deterministic validation for exact attributes (prices, quantities, SKU, currency) and controlled semantic evaluation for descriptions and variant tolerances.
- **Cryptographic Provenance:** Every evidence artifact, intent record, agreement version, and decision verdict carries a cryptographic SHA-256 digest, creating an unbroken chain of custody.

---

## 5. Commercial Objectives & Target Personas

### 5.1 Consumers (Buyers)
- Experience guaranteed checkout transparency and zero risk of unexpected charges or specification downgrades.
- Possess verifiable digital receipts with instant dispute protection.

### 5.2 Registered Merchants (Sellers)
- Display the verified **IntentGuard Trust Seal**, driving higher checkout conversion rates.
- Drastically reduce high-friction chargebacks and payment gateway merchant account reserves.
- Access fair, automated dispute mediation governed by explicit contractual rules.

### 5.3 Payment Gateways & E-Commerce Platforms
- IntentGuard complements payment processors (like PayPal) by pre-filtering transaction discrepancies, reducing chargeback volumes and customer support overhead.
