// src/types/intent.ts
// Module D: Purchase Intent type definitions

export type PurchaseIntentStatus =
  | 'pending'
  | 'confirmed'
  | 'verified'
  | 'mismatched'
  | 'cancelled'
  | 'expired';

export interface ProductSpecification {
  brand?: string;
  model?: string;
  condition?: 'new' | 'refurbished' | 'used' | 'open_box';
  colour?: string;
  storage?: string;
  [key: string]: string | undefined;
}

export interface ShippingConditions {
  method: string;
  estimated_days_min: number;
  estimated_days_max: number;
  shipping_cost_cents: number;
}

export interface AllowedSubstitutions {
  colour_variants?: boolean;
  storage_upgrade?: boolean;
  refurbished_ok?: boolean;
  [key: string]: boolean | undefined;
}

export interface PurchaseIntent {
  id: number;
  intent_hash: string;
  status: PurchaseIntentStatus;
  status_label: string;

  // Parties
  customer_id: number;
  merchant_id: number;
  merchant_intentguard_id?: string;
  merchant_name?: string;

  // Product
  product_name: string;
  product_sku: string | null;
  product_url: string | null;
  specification: ProductSpecification;

  // Pricing
  quantity: number;
  unit_price_cents: number;
  unit_price: string;
  total_price_cents: number;
  total_price: string;
  currency: string;

  // Conditions
  shipping_conditions: ShippingConditions;
  allowed_substitutions: AllowedSubstitutions | null;

  // Timing
  expires_at: string | null;
  created_at: string;
  updated_at: string;
}

export interface RecordIntentPayload {
  merchant_id: number;
  product_name: string;
  product_sku?: string;
  product_url?: string;
  specification_json: ProductSpecification;
  quantity: number;
  unit_price_cents: number;
  currency: string;
  shipping_conditions_json: ShippingConditions;
  allowed_substitutions_json?: AllowedSubstitutions | null;
}

export interface IntegrityCheckResult {
  intent_id: number;
  intent_hash: string;
  integrity_ok: boolean;
  message: string;
}

export const STATUS_LABELS: Record<PurchaseIntentStatus, string> = {
  pending:    'Pending',
  confirmed:  'Confirmed',
  verified:   'Verified ✓',
  mismatched: 'Mismatched ✗',
  cancelled:  'Cancelled',
  expired:    'Expired',
};

export const STATUS_COLORS: Record<PurchaseIntentStatus, string> = {
  pending:    'bg-yellow-100 text-yellow-800',
  confirmed:  'bg-blue-100 text-blue-800',
  verified:   'bg-green-100 text-green-800',
  mismatched: 'bg-red-100 text-red-800',
  cancelled:  'bg-gray-100 text-gray-600',
  expired:    'bg-orange-100 text-orange-700',
};
