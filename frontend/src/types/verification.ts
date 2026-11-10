// src/types/verification.ts
// Module F: Semantic Purchase Integrity Engine (SPIE) & Module G: Transaction Decision Engine (TDE)

export type VerificationVerdict =
  | 'MATCH'
  | 'MISMATCH'
  | 'UNCERTAIN'
  | 'REQUIRE_CUSTOMER_CONFIRMATION';

export type DiscrepancyCategory =
  | 'merchant'
  | 'price'
  | 'currency'
  | 'quantity'
  | 'condition'
  | 'specification'
  | 'shipping'
  | 'substitution';

export type DiscrepancySeverity = 'critical' | 'warning' | 'info';

export type DecisionResolutionStatus =
  | 'unresolved'
  | 'customer_confirmed'
  | 'customer_rejected'
  | 'hold_expired';

export type ResolutionAuditAction =
  | 'hold_placed'
  | 'customer_confirmed'
  | 'customer_rejected'
  | 'capture_released'
  | 'capture_voided'
  | 'hold_expired';

export type ResolutionActorType = 'customer' | 'system' | 'admin' | 'merchant';

export interface DiscrepancyItem {
  category: DiscrepancyCategory;
  category_label: string;
  severity: DiscrepancySeverity;
  severity_label: string;
  field: string;
  intended_value: unknown;
  actual_value: unknown;
  message: string;
  is_tolerated: boolean;
}

export interface CheckoutPayload {
  merchant_id: number;
  currency: string;
  unit_price_cents: number;
  quantity: number;
  total_price_cents: number;
  shipping_cost_cents?: number;
  product_name?: string;
  product_sku?: string;
  specification_json?: {
    brand?: string;
    model?: string;
    condition?: string;
    colour?: string;
    storage?: string;
    [key: string]: unknown;
  };
  line_items?: Array<{
    product_name?: string;
    product_sku?: string;
    unit_price_cents?: number;
    quantity?: number;
    specification_json?: Record<string, unknown>;
  }>;
  metadata?: Record<string, unknown>;
}

export interface DecisionResolutionAuditLog {
  id: number;
  verification_decision_id: number;
  action: ResolutionAuditAction;
  action_label: string;
  actor_type: ResolutionActorType;
  actor_type_label: string;
  actor_id: number | null;
  actor_name?: string | null;
  reason: string | null;
  metadata: Record<string, unknown> | null;
  created_at: string;
}

export interface VerificationDecision {
  id: number;
  purchase_intent_id: number;
  confidence_score: number;
  verdict: VerificationVerdict;
  verdict_label: string;
  hold_payment_capture: boolean;
  resolution_status: DecisionResolutionStatus;
  resolution_status_label: string;
  is_pending_hold: boolean;
  resolved_at: string | null;
  discrepancy_count: number;
  discrepancies: DiscrepancyItem[];
  checkout_payload: CheckoutPayload;
  resolution_audit_logs?: DecisionResolutionAuditLog[];
  decided_at: string;
  created_at: string;
}

export interface ConfirmResolutionPayload {
  notes?: string;
}

export interface RejectResolutionPayload {
  reason: string;
}

export const VERDICT_LABELS: Record<VerificationVerdict, string> = {
  MATCH: 'Match (Capture Approved)',
  MISMATCH: 'Mismatch (Capture Blocked)',
  UNCERTAIN: 'Uncertain (Capture Held)',
  REQUIRE_CUSTOMER_CONFIRMATION: 'Customer Confirmation Required',
};

export const VERDICT_COLORS: Record<VerificationVerdict, string> = {
  MATCH: 'bg-green-100 text-green-800 border-green-300',
  MISMATCH: 'bg-red-100 text-red-800 border-red-300',
  UNCERTAIN: 'bg-amber-100 text-amber-800 border-amber-300',
  REQUIRE_CUSTOMER_CONFIRMATION: 'bg-blue-100 text-blue-800 border-blue-300',
};

export const SEVERITY_COLORS: Record<DiscrepancySeverity, string> = {
  critical: 'text-red-600 bg-red-50',
  warning: 'text-amber-600 bg-amber-50',
  info: 'text-blue-600 bg-blue-50',
};

export const RESOLUTION_STATUS_LABELS: Record<DecisionResolutionStatus, string> = {
  unresolved: 'Pending Confirmation',
  customer_confirmed: 'Customer Confirmed',
  customer_rejected: 'Customer Rejected',
  hold_expired: 'Hold Expired',
};

export const RESOLUTION_STATUS_COLORS: Record<DecisionResolutionStatus, string> = {
  unresolved: 'bg-amber-100 text-amber-800',
  customer_confirmed: 'bg-green-100 text-green-800',
  customer_rejected: 'bg-red-100 text-red-800',
  hold_expired: 'bg-gray-100 text-gray-700',
};
