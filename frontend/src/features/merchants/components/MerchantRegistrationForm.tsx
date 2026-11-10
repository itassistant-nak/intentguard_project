'use client';

import React, { useState } from 'react';
import {
  BusinessType,
  Merchant,
  MerchantPlatformType,
  RegisterMerchantPayload,
} from '../../../types/merchant';
import { merchantService } from '../../../services/merchantService';
import { agreementService } from '../../../services/agreementService';
import { MerchantAgreement } from '../../../types/agreement';
import { MerchantAgreementModal } from './MerchantAgreementModal';
import { MerchantStatusBadge } from './MerchantStatusBadge';

export function MerchantRegistrationForm() {
  const [formData, setFormData] = useState<RegisterMerchantPayload>({
    business_name: '',
    legal_name: '',
    business_registration_number: '',
    tax_id: '',
    business_type: 'corporation',
    contact_name: '',
    contact_email: '',
    contact_phone: '',
    website_url: '',
    platform_type: 'shopify',
  });

  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [registeredMerchant, setRegisteredMerchant] = useState<Merchant | null>(null);
  const [activeAgreement, setActiveAgreement] = useState<MerchantAgreement | null>(null);
  const [modalOpen, setModalOpen] = useState(false);
  const [agreementSuccess, setAgreementSuccess] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError(null);

    try {
      const res = await merchantService.register(formData);
      setRegisteredMerchant(res.data);

      // Fetch active agreement immediately for digital execution
      const agreementRes = await agreementService.getActive();
      setActiveAgreement(agreementRes.data);
      setModalOpen(true);
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : 'Registration failed.');
    } finally {
      setLoading(false);
    }
  };

  const handleAgreementAccepted = () => {
    setModalOpen(false);
    setAgreementSuccess(true);
    if (registeredMerchant) {
      setRegisteredMerchant({
        ...registeredMerchant,
        agreement_accepted: true,
      });
    }
  };

  return (
    <div className="bg-white rounded-2xl shadow-sm border border-slate-200/80 overflow-hidden">
      <div className="px-6 py-6 bg-[#101D35] text-white border-b border-[#1C2C4E]">
        <div className="flex items-center justify-between">
          <div>
            <h2 className="text-xl font-bold tracking-tight">Merchant Onboarding Portal</h2>
            <p className="text-xs text-slate-300 mt-1">
              Join the IntentGuard Trusted Merchant Network with cryptographic purchase integrity guarantees.
            </p>
          </div>
          {registeredMerchant && (
            <MerchantStatusBadge status={registeredMerchant.status} />
          )}
        </div>
      </div>

      {registeredMerchant ? (
        <div className="p-8 space-y-6">
          <div className="p-6 bg-[#12B981]/10 border border-[#12B981]/30 rounded-xl">
            <h3 className="text-lg font-bold text-[#0E9B6C] flex items-center gap-2">
              <span className="w-6 h-6 rounded-full bg-[#12B981] text-white flex items-center justify-center text-xs">✓</span>
              Merchant Successfully Registered!
            </h3>
            <p className="text-sm text-[#0E9B6C] mt-2">
              Your unique IntentGuard Merchant Identifier is:
            </p>
            <div className="mt-3 inline-block px-4 py-2 bg-white border border-[#12B981]/40 rounded-lg font-mono font-bold text-lg text-[#101D35] shadow-xs">
              {registeredMerchant.intentguard_id}
            </div>
            <p className="text-xs text-[#0E9B6C] mt-3">
              Keep this identifier secure. It validates your store identity during transaction integrity verification.
            </p>
          </div>

          <div className="border border-slate-200 rounded-xl p-6 bg-[#F3F6FB] space-y-3">
            <h4 className="font-semibold text-[#101D35] text-sm">Onboarding Next Steps:</h4>
            <div className="flex items-center justify-between py-2 border-b border-slate-200 text-sm">
              <span className="text-slate-600">1. Digital Merchant Agreement Execution</span>
              {registeredMerchant.agreement_accepted || agreementSuccess ? (
                <span className="text-xs font-semibold text-[#0E9B6C] bg-[#12B981]/10 border border-[#12B981]/30 px-2.5 py-0.5 rounded-full">
                  Signed &amp; Cryptographically Verified
                </span>
              ) : (
                <button
                  onClick={() => setModalOpen(true)}
                  className="text-xs font-semibold text-[#176BEB] hover:underline"
                >
                  Review &amp; Sign Policy →
                </button>
              )}
            </div>
            <div className="flex items-center justify-between py-2 text-sm">
              <span className="text-slate-600">2. Business Identity &amp; Registry Review</span>
              <span className="text-xs font-semibold text-[#A06813] bg-[#EAA33A]/10 border border-[#EAA33A]/30 px-2.5 py-0.5 rounded-full">
                Pending Administrator Review
              </span>
            </div>
          </div>

          <button
            onClick={() => {
              setRegisteredMerchant(null);
              setAgreementSuccess(false);
            }}
            className="text-xs text-[#176BEB] hover:text-[#1258C2] font-semibold"
          >
            ← Register another merchant entity
          </button>
        </div>
      ) : (
        <form onSubmit={handleSubmit} className="p-8 space-y-6">
          {error && (
            <div className="p-4 bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl font-medium">
              {error}
            </div>
          )}

          <div className="space-y-4">
            <h3 className="text-sm font-bold uppercase tracking-wider text-[#101D35]">
              1. Business Identity
            </h3>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label className="block text-xs font-semibold text-[#101D35] mb-1">
                  Operating Trading Name *
                </label>
                <input
                  type="text"
                  required
                  placeholder="Apex Electronics"
                  value={formData.business_name}
                  onChange={(e) => setFormData({ ...formData, business_name: e.target.value })}
                  className="w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#176BEB] focus:border-[#176BEB] focus:outline-none"
                />
              </div>

              <div>
                <label className="block text-xs font-semibold text-[#101D35] mb-1">
                  Registered Corporate Legal Name
                </label>
                <input
                  type="text"
                  placeholder="Apex Electronics Limited"
                  value={formData.legal_name || ''}
                  onChange={(e) => setFormData({ ...formData, legal_name: e.target.value })}
                  className="w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#176BEB] focus:border-[#176BEB] focus:outline-none"
                />
              </div>

              <div>
                <label className="block text-xs font-semibold text-[#101D35] mb-1">
                  Business Registration / Company Number *
                </label>
                <input
                  type="text"
                  required
                  placeholder="e.g. CRN-98472910"
                  value={formData.business_registration_number}
                  onChange={(e) =>
                    setFormData({ ...formData, business_registration_number: e.target.value })
                  }
                  className="w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#176BEB] focus:border-[#176BEB] focus:outline-none font-mono"
                />
              </div>

              <div>
                <label className="block text-xs font-semibold text-[#101D35] mb-1">
                  Tax Identification Number (TIN / VAT / EIN)
                </label>
                <input
                  type="text"
                  placeholder="e.g. VAT-89410294"
                  value={formData.tax_id || ''}
                  onChange={(e) => setFormData({ ...formData, tax_id: e.target.value })}
                  className="w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#176BEB] focus:border-[#176BEB] focus:outline-none font-mono"
                />
              </div>

              <div>
                <label className="block text-xs font-semibold text-[#101D35] mb-1">
                  Legal Entity Business Type *
                </label>
                <select
                  value={formData.business_type}
                  onChange={(e) =>
                    setFormData({ ...formData, business_type: e.target.value as BusinessType })
                  }
                  className="w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#176BEB] focus:border-[#176BEB] focus:outline-none"
                >
                  <option value="corporation">Corporation / Limited Liability Company</option>
                  <option value="sole_proprietorship">Sole Proprietorship</option>
                  <option value="partnership">Partnership</option>
                  <option value="other">Other / Non-Profit</option>
                </select>
              </div>

              <div>
                <label className="block text-xs font-semibold text-[#101D35] mb-1">
                  Primary E-Commerce Platform *
                </label>
                <select
                  value={formData.platform_type}
                  onChange={(e) =>
                    setFormData({
                      ...formData,
                      platform_type: e.target.value as MerchantPlatformType,
                    })
                  }
                  className="w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#176BEB] focus:border-[#176BEB] focus:outline-none"
                >
                  <option value="shopify">Shopify</option>
                  <option value="woocommerce">WooCommerce</option>
                  <option value="magento">Magento / Adobe Commerce</option>
                  <option value="bigcommerce">BigCommerce</option>
                  <option value="custom_api">Custom Enterprise API</option>
                </select>
              </div>
            </div>
          </div>

          <div className="space-y-4 pt-4 border-t border-slate-200">
            <h3 className="text-sm font-bold uppercase tracking-wider text-[#101D35]">
              2. Authorized Contact &amp; Store Information
            </h3>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label className="block text-xs font-semibold text-[#101D35] mb-1">
                  Authorized Signatory Contact Name *
                </label>
                <input
                  type="text"
                  required
                  placeholder="Sarah Jenkins"
                  value={formData.contact_name}
                  onChange={(e) => setFormData({ ...formData, contact_name: e.target.value })}
                  className="w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#176BEB] focus:border-[#176BEB] focus:outline-none"
                />
              </div>

              <div>
                <label className="block text-xs font-semibold text-[#101D35] mb-1">
                  Corporate Signatory Email *
                </label>
                <input
                  type="email"
                  required
                  placeholder="s.jenkins@apex.internal"
                  value={formData.contact_email}
                  onChange={(e) => setFormData({ ...formData, contact_email: e.target.value })}
                  className="w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#176BEB] focus:border-[#176BEB] focus:outline-none"
                />
              </div>

              <div>
                <label className="block text-xs font-semibold text-[#101D35] mb-1">
                  Business Phone Number
                </label>
                <input
                  type="tel"
                  placeholder="+1 (555) 019-2834"
                  value={formData.contact_phone || ''}
                  onChange={(e) => setFormData({ ...formData, contact_phone: e.target.value })}
                  className="w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#176BEB] focus:border-[#176BEB] focus:outline-none"
                />
              </div>

              <div>
                <label className="block text-xs font-semibold text-[#101D35] mb-1">
                  Official Storefront URL *
                </label>
                <input
                  type="url"
                  required
                  placeholder="https://store.apex.internal"
                  value={formData.website_url}
                  onChange={(e) => setFormData({ ...formData, website_url: e.target.value })}
                  className="w-full px-3.5 py-2.5 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-[#176BEB] focus:border-[#176BEB] focus:outline-none"
                />
              </div>
            </div>
          </div>

          <div className="pt-4 flex justify-end">
            <button
              type="submit"
              disabled={loading}
              className="px-6 py-3 bg-[#176BEB] hover:bg-[#1258C2] text-white font-semibold text-sm rounded-xl shadow-xs transition-colors cursor-pointer disabled:opacity-50"
            >
              {loading ? 'Submitting Application...' : 'Submit Application & Review Agreement →'}
            </button>
          </div>
        </form>
      )}

      {/* Digital Agreement Modal */}
      {registeredMerchant && activeAgreement && (
        <MerchantAgreementModal
          merchantId={registeredMerchant.intentguard_id}
          agreement={activeAgreement}
          isOpen={modalOpen}
          onClose={() => setModalOpen(false)}
          onAccepted={handleAgreementAccepted}
        />
      )}
    </div>
  );
}

export default MerchantRegistrationForm;
