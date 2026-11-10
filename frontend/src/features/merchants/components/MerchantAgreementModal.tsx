'use client';

import React, { useState } from 'react';
import { MerchantAgreement } from '../../../types/agreement';
import { agreementService } from '../../../services/agreementService';

interface Props {
  merchantId: string;
  agreement: MerchantAgreement;
  isOpen: boolean;
  onClose: () => void;
  onAccepted: () => void;
}

export function MerchantAgreementModal({
  merchantId,
  agreement,
  isOpen,
  onClose,
  onAccepted,
}: Props) {
  const [signatoryName, setSignatoryName] = useState('');
  const [signatoryTitle, setSignatoryTitle] = useState('');
  const [agreed, setAgreed] = useState(false);
  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  if (!isOpen) return null;

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!agreed) {
      setError('You must confirm acceptance of all four policies.');
      return;
    }

    try {
      setSubmitting(true);
      setError(null);

      await agreementService.accept(merchantId, {
        agreement_id: agreement.id,
        signatory_name: signatoryName,
        signatory_title: signatoryTitle,
        terms_digest_snapshot: agreement.content_hash,
      });

      onAccepted();
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : 'Failed to submit agreement.');
    } finally {
      setSubmitting(false);
    }
  };

  return (
    <div className="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
      <div className="relative bg-white rounded-2xl max-w-3xl w-full shadow-2xl border border-slate-200 overflow-hidden flex flex-col max-h-[90vh]">
        {/* Modal Header */}
        <div className="px-6 py-5 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
          <div>
            <div className="flex items-center gap-2">
              <span className="px-2 py-0.5 text-xs font-bold bg-indigo-100 text-indigo-700 rounded">
                Version {agreement.version}
              </span>
              <h2 className="text-lg font-bold text-slate-900">{agreement.title}</h2>
            </div>
            <p className="text-xs text-slate-500 mt-1 font-mono break-all">
              Cryptographic SHA-256 Digest: {agreement.content_hash}
            </p>
          </div>
          <button
            onClick={onClose}
            className="text-slate-400 hover:text-slate-600 text-xl font-bold p-1"
          >
            ×
          </button>
        </div>

        {/* Policy Documents */}
        <div className="p-6 overflow-y-auto space-y-6 text-sm text-slate-700 divide-y divide-slate-100">
          <div className="pt-2">
            <h3 className="font-semibold text-slate-900 text-base flex items-center gap-2">
              <span className="w-6 h-6 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-xs font-bold">1</span>
              Product Accuracy Policy
            </h3>
            <p className="mt-2 text-slate-600 bg-slate-50 p-4 rounded-lg border border-slate-200/80 leading-relaxed">
              {agreement.policies.product_accuracy}
            </p>
          </div>

          <div className="pt-4">
            <h3 className="font-semibold text-slate-900 text-base flex items-center gap-2">
              <span className="w-6 h-6 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-xs font-bold">2</span>
              Shipping & Delivery SLA Policy
            </h3>
            <p className="mt-2 text-slate-600 bg-slate-50 p-4 rounded-lg border border-slate-200/80 leading-relaxed">
              {agreement.policies.shipping_delivery}
            </p>
          </div>

          <div className="pt-4">
            <h3 className="font-semibold text-slate-900 text-base flex items-center gap-2">
              <span className="w-6 h-6 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-xs font-bold">3</span>
              Authorized Substitution Protocol
            </h3>
            <p className="mt-2 text-slate-600 bg-slate-50 p-4 rounded-lg border border-slate-200/80 leading-relaxed">
              {agreement.policies.substitution}
            </p>
          </div>

          <div className="pt-4">
            <h3 className="font-semibold text-slate-900 text-base flex items-center gap-2">
              <span className="w-6 h-6 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-xs font-bold">4</span>
              Refund & Express Replacement Guarantee
            </h3>
            <p className="mt-2 text-slate-600 bg-slate-50 p-4 rounded-lg border border-slate-200/80 leading-relaxed">
              {agreement.policies.refund_replacement}
            </p>
          </div>
        </div>

        {/* Digital Signature Form */}
        <form onSubmit={handleSubmit} className="p-6 border-t border-slate-200 bg-slate-50 space-y-4">
          {error && (
            <div className="p-3 text-xs bg-red-50 border border-red-200 text-red-700 rounded-lg">
              {error}
            </div>
          )}

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-semibold text-slate-700 mb-1">
                Authorized Signatory Full Name
              </label>
              <input
                type="text"
                required
                value={signatoryName}
                onChange={(e) => setSignatoryName(e.target.value)}
                placeholder="e.g. Sarah Jenkins"
                className="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none"
              />
            </div>
            <div>
              <label className="block text-xs font-semibold text-slate-700 mb-1">
                Signatory Corporate Title
              </label>
              <input
                type="text"
                required
                value={signatoryTitle}
                onChange={(e) => setSignatoryTitle(e.target.value)}
                placeholder="e.g. Managing Director / CEO"
                className="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none"
              />
            </div>
          </div>

          <div className="flex items-start gap-2 pt-1">
            <input
              type="checkbox"
              id="agree-checkbox"
              checked={agreed}
              onChange={(e) => setAgreed(e.target.checked)}
              className="mt-1 rounded text-indigo-600 focus:ring-indigo-500"
            />
            <label htmlFor="agree-checkbox" className="text-xs text-slate-600 leading-normal">
              I certify under penalty of contract breach that I am legally authorized to bind this merchant entity to the terms of the IntentGuard Agreement version {agreement.version} and its cryptographic digest.
            </label>
          </div>

          <div className="flex items-center justify-end gap-3 pt-2">
            <button
              type="button"
              onClick={onClose}
              className="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-100"
            >
              Cancel
            </button>
            <button
              type="submit"
              disabled={submitting}
              className="px-5 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50 shadow-sm"
            >
              {submitting ? 'Cryptographically Signing...' : 'Digitally Execute Agreement'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
