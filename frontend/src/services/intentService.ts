// src/services/intentService.ts
// Module D: Purchase Intent API service

import { apiRequest } from './apiClient';
import type {
  PurchaseIntent,
  RecordIntentPayload,
  IntegrityCheckResult,
} from '@/types/intent';

export interface PaginatedIntents {
  data: PurchaseIntent[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

/**
 * Record a new purchase intent.
 * Returns the created intent with its cryptographic hash.
 */
export async function recordIntent(
  payload: RecordIntentPayload,
): Promise<PurchaseIntent> {
  const response = await apiRequest<PurchaseIntent>('/intents', {
    method: 'POST',
    body: JSON.stringify(payload),
  });
  return response.data;
}

/**
 * Retrieve a purchase intent by its internal numeric ID or intent_hash.
 */
export async function getIntent(
  identifier: string | number,
): Promise<PurchaseIntent> {
  const response = await apiRequest<PurchaseIntent>(`/intents/${identifier}`);
  return response.data;
}

/**
 * List the authenticated customer's purchase intents (paginated).
 */
export async function listIntents(page = 1): Promise<PaginatedIntents> {
  const response = await apiRequest<PaginatedIntents>(`/intents?page=${page}`);
  return response.data;
}

/**
 * Cancel an active purchase intent by its internal ID.
 */
export async function cancelIntent(id: number): Promise<PurchaseIntent> {
  const response = await apiRequest<PurchaseIntent>(`/intents/${id}/cancel`, {
    method: 'POST',
  });
  return response.data;
}

/**
 * Verify the cryptographic integrity of a stored intent.
 * Detects any post-recording tampering of the payload.
 */
export async function verifyIntentIntegrity(
  id: number,
): Promise<IntegrityCheckResult> {
  const response = await apiRequest<IntegrityCheckResult>(
    `/intents/${id}/verify-integrity`,
  );
  return response.data;
}

/**
 * Format a price in cents to a human-readable string with currency symbol.
 * e.g. formatPrice(119900, 'USD') → '$1,199.00'
 */
export function formatPrice(cents: number, currency = 'USD'): string {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency,
    minimumFractionDigits: 2,
  }).format(cents / 100);
}
