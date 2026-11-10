// src/services/paymentService.ts
// Module I: PayPal Gateway Integration API Client

import { apiRequest } from './apiClient';
import type {
  AuthorizePayPalOrderPayload,
  CaptureHeldPaymentPayload,
  CreatePayPalOrderPayload,
  PaymentAuthorization,
  VoidHeldPaymentPayload,
} from '@/types/payment';

/**
 * Initializes a PayPal Orders v2 order with intent="AUTHORIZE".
 */
export async function createPayPalOrder(
  intentId: number,
  payload: CreatePayPalOrderPayload,
): Promise<PaymentAuthorization> {
  const response = await apiRequest<PaymentAuthorization>(
    `/intents/${intentId}/paypal/order`,
    {
      method: 'POST',
      body: JSON.stringify(payload),
    },
  );
  return response.data;
}

/**
 * Authorizes an approved PayPal order and evaluates checkout with IntentGuard TDE.
 * Intercepts capture based on verification verdict:
 * - MATCH: Captures immediately
 * - REQUIRE_CUSTOMER_CONFIRMATION / UNCERTAIN: Places capture on hold
 * - MISMATCH: Voids authorization immediately
 */
export async function authorizePayPalOrder(
  intentId: number,
  payload: AuthorizePayPalOrderPayload,
): Promise<PaymentAuthorization> {
  const response = await apiRequest<PaymentAuthorization>(
    `/intents/${intentId}/paypal/authorize`,
    {
      method: 'POST',
      body: JSON.stringify(payload),
    },
  );
  return response.data;
}

/**
 * Retrieves details, status, and verification resolution for a payment authorization.
 */
export async function getPayment(paymentId: number): Promise<PaymentAuthorization> {
  const response = await apiRequest<PaymentAuthorization>(`/payments/${paymentId}`);
  return response.data;
}

/**
 * Releases capture hold for an authorized payment upon customer/admin confirmation.
 */
export async function captureHeldPayment(
  paymentId: number,
  payload: CaptureHeldPaymentPayload = {},
): Promise<PaymentAuthorization> {
  const response = await apiRequest<PaymentAuthorization>(
    `/payments/${paymentId}/capture-held`,
    {
      method: 'POST',
      body: JSON.stringify(payload),
    },
  );
  return response.data;
}

/**
 * Voids an authorized payment for a held transaction upon customer rejection.
 */
export async function voidHeldPayment(
  paymentId: number,
  payload: VoidHeldPaymentPayload,
): Promise<PaymentAuthorization> {
  const response = await apiRequest<PaymentAuthorization>(
    `/payments/${paymentId}/void-held`,
    {
      method: 'POST',
      body: JSON.stringify(payload),
    },
  );
  return response.data;
}

export const paymentService = {
  createPayPalOrder,
  authorizePayPalOrder,
  getPayment,
  captureHeldPayment,
  voidHeldPayment,
};
