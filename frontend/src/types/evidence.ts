// src/types/evidence.ts
// Module E: Evidence & Provenance type definitions

export type EvidenceType =
  | 'product_page'
  | 'checkout_screen'
  | 'payment_quote'
  | 'receipt';

export interface EvidenceRecord {
  id: number;
  purchase_intent_id: number;
  evidence_type: EvidenceType;
  evidence_type_label: string;
  evidence_type_description: string;
  source_url: string | null;
  has_inline_snapshot: boolean;
  has_blob_snapshot: boolean;
  content_snapshot_blob_path: string | null;
  content_hash: string;
  metadata: Record<string, unknown> | null;
  submitted_by?: {
    id: number;
    name: string;
  };
  captured_at: string;
  created_at: string;
  content_snapshot?: string | null;
}

export interface RecordEvidencePayload {
  evidence_type: EvidenceType;
  source_url?: string;
  content?: string;
  blob_path?: string;
  content_hash?: string;
  metadata?: Record<string, unknown>;
}

export interface ChainItem {
  id: number;
  content_hash: string;
  source_url: string | null;
  captured_at: string;
  integrity_ok: boolean | null;
  has_inline: boolean;
  has_blob: boolean;
}

export interface ProvenanceChainResponse {
  intent_id: number;
  total_records: number;
  chain: Partial<Record<EvidenceType, ChainItem[]>>;
  integrity_summary: {
    total: number;
    intact: number;
    tampered: number;
    unverifiable: number;
  };
}

export interface EvidenceIntegrityCheckResult {
  evidence_id: number;
  content_hash: string;
  integrity_ok: boolean | null;
  message: string;
}

export const EVIDENCE_TYPE_LABELS: Record<EvidenceType, string> = {
  product_page: 'Product Page Snapshot',
  checkout_screen: 'Checkout Screen Snapshot',
  payment_quote: 'Payment Quote',
  receipt: 'Order Receipt',
};
