// src/services/complianceService.ts
// Module H: Merchant Compliance Telemetry & Customer Dispute Adjudication

import { apiRequest } from './apiClient';
import type {
  AdjudicateAppealPayload,
  CustomerDispute,
  DisputeFilterParams,
  MerchantComplianceEvent,
  OpenDisputePayload,
  RecordComplianceEventPayload,
  ResolveDisputePayload,
  RespondDisputePayload,
  SubmitAppealPayload,
} from '@/types/compliance';

/**
 * List compliance telemetry events and scoring history for a merchant.
 */
export async function listComplianceEvents(
  merchantId: number | string,
  page: number = 1,
): Promise<MerchantComplianceEvent[]> {
  const response = await apiRequest<MerchantComplianceEvent[]>(
    `/merchants/${merchantId}/compliance/events?page=${page}`,
  );
  return response.data;
}

/**
 * Record a new compliance event against a merchant.
 */
export async function recordComplianceEvent(
  merchantId: number | string,
  payload: RecordComplianceEventPayload,
): Promise<MerchantComplianceEvent> {
  const response = await apiRequest<MerchantComplianceEvent>(
    `/merchants/${merchantId}/compliance/events`,
    {
      method: 'POST',
      body: JSON.stringify(payload),
    },
  );
  return response.data;
}

/**
 * Submit an appeal for a penalized compliance event.
 */
export async function submitAppeal(
  eventId: number,
  payload: SubmitAppealPayload,
): Promise<MerchantComplianceEvent> {
  const response = await apiRequest<MerchantComplianceEvent>(
    `/compliance/events/${eventId}/appeal`,
    {
      method: 'POST',
      body: JSON.stringify(payload),
    },
  );
  return response.data;
}

/**
 * Adjudicate (approve or reject) an appeal as an administrator.
 */
export async function adjudicateAppeal(
  eventId: number,
  payload: AdjudicateAppealPayload,
): Promise<MerchantComplianceEvent> {
  const response = await apiRequest<MerchantComplianceEvent>(
    `/compliance/events/${eventId}/adjudicate`,
    {
      method: 'POST',
      body: JSON.stringify(payload),
    },
  );
  return response.data;
}

/**
 * Customer opens a dispute against an established purchase intent.
 */
export async function openDispute(
  intentId: number,
  payload: OpenDisputePayload,
): Promise<CustomerDispute> {
  const response = await apiRequest<CustomerDispute>(
    `/intents/${intentId}/disputes`,
    {
      method: 'POST',
      body: JSON.stringify(payload),
    },
  );
  return response.data;
}

/**
 * List disputes with optional filters (status, category, merchant).
 */
export async function listDisputes(
  filters: DisputeFilterParams = {},
): Promise<CustomerDispute[]> {
  const params = new URLSearchParams();
  if (filters.merchant_id) params.set('merchant_id', String(filters.merchant_id));
  if (filters.status) params.set('status', filters.status);
  if (filters.category) params.set('category', filters.category);

  const query = params.toString() ? `?${params.toString()}` : '';
  const response = await apiRequest<CustomerDispute[]>(`/disputes${query}`);
  return response.data;
}

/**
 * Get a single dispute with full customer, merchant, and resolution details.
 */
export async function getDispute(disputeId: number): Promise<CustomerDispute> {
  const response = await apiRequest<CustomerDispute>(`/disputes/${disputeId}`);
  return response.data;
}

/**
 * Merchant responds to an active dispute.
 */
export async function respondDispute(
  disputeId: number,
  payload: RespondDisputePayload,
): Promise<CustomerDispute> {
  const response = await apiRequest<CustomerDispute>(
    `/disputes/${disputeId}/respond`,
    {
      method: 'POST',
      body: JSON.stringify(payload),
    },
  );
  return response.data;
}

/**
 * Admin formally resolves a dispute (refund, replace, or reject).
 */
export async function resolveDispute(
  disputeId: number,
  payload: ResolveDisputePayload,
): Promise<CustomerDispute> {
  const response = await apiRequest<CustomerDispute>(
    `/disputes/${disputeId}/resolve`,
    {
      method: 'POST',
      body: JSON.stringify(payload),
    },
  );
  return response.data;
}

export const complianceService = {
  listComplianceEvents,
  recordComplianceEvent,
  submitAppeal,
  adjudicateAppeal,
  openDispute,
  listDisputes,
  getDispute,
  respondDispute,
  resolveDispute,
};
