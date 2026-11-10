export interface AgreementPolicies {
  product_accuracy: string;
  shipping_delivery: string;
  substitution: string;
  refund_replacement: string;
}

export interface MerchantAgreement {
  id: number;
  version: string;
  title: string;
  content_hash: string;
  policies: AgreementPolicies;
  is_active: boolean;
  published_at: string;
  created_at: string;
}

export interface AcceptAgreementPayload {
  agreement_id: number;
  signatory_name: string;
  signatory_title: string;
  terms_digest_snapshot: string;
}

export interface MerchantAgreementAcceptance {
  id: number;
  merchant_id: number;
  merchant_agreement_id: number;
  agreement_version?: string;
  signatory_name: string;
  signatory_title: string;
  ip_address: string;
  terms_digest_snapshot: string;
  accepted_at: string;
  created_at: string;
}
