// src/services/evidenceService.ts
// Module E: Evidence & Provenance API service

import { apiRequest } from './apiClient';
import type {
  EvidenceRecord,
  EvidenceType,
  RecordEvidencePayload,
  ProvenanceChainResponse,
  EvidenceIntegrityCheckResult,
} from '@/types/evidence';

/**
 * Record a new piece of evidence for a purchase intent.
 */
export async function recordEvidence(
  intentId: number,
  payload: RecordEvidencePayload,
): Promise<EvidenceRecord> {
  const response = await apiRequest<EvidenceRecord>(`/intents/${intentId}/evidence`, {
    method: 'POST',
    body: JSON.stringify(payload),
  });
  return response.data;
}

/**
 * List all evidence records for a purchase intent, optionally filtered by type.
 */
export async function listEvidence(
  intentId: number,
  type?: EvidenceType,
): Promise<EvidenceRecord[]> {
  const url = type
    ? `/intents/${intentId}/evidence?type=${type}`
    : `/intents/${intentId}/evidence`;

  const response = await apiRequest<EvidenceRecord[]>(url);
  return response.data;
}

/**
 * Retrieve a single evidence record by ID (includes content_snapshot if present).
 */
export async function getEvidence(evidenceId: number): Promise<EvidenceRecord> {
  const response = await apiRequest<EvidenceRecord>(`/evidence/${evidenceId}`);
  return response.data;
}

/**
 * Retrieve the full provenance chain for a purchase intent.
 */
export async function getProvenanceChain(
  intentId: number,
): Promise<ProvenanceChainResponse> {
  const response = await apiRequest<ProvenanceChainResponse>(
    `/intents/${intentId}/evidence/provenance-chain`,
  );
  return response.data;
}

/**
 * Re-verify cryptographic integrity of an evidence record.
 */
export async function verifyEvidenceIntegrity(
  evidenceId: number,
): Promise<EvidenceIntegrityCheckResult> {
  const response = await apiRequest<EvidenceIntegrityCheckResult>(
    `/evidence/${evidenceId}/verify-integrity`,
  );
  return response.data;
}
