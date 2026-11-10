// src/types/verification.ts
// Module F: Semantic Purchase Integrity Engine (SPIE) types

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

export interface VerificationDecision {
  id: number;
  purchase_intent_id: number;
  confidence_score: number;
  verdict: VerificationVerdict;
  verdict_label: string;
  hold_payment_capture: boolean;
  discrepancy_count: number;
  discrepancies: DiscrepancyItem[];
  checkout_payload: CheckoutPayload;
  decided_at: string;
  created_at: string;
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
