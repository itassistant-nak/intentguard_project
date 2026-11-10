// src/services/verificationService.ts
// Module F (SPIE) & Module G (TDE) API client

import { apiRequest } from './apiClient';
import type {
  CheckoutPayload,
  ConfirmResolutionPayload,
  DecisionResolutionAuditLog,
  RejectResolutionPayload,
  VerificationDecision,
} from '@/types/verification';

export interface PaginatedVerificationDecisions {
  data: VerificationDecision[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

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

/**
 * List verification decisions currently on capture hold awaiting customer confirmation.
 */
export async function listPendingHolds(
  page = 1,
): Promise<PaginatedVerificationDecisions> {
  const response = await apiRequest<PaginatedVerificationDecisions>(
    `/verifications/pending-holds?page=${page}`,
  );
  return response.data;
}

/**
 * Customer 1-click confirmation to accept deviation and release payment capture hold.
 */
export async function confirmResolution(
  verificationId: number,
  payload: ConfirmResolutionPayload = {},
): Promise<VerificationDecision> {
  const response = await apiRequest<VerificationDecision>(
    `/verifications/${verificationId}/confirm`,
    {
      method: 'POST',
      body: JSON.stringify(payload),
    },
  );
  return response.data;
}

/**
 * Customer rejects deviation, voiding payment capture and confirming mismatch.
 */
export async function rejectResolution(
  verificationId: number,
  payload: RejectResolutionPayload,
): Promise<VerificationDecision> {
  const response = await apiRequest<VerificationDecision>(
    `/verifications/${verificationId}/reject`,
    {
      method: 'POST',
      body: JSON.stringify(payload),
    },
  );
  return response.data;
}

/**
 * Retrieve resolution audit log history for a verification decision.
 */
export async function getResolutionAuditLogs(
  verificationId: number,
): Promise<DecisionResolutionAuditLog[]> {
  const response = await apiRequest<DecisionResolutionAuditLog[]>(
    `/verifications/${verificationId}/audit-log`,
  );
  return response.data;
}
