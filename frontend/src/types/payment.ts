// src/types/payment.ts
// Module I: PayPal Gateway Integration (Orders v2 & Payments v2)

import type { CheckoutPayload, VerificationDecision } from './verification';

export type PaymentAuthorizationStatus =
  | 'created'
  | 'authorized'
  | 'captured'
  | 'voided'
  | 'held';

export interface PaymentAuthorization {
  id: number;
  purchase_intent_id: number;
  verification_decision_id?: number | null;
  gateway: string;
  order_id: string;
  authorization_id?: string | null;
  capture_id?: string | null;
  amount_cents: number;
  amount: string;
  currency: string;
  status: PaymentAuthorizationStatus;
  status_label: string;
  is_captured: boolean;
  is_held: boolean;
  is_voided: boolean;
  customer_payer_id?: string | null;
  idempotency_key: string;
  authorized_at?: string | null;
  captured_at?: string | null;
  voided_at?: string | null;
  verification_decision?: VerificationDecision | null;
  created_at: string;
  updated_at: string;
}

export interface CreatePayPalOrderPayload {
  idempotency_key: string;
  return_url?: string;
  cancel_url?: string;
}

export interface AuthorizePayPalOrderPayload {
  order_id: string;
  checkout_payload: CheckoutPayload;
  idempotency_key?: string;
}

export interface CaptureHeldPaymentPayload {
  reason?: string;
}

export interface VoidHeldPaymentPayload {
  reason: string;
}
