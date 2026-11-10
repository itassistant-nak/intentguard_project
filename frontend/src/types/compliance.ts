// src/types/compliance.ts
// Module H: Merchant Compliance Telemetry & Dispute Adjudication

export type ComplianceEventType =
  | 'mismatch_confirmed'
  | 'unauthorized_substitution'
  | 'shipping_delay_breach'
  | 'chargeback_received'
  | 'positive_fulfilment_recovery';

export type ComplianceAppealStatus =
  | 'none'
  | 'pending_review'
  | 'approved'
  | 'rejected';

export type DisputeCategory =
  | 'item_not_as_described'
  | 'unauthorized_substitution'
  | 'excessive_shipping_delay'
  | 'pricing_discrepancy'
  | 'counterfeit_or_altered'
  | 'other';

export type DisputeStatus =
  | 'opened'
  | 'under_merchant_response'
  | 'escalated_to_admin'
  | 'resolved_refunded'
  | 'resolved_replaced'
  | 'resolved_rejected';

export interface MerchantComplianceEvent {
  id: number;
  merchant_id: number;
  purchase_intent_id?: number | null;
  verification_decision_id?: number | null;
  event_type: ComplianceEventType;
  event_type_label: string;
  severity: 'info' | 'warning' | 'critical';
  score_delta: number;
  score_before: number;
  score_after: number;
  notes?: string | null;
  evidence_reference?: string | null;
  appeal_status: ComplianceAppealStatus;
  appeal_status_label: string;
  appeal_reason?: string | null;
  appeal_evidence_url?: string | null;
  appealed_at?: string | null;
  adjudicated_at?: string | null;
  adjudicated_by?: number | null;
  adjudicator_name?: string | null;
  adjudication_notes?: string | null;
  created_at: string;
}

export interface CustomerDispute {
  id: number;
  purchase_intent_id: number;
  customer_id: number;
  customer_name?: string | null;
  merchant_id: number;
  merchant_name?: string | null;
  dispute_category: DisputeCategory;
  dispute_category_label: string;
  description: string;
  claimed_amount_cents?: number | null;
  claimed_amount?: string | null;
  evidence_snapshot_id?: number | null;
  status: DisputeStatus;
  status_label: string;
  is_terminal: boolean;
  merchant_response?: string | null;
  merchant_responded_at?: string | null;
  resolution_notes?: string | null;
  resolved_at?: string | null;
  resolved_by?: number | null;
  resolver_name?: string | null;
  created_at: string;
  updated_at: string;
}

export interface RecordComplianceEventPayload {
  event_type: ComplianceEventType;
  purchase_intent_id?: number;
  verification_decision_id?: number;
  score_delta?: number;
  notes?: string;
  evidence_reference?: string;
}

export interface SubmitAppealPayload {
  appeal_reason: string;
  appeal_evidence_url?: string;
}

export interface AdjudicateAppealPayload {
  approved: boolean;
  notes: string;
}

export interface OpenDisputePayload {
  dispute_category: DisputeCategory;
  description: string;
  claimed_amount_cents?: number;
  evidence_snapshot_id?: number;
}

export interface RespondDisputePayload {
  merchant_response: string;
}

export interface ResolveDisputePayload {
  status: 'resolved_refunded' | 'resolved_replaced' | 'resolved_rejected';
  notes: string;
}

export interface DisputeFilterParams {
  merchant_id?: number;
  status?: DisputeStatus;
  category?: DisputeCategory;
}
