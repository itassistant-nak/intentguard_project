export type MerchantStatus =
  | 'pending_review'
  | 'under_review'
  | 'verified'
  | 'rejected'
  | 'suspended';

export type BusinessType =
  | 'sole_proprietorship'
  | 'corporation'
  | 'llc'
  | 'partnership';

export type MerchantPlatformType =
  | 'shopify'
  | 'woocommerce'
  | 'magento'
  | 'custom'
  | 'paypal_direct';

export interface Merchant {
  id: number;
  intentguard_id: string;
  business_name: string;
  legal_name: string | null;
  business_registration_number: string;
  tax_id: string | null;
  business_type: BusinessType;
  contact_name: string;
  contact_email: string;
  contact_phone: string;
  website_url: string;
  platform_type: MerchantPlatformType;
  status: MerchantStatus;
  compliance_score: number;
  agreement_accepted: boolean;
  verification_notes?: string | null;
  verified_at?: string | null;
  created_at: string;
  updated_at: string;
}

export interface RegisterMerchantPayload {
  business_name: string;
  legal_name?: string;
  business_registration_number: string;
  tax_id?: string;
  business_type: BusinessType;
  contact_name: string;
  contact_email: string;
  contact_phone: string;
  website_url: string;
  platform_type: MerchantPlatformType;
}

export interface StatusAuditLog {
  id: number;
  merchant_id: number;
  previous_status: string;
  new_status: string;
  reason: string;
  actioned_by: number | null;
  actioned_by_name: string | null;
  created_at: string;
}
