// src/types/audit.ts
// Module J: System-Wide Audit, Cryptographic Provenance & Administrative Reporting

export interface SystemAuditLog {
  id: number;
  auditable_type?: string | null;
  auditable_id?: number | null;
  event: string;
  event_label: string;
  actor_type: string;
  actor_id?: number | null;
  actor_email?: string | null;
  actor_name?: string | null;
  ip_address?: string | null;
  user_agent?: string | null;
  old_values?: Record<string, unknown> | null;
  new_values?: Record<string, unknown> | null;
  metadata?: Record<string, unknown> | null;
  previous_hash?: string | null;
  tamper_hash: string;
  created_at: string;
}

export interface AuditChainVerificationResult {
  is_intact: boolean;
  verified_count: number;
  broken_record_id?: number | null;
  message: string;
  latest_hash?: string | null;
}

export interface PlatformMetrics {
  fraud_prevention: {
    interceptions_count: number;
    prevented_surge_amount_cents: number;
    prevented_surge_amount: string;
  };
  intent_analytics: {
    total_intents: number;
    intents_by_status: Record<string, number>;
  };
  payment_analytics: {
    total_authorizations: number;
    total_captured_amount_cents: number;
    total_captured_amount: string;
    held_authorizations_count: number;
    voided_authorizations_count: number;
  };
  merchant_health: {
    total_merchants: number;
    average_compliance_score: number;
    merchants_by_status: Record<string, number>;
  };
  dispute_analytics: {
    total_disputes: number;
    open_disputes: number;
    resolved_disputes: number;
  };
  audit_provenance: {
    total_audit_records: number;
    latest_tamper_hash?: string | null;
    last_audit_at?: string | null;
  };
}

export interface AuditLogFilterParams {
  event?: string;
  actor_type?: string;
  auditable_type?: string;
  auditable_id?: number;
  page?: number;
}
