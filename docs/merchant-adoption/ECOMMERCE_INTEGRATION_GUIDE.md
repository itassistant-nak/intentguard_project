# IntentGuard E-Commerce Developer Integration Guide

> **Document Version:** 1.0.0  
> **Target Audience:** Storefront Developers, E-Commerce Platform Integrators, Payment Engineers  
> **API Version:** REST API v1 (`/api/v1`)

---

## 1. Architectural Flow Overview

IntentGuard sits between your e-commerce storefront and your payment gateway (e.g., PayPal Developer Orders v2 / Payments v2):

```
[ Customer Cart ]
       │
       ▼
1. Capture Intent (POST /api/v1/intents) ─────────► [ IntentGuard Database ]
       │                                                     │
       ▼                                                     │ (Stores SHA-256 Intent Hash)
2. PayPal 2-Step Authorization (intent: AUTHORIZE)            │
       │                                                     │
       ▼                                                     ▼
3. Pre-Capture Verification (POST /api/v1/intents/{id}/paypal/authorize)
       │
       ├───► Clean MATCH: Immediate Capture (POST /v2/payments/.../capture)
       │
       ├───► Discrepancy / Uncertainty: CAPTURE_HOLD (Funds held uncaptured)
       │                                  │
       │                                  ▼
       │                     Customer 1-Click Confirmation
       │                     (POST /api/v1/payments/{id}/capture-held)
       │
       └───► Severe Mismatch: Immediate Void (POST /v2/payments/.../void)
```

---

## 2. Quick-Start API Integration

### Step 1: Record Purchase Intent
When the customer proceeds to checkout, capture what they intend to buy before payment authorization:

```bash
POST /api/v1/intents HTTP/1.1
Host: api.intentguard.internal
Authorization: Bearer <customer_sanctum_token>
Content-Type: application/json

{
  "merchant_id": 1,
  "product_name": "Sony WH-1000XM5 Wireless Headphones",
  "product_sku": "SONY-WH1000XM5-BLK",
  "product_url": "https://store.apex.internal/products/sony-wh1000xm5",
  "unit_price_cents": 34999,
  "currency": "USD",
  "quantity": 1,
  "shipping_conditions_json": {
    "shipping_cost_cents": 0,
    "method": "Standard 3-Day Ground"
  },
  "specification_json": {
    "brand": "Sony",
    "model": "WH-1000XM5",
    "condition": "new",
    "colour": "Black"
  },
  "allowed_substitutions_json": {
    "colour_variants": true,
    "refurbished_ok": false
  }
}
```

Response returns the generated unique `id` and deterministic `intent_hash` (SHA-256).

### Step 2: Initialize PayPal Two-Step Order
Initialize the payment authorization linked to the purchase intent:

```bash
POST /api/v1/intents/{id}/paypal/order HTTP/1.1
Content-Type: application/json
Idempotency-Key: idemp-uuid-123456789

{
  "return_url": "https://store.apex.internal/checkout/complete",
  "cancel_url": "https://store.apex.internal/checkout/cancel"
}
```

This instructs PayPal to create an Order with `intent: "AUTHORIZE"`.

### Step 3: Authorize & Intercept Checkout
When the customer approves on PayPal, submit the order ID to IntentGuard:

```bash
POST /api/v1/intents/{id}/paypal/authorize HTTP/1.1
Content-Type: application/json

{
  "paypal_order_id": "ORDER-9876543210"
}
```

**IntentGuard Decision Engine Verdicts:**
- **`CAPTURED`:** The checkout matched the intent perfectly. Payment capture completed immediately.
- **`HELD`:** Discrepancy detected (e.g. price surge, unexpected shipping fee, condition downgrade). The authorization is preserved, but **funds are NOT captured**. The customer is shown an IntentGuard Confirmation dialog to review and approve or reject.
- **`VOIDED`:** Severe violation detected (e.g. completely different merchant ID or currency). Authorization was immediately cancelled.

---

## 3. Platform-Specific Integration

### 3.1 Shopify Storefronts
For Shopify Plus / Checkout Extensibility:

1. **Checkout UI Extension:** In your checkout extension, call the IntentGuard Intent API in the `shipping_address_completed` step.
2. **Trust Seal Badge:** Add the IntentGuard Trust Badge above the payment step:
```html
<div class="intentguard-seal-badge" data-merchant-id="IG-MCH-XXXXXXXX"></div>
<script src="https://cdn.intentguard.internal/widget/intentguard-seal.js" async></script>
```

### 3.2 WooCommerce (WordPress)
1. Add the IntentGuard WooCommerce helper to `functions.php`:
```php
add_action('woocommerce_review_order_after_payment', function() {
    echo '<div style="margin-top: 15px;"><intentguard-seal merchant-id="IG-MCH-APEX0001"></intentguard-seal></div>';
    echo '<script src="https://cdn.intentguard.internal/widget/intentguard-seal.js" async></script>';
});
```

### 3.3 Headless Next.js / React Checkout
Install the IntentGuard React component or copy `IntentGuardSeal.tsx`:
```tsx
import { IntentGuardSeal } from '@/components/IntentGuardSeal';

export function CheckoutPage() {
  return (
    <div className="checkout-container">
      {/* Checkout Form */}
      <IntentGuardSeal merchantId="IG-MCH-APEX0001" variant="standard" />
    </div>
  );
}
```

---

## 4. Production Security Checklist

- [x] **Store Secrets in Environment:** Never commit `PAYPAL_CLIENT_SECRET` or Sanctum bearer tokens.
- [x] **Enforce Idempotency:** Always send a unique `Idempotency-Key` HTTP header with order creation requests.
- [x] **Verify Webhooks:** Enable PayPal Webhook signature verification in IntentGuard settings to prevent replay attacks.
- [x] **Check SSL / TLS:** Ensure all communication uses TLS 1.3.
