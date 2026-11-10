// src/services/auditService.ts
// Module J: System-Wide Audit & Administrative Reporting Client

import { apiRequest } from './apiClient';
import type {
  AuditChainVerificationResult,
  AuditLogFilterParams,
  PlatformMetrics,
  SystemAuditLog,
} from '@/types/audit';

/**
 * List paginated and filterable system audit logs (Admin restricted).
 */
export async function listAuditLogs(
  filters: AuditLogFilterParams = {},
): Promise<SystemAuditLog[]> {
  const params = new URLSearchParams();
  if (filters.event) params.set('event', filters.event);
  if (filters.actor_type) params.set('actor_type', filters.actor_type);
  if (filters.auditable_type) params.set('auditable_type', filters.auditable_type);
  if (filters.auditable_id) params.set('auditable_id', String(filters.auditable_id));
  if (filters.page) params.set('page', String(filters.page));

  const query = params.toString() ? `?${params.toString()}` : '';
  const response = await apiRequest<SystemAuditLog[]>(`/admin/audit-logs${query}`);
  return response.data;
}

/**
 * Mathematically verifies the linear SHA-256 cryptographic hash chain across audit records.
 */
export async function verifyAuditChain(
  limit: number = 1000,
): Promise<AuditChainVerificationResult> {
  const response = await apiRequest<AuditChainVerificationResult>(
    `/admin/audit-logs/verify-chain?limit=${limit}`,
  );
  return response.data;
}

/**
 * Retrieves platform-wide fraud prevention, transaction volume, and merchant health metrics.
 */
export async function getPlatformMetrics(): Promise<PlatformMetrics> {
  const response = await apiRequest<PlatformMetrics>('/admin/metrics');
  return response.data;
}

export const auditService = {
  listAuditLogs,
  verifyAuditChain,
  getPlatformMetrics,
};
