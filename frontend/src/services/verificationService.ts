// src/services/verificationService.ts
// Module F: Semantic Purchase Integrity Engine (SPIE) API client

import { apiRequest } from './apiClient';
import type {
  CheckoutPayload,
  VerificationDecision,
} from '@/types/verification';

/**
 * Verify a merchant checkout payload against an established purchase intent.
 * Runs deterministic scalar and semantic attribute checks, returning a verification verdict.
 */
export async function verifyCheckout(
  intentId: number,
  payload: CheckoutPayload,
): Promise<VerificationDecision> {
  const response = await apiRequest<VerificationDecision>(
    `/intents/${intentId}/verify`,
    {
      method: 'POST',
      body: JSON.stringify(payload),
    },
  );
  return response.data;
}

/**
 * List all historical verification decisions for a given purchase intent.
 */
export async function listVerifications(
  intentId: number,
): Promise<VerificationDecision[]> {
  const response = await apiRequest<VerificationDecision[]>(
    `/intents/${intentId}/verifications`,
  );
  return response.data;
}

/**
 * Retrieve a single verification decision with full discrepancy analysis.
 */
export async function getVerification(
  verificationId: number,
): Promise<VerificationDecision> {
  const response = await apiRequest<VerificationDecision>(
    `/verifications/${verificationId}`,
  );
  return response.data;
}
