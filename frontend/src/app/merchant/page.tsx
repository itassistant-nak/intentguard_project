'use client';

import React, { useEffect, useState } from 'react';
import Link from 'next/link';
import { merchantService } from '@/services/merchantService';
import { agreementService } from '@/services/agreementService';
import { complianceService } from '@/services/complianceService';
import { MerchantStatusBadge } from '@/features/merchants/components/MerchantStatusBadge';
import { MerchantAgreementModal } from '@/features/merchants/components/MerchantAgreementModal';
import type { Merchant } from '@/types/merchant';
import type { MerchantAgreement } from '@/types/agreement';
import type { CustomerDispute, MerchantComplianceEvent } from '@/types/compliance';

export default function MerchantPortalPage() {
  const [merchantIdInput, setMerchantIdInput] = useState('IG-MCH-APEX0001');
  const [merchant, setMerchant] = useState<Merchant | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  // Agreement State
  const [activeAgreement, setActiveAgreement] = useState<MerchantAgreement | null>(null);
  const [showAgreementModal, setShowAgreementModal] = useState(false);

  // Compliance & Dispute State
  const [complianceEvents, setComplianceEvents] = useState<MerchantComplianceEvent[]>([]);
  const [disputes, setDisputes] = useState<CustomerDispute[]>([]);
  const [activeTab, setActiveTab] = useState<'overview' | 'compliance' | 'disputes'>('overview');

  // Dispute Response Modal
  const [respondingDispute, setRespondingDispute] = useState<CustomerDispute | null>(null);
  const [merchantResponseText, setMerchantResponseText] = useState('');
  const [proposedResolution, setProposedResolution] = useState<'accept_refund' | 'express_replacement' | 'contest'>('accept_refund');
  const [submittingResponse, setSubmittingResponse] = useState(false);

  // Appeal Modal
  const [appealEvent, setAppealEvent] = useState<MerchantComplianceEvent | null>(null);
  const [appealReason, setAppealReason] = useState('');
  const [submittingAppeal, setSubmittingAppeal] = useState(false);

  useEffect(() => {
    loadMerchant('IG-MCH-APEX0001');
    loadActiveAgreement();
  }, []);

  const loadActiveAgreement = async () => {
    try {
      const res = await agreementService.getActive();
      if (res.data) setActiveAgreement(res.data);
    } catch (err) {
      console.error(err);
    }
  };

  const loadMerchant = async (id: string) => {
    if (!id.trim()) return;
    try {
      setLoading(true);
      setError(null);
      const res = await merchantService.getByIdentifier(id.trim());
      if (res.data) {
        setMerchant(res.data);
        await Promise.all([
          loadComplianceEvents(res.data.id),
          loadDisputes(res.data.id),
        ]);
      }
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : 'Merchant not found.');
      setMerchant(null);
    } finally {
      setLoading(false);
    }
  };

  const loadComplianceEvents = async (mId: number) => {
    try {
      const events = await complianceService.listComplianceEvents(mId);
      setComplianceEvents(events || []);
    } catch (err) {
      console.error(err);
    }
  };

  const loadDisputes = async (mId: number) => {
    try {
      const res = await complianceService.listDisputes({ merchant_id: mId });
      setDisputes(res || []);
    } catch (err) {
      console.error(err);
    }
  };

  const handleRespondDispute = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!respondingDispute) return;

    try {
      setSubmittingResponse(true);
      await complianceService.respondDispute(respondingDispute.id, {
        merchant_response: `[${proposedResolution.toUpperCase()}] ${merchantResponseText}`,
      });

      alert('Response submitted to customer and administrator.');
      setRespondingDispute(null);
      setMerchantResponseText('');
      if (merchant) await loadDisputes(merchant.id);
    } catch (err: unknown) {
      alert(err instanceof Error ? err.message : 'Failed to submit response.');
    } finally {
      setSubmittingResponse(false);
    }
  };

  const handleSubmitAppeal = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!appealEvent) return;

    try {
      setSubmittingAppeal(true);
      await complianceService.submitAppeal(appealEvent.id, {
        appeal_reason: appealReason,
      });

      alert('Appeal lodged successfully. It will be reviewed by an administrator.');
      setAppealEvent(null);
      setAppealReason('');
      if (merchant) await loadComplianceEvents(merchant.id);
    } catch (err: unknown) {
      alert(err instanceof Error ? err.message : 'Failed to lodge appeal.');
    } finally {
      setSubmittingAppeal(false);
    }
  };

  return (
    <main className="min-h-screen bg-slate-50 flex flex-col text-slate-900 pb-16">
      {/* Header Banner */}
      <section className="bg-slate-900 text-white py-10 px-4 sm:px-6 lg:px-8 border-b border-slate-800">
        <div className="max-w-7xl mx-auto flex flex-col md:flex-row md:items-center justify-between gap-4">
          <div>
            <div className="inline-flex items-center gap-2 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
              🏬 Merchant Accountability & Governance
            </div>
            <h1 className="text-2xl sm:text-3xl font-black tracking-tight mt-2">
              Merchant Profile, Compliance & Disputes
            </h1>
            <p className="text-xs sm:text-sm text-slate-400 mt-1 max-w-xl">
              Contractually guarantee product accuracy and shipping SLAs. Monitor your 100-point compliance score and adjudicate customer disputes.
            </p>
          </div>

          {/* Quick Lookup Form */}
          <div className="bg-slate-800 p-4 rounded-xl border border-slate-700 flex items-center gap-2">
            <input
              type="text"
              value={merchantIdInput}
              onChange={(e) => setMerchantIdInput(e.target.value)}
              placeholder="Enter IntentGuard ID..."
              className="px-3 py-1.5 text-xs bg-slate-900 border border-slate-600 rounded-lg text-white font-mono focus:ring-2 focus:ring-emerald-500 focus:outline-none"
            />
            <button
              onClick={() => loadMerchant(merchantIdInput)}
              disabled={loading}
              className="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold rounded-lg shadow-sm whitespace-nowrap disabled:opacity-50"
            >
              {loading ? 'Searching...' : 'Lookup Merchant'}
            </button>
          </div>
        </div>
      </section>

      {/* Main Content */}
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full space-y-6 flex-1">
        {error && (
          <div className="p-4 bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl">
            {error}
          </div>
        )}

        {merchant && (
          <div className="space-y-6">
            {/* Top Merchant Summary Card */}
            <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-6">
              <div className="space-y-2">
                <div className="flex items-center gap-3">
                  <h2 className="text-xl font-black text-slate-900">{merchant.business_name}</h2>
                  <MerchantStatusBadge status={merchant.status} />
                </div>
                <div className="text-xs text-slate-500 flex flex-wrap items-center gap-3">
                  <span>IntentGuard ID: <strong className="font-mono text-slate-800">{merchant.intentguard_id}</strong></span>
                  <span>•</span>
                  <span>Registry: <strong className="font-mono text-slate-800">{merchant.business_registration_number}</strong></span>
                  <span>•</span>
                  <span>Platform: <strong className="uppercase text-slate-800">{merchant.platform_type}</strong></span>
                </div>
              </div>

              {/* Compliance Score Gauge */}
              <div className="flex items-center gap-4 bg-slate-50 p-4 rounded-xl border border-slate-200/80">
                <div className="text-right">
                  <div className="text-[10px] font-bold text-slate-500 uppercase tracking-wider">
                    Fulfilment Compliance Score
                  </div>
                  <div className="text-2xl font-black text-slate-900">
                    {merchant.compliance_score} / 100
                  </div>
                  <div className="text-[10px] text-slate-500">
                    {Number(merchant.compliance_score) >= 90 ? (
                      <span className="text-emerald-600 font-bold">● Excellent Standing</span>
                    ) : Number(merchant.compliance_score) >= 70 ? (
                      <span className="text-amber-600 font-bold">● Good (Standard Monitoring)</span>
                    ) : (
                      <span className="text-rose-600 font-bold">● High Risk (Holds Active)</span>
                    )}
                  </div>
                </div>

                <div
                  className={`w-12 h-12 rounded-xl flex items-center justify-center font-black text-white text-base ${
                    Number(merchant.compliance_score) >= 90
                      ? 'bg-emerald-600'
                      : Number(merchant.compliance_score) >= 70
                      ? 'bg-amber-600'
                      : 'bg-rose-600'
                  }`}
                >
                  {Number(merchant.compliance_score).toFixed(0)}
                </div>
              </div>
            </div>

            {/* Navigation Tabs */}
            <div className="flex gap-2 border-b border-slate-200 pb-2">
              <button
                onClick={() => setActiveTab('overview')}
                className={`px-4 py-2 rounded-xl text-xs font-bold transition-all ${
                  activeTab === 'overview'
                    ? 'bg-emerald-600 text-white shadow-sm'
                    : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'
                }`}
              >
                📜 Policy Agreement & Provenance
              </button>
              <button
                onClick={() => setActiveTab('compliance')}
                className={`px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 ${
                  activeTab === 'compliance'
                    ? 'bg-emerald-600 text-white shadow-sm'
                    : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'
                }`}
              >
                <span>📊 Compliance Telemetry Log</span>
                <span className="px-1.5 py-0.5 bg-black/20 rounded-full text-[10px]">
                  {complianceEvents.length}
                </span>
              </button>
              <button
                onClick={() => setActiveTab('disputes')}
                className={`px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 ${
                  activeTab === 'disputes'
                    ? 'bg-emerald-600 text-white shadow-sm'
                    : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200'
                }`}
              >
                <span>⚖️ Customer Disputes</span>
                <span className="px-1.5 py-0.5 bg-black/20 rounded-full text-[10px]">
                  {disputes.length}
                </span>
              </button>
            </div>

            {/* TAB 1: AGREEMENT & PROVENANCE */}
            {activeTab === 'overview' && (
              <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {/* Agreement Status Card */}
                <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                  <div className="flex items-center justify-between">
                    <h3 className="font-bold text-slate-900 text-base">
                      IntentGuard Merchant Agreement
                    </h3>
                    <span className="px-2 py-0.5 bg-indigo-50 text-indigo-700 text-xs font-bold rounded">
                      Version {activeAgreement?.version || '1.0.0'}
                    </span>
                  </div>

                  <p className="text-xs text-slate-600 leading-relaxed">
                    Legally binding digital agreement governing product accuracy, 48-hour shipping SLA, unauthorized substitution bans, and mandatory refund guarantees.
                  </p>

                  <div className="p-4 bg-slate-50 rounded-xl border border-slate-200/80 space-y-2 text-xs">
                    <div className="flex items-center justify-between">
                      <span className="text-slate-500">Agreement Status:</span>
                      {merchant.agreement_accepted ? (
                        <span className="font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                          ✓ Digitally Executed & Active
                        </span>
                      ) : (
                        <span className="font-bold text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200">
                          Pending Execution
                        </span>
                      )}
                    </div>

                    <div className="text-[11px] font-mono text-slate-500 break-all pt-1 border-t border-slate-200">
                      <span className="font-bold text-slate-700">Active Content Hash:</span>{' '}
                      {activeAgreement?.content_hash}
                    </div>
                  </div>

                  <div className="pt-2">
                    <button
                      onClick={() => setShowAgreementModal(true)}
                      className="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-sm transition-all"
                    >
                      {merchant.agreement_accepted ? 'Review Signed Terms Digest →' : 'Review & Digitally Sign Agreement →'}
                    </button>
                  </div>
                </div>

                {/* Accountability Rules Checklist */}
                <div className="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
                  <h3 className="font-bold text-slate-900 text-base">
                    Contractual Accountability Standards
                  </h3>

                  <div className="space-y-3 text-xs">
                    <div className="p-3 bg-slate-50 rounded-xl border border-slate-200/80 flex items-start gap-3">
                      <span className="text-emerald-600 font-bold text-sm">✓</span>
                      <div>
                        <strong className="text-slate-900 block">Product Accuracy Strict Match</strong>
                        <span className="text-slate-600 text-[11px]">No spec drift, altered memory, or unsealed goods without prior consent.</span>
                      </div>
                    </div>

                    <div className="p-3 bg-slate-50 rounded-xl border border-slate-200/80 flex items-start gap-3">
                      <span className="text-emerald-600 font-bold text-sm">✓</span>
                      <div>
                        <strong className="text-slate-900 block">Pre-Capture Payment Interception</strong>
                        <span className="text-slate-600 text-[11px]">Orders with detected price surges or condition mismatches are held at the payment gateway.</span>
                      </div>
                    </div>

                    <div className="p-3 bg-slate-50 rounded-xl border border-slate-200/80 flex items-start gap-3">
                      <span className="text-emerald-600 font-bold text-sm">✓</span>
                      <div>
                        <strong className="text-slate-900 block">100-Point Compliance Rating</strong>
                        <span className="text-slate-600 text-[11px]">Unresolved disputes or verified mismatches deduct score points; falling below 70 triggers automatic review.</span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            )}

            {/* TAB 2: COMPLIANCE TELEMETRY */}
            {activeTab === 'compliance' && (
              <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden space-y-4 p-6">
                <div className="flex items-center justify-between">
                  <div>
                    <h3 className="font-bold text-slate-900 text-base">Fulfilment Compliance History</h3>
                    <p className="text-xs text-slate-500">Live score telemetry events recorded by IntentGuard engines.</p>
                  </div>
                  <button
                    onClick={() => merchant && loadComplianceEvents(merchant.id)}
                    className="text-xs text-emerald-600 hover:text-emerald-800 font-semibold"
                  >
                    🔄 Refresh
                  </button>
                </div>

                {complianceEvents.length === 0 ? (
                  <div className="p-12 text-center text-xs text-slate-500">
                    No compliance deductions or telemetry events recorded. Score is in pristine standing!
                  </div>
                ) : (
                  <div className="overflow-x-auto">
                    <table className="w-full text-left text-xs">
                      <thead className="bg-slate-50 text-slate-500 uppercase font-semibold border-b border-slate-200">
                        <tr>
                          <th className="px-4 py-2.5">Date</th>
                          <th className="px-4 py-2.5">Event Type</th>
                          <th className="px-4 py-2.5">Severity</th>
                          <th className="px-4 py-2.5">Score Impact</th>
                          <th className="px-4 py-2.5">Appeal Status</th>
                          <th className="px-4 py-2.5 text-right">Actions</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-slate-100">
                        {complianceEvents.map((evt) => (
                          <tr key={evt.id} className="hover:bg-slate-50/80">
                            <td className="px-4 py-3 font-mono text-[11px] text-slate-500">
                              {new Date(evt.created_at).toLocaleDateString()}
                            </td>
                            <td className="px-4 py-3 font-semibold text-slate-800 capitalize">
                              {evt.event_type.replace(/_/g, ' ')}
                            </td>
                            <td className="px-4 py-3">
                              <span
                                className={`px-2 py-0.5 rounded-full text-[10px] font-bold uppercase ${
                                  evt.severity === 'critical'
                                    ? 'bg-rose-100 text-rose-800'
                                    : evt.severity === 'warning'
                                    ? 'bg-amber-100 text-amber-800'
                                    : 'bg-blue-100 text-blue-800'
                                }`}
                              >
                                {evt.severity}
                              </span>
                            </td>
                            <td className="px-4 py-3 font-bold">
                              {Number(evt.score_delta) < 0 ? (
                                <span className="text-rose-600">{evt.score_delta} pts</span>
                              ) : (
                                <span className="text-emerald-600">+{evt.score_delta} pts</span>
                              )}
                            </td>
                            <td className="px-4 py-3 capitalize text-slate-600">
                              {evt.appeal_status}
                            </td>
                            <td className="px-4 py-3 text-right">
                              {evt.appeal_status === 'none' && Number(evt.score_delta) < 0 && (
                                <button
                                  onClick={() => setAppealEvent(evt)}
                                  className="px-2.5 py-1 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 rounded font-semibold text-[11px]"
                                >
                                  Lodge Appeal
                                </button>
                              )}
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                )}
              </div>
            )}

            {/* TAB 3: DISPUTES */}
            {activeTab === 'disputes' && (
              <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden space-y-4 p-6">
                <div className="flex items-center justify-between">
                  <div>
                    <h3 className="font-bold text-slate-900 text-base">Customer Disputes Inbox</h3>
                    <p className="text-xs text-slate-500">Formal complaints submitted by customers regarding item integrity.</p>
                  </div>
                  <button
                    onClick={() => merchant && loadDisputes(merchant.id)}
                    className="text-xs text-emerald-600 hover:text-emerald-800 font-semibold"
                  >
                    🔄 Refresh
                  </button>
                </div>

                {disputes.length === 0 ? (
                  <div className="p-12 text-center text-xs text-slate-500">
                    No active disputes filed against this merchant.
                  </div>
                ) : (
                  <div className="overflow-x-auto">
                    <table className="w-full text-left text-xs">
                      <thead className="bg-slate-50 text-slate-500 uppercase font-semibold border-b border-slate-200">
                        <tr>
                          <th className="px-4 py-2.5">Dispute #</th>
                          <th className="px-4 py-2.5">Category</th>
                          <th className="px-4 py-2.5">Claim Amount</th>
                          <th className="px-4 py-2.5">Status</th>
                          <th className="px-4 py-2.5">Customer Claim</th>
                          <th className="px-4 py-2.5 text-right">Actions</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-slate-100">
                        {disputes.map((d) => (
                          <tr key={d.id} className="hover:bg-slate-50/80">
                            <td className="px-4 py-3 font-mono font-bold text-slate-900">
                              #{d.id}
                            </td>
                            <td className="px-4 py-3 font-semibold text-slate-700 uppercase">
                              {d.dispute_category_label || d.dispute_category}
                            </td>
                            <td className="px-4 py-3 font-bold text-slate-900">
                              ${d.claimed_amount_cents ? (d.claimed_amount_cents / 100).toFixed(2) : '0.00'}
                            </td>
                            <td className="px-4 py-3">
                              <span
                                className={`px-2 py-0.5 rounded-full text-[10px] font-bold capitalize ${
                                  !d.is_terminal
                                    ? 'bg-amber-100 text-amber-800'
                                    : 'bg-slate-100 text-slate-700'
                                }`}
                              >
                                {d.status_label || d.status.replace(/_/g, ' ')}
                              </span>
                            </td>
                            <td className="px-4 py-3 text-slate-600 max-w-xs truncate">
                              {d.description}
                            </td>
                            <td className="px-4 py-3 text-right">
                              {!d.is_terminal && (
                                <button
                                  onClick={() => setRespondingDispute(d)}
                                  className="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded font-bold text-[11px]"
                                >
                                  Submit Response
                                </button>
                              )}
                            </td>
                          </tr>
                        ))}
                      </tbody>
                    </table>
                  </div>
                )}
              </div>
            )}
          </div>
        )}
      </div>

      {/* Agreement Signing Modal */}
      {showAgreementModal && activeAgreement && merchant && (
        <MerchantAgreementModal
          merchantId={merchant.intentguard_id}
          agreement={activeAgreement}
          isOpen={showAgreementModal}
          onClose={() => setShowAgreementModal(false)}
          onAccepted={() => {
            setShowAgreementModal(false);
            loadMerchant(merchant.intentguard_id);
          }}
        />
      )}

      {/* Dispute Response Modal */}
      {respondingDispute && (
        <div className="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl max-w-lg w-full shadow-2xl border border-slate-200 overflow-hidden">
            <div className="px-6 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
              <div>
                <h3 className="font-bold text-slate-900 text-sm">
                  Respond to Dispute #{respondingDispute.id}
                </h3>
                <p className="text-[11px] text-slate-500 mt-0.5">
                  Category: {respondingDispute.dispute_category_label || respondingDispute.dispute_category} | Claim: ${respondingDispute.claimed_amount_cents ? (respondingDispute.claimed_amount_cents / 100).toFixed(2) : '0.00'}
                </p>
              </div>
              <button
                onClick={() => setRespondingDispute(null)}
                className="text-slate-400 hover:text-slate-600 text-xl font-bold p-1"
              >
                ×
              </button>
            </div>

            <form onSubmit={handleRespondDispute} className="p-6 space-y-4">
              <div className="p-3 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-700">
                <span className="font-bold block text-slate-900">Customer Claim:</span>
                {respondingDispute.description}
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-700 mb-1">
                  Proposed Resolution *
                </label>
                <select
                  value={proposedResolution}
                  onChange={(e) => setProposedResolution(e.target.value as 'accept_refund' | 'express_replacement' | 'contest')}
                  className="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500 bg-white"
                >
                  <option value="accept_refund">Accept Full Refund (${respondingDispute.claimed_amount_cents ? (respondingDispute.claimed_amount_cents / 100).toFixed(2) : '0.00'})</option>
                  <option value="express_replacement">Ship Express Replacement Within 48 Hours</option>
                  <option value="contest">Contest Claim (Provide Proof of Conformance)</option>
                </select>
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-700 mb-1">
                  Merchant Response Notes *
                </label>
                <textarea
                  required
                  rows={3}
                  value={merchantResponseText}
                  onChange={(e) => setMerchantResponseText(e.target.value)}
                  placeholder="Detail your explanation, tracking numbers, or settlement terms..."
                  className="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500"
                />
              </div>

              <div className="flex justify-end gap-2 pt-2">
                <button
                  type="button"
                  onClick={() => setRespondingDispute(null)}
                  className="px-4 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-100"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={submittingResponse}
                  className="px-5 py-2 text-xs font-semibold text-white bg-emerald-600 rounded-lg hover:bg-emerald-700 disabled:opacity-50 shadow-sm"
                >
                  {submittingResponse ? 'Submitting...' : 'Submit Official Response'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Appeal Modal */}
      {appealEvent && (
        <div className="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl max-w-lg w-full shadow-2xl border border-slate-200 overflow-hidden">
            <div className="px-6 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
              <h3 className="font-bold text-slate-900 text-sm">
                Appeal Compliance Penalty: Event #{appealEvent.id}
              </h3>
              <button
                onClick={() => setAppealEvent(null)}
                className="text-slate-400 hover:text-slate-600 text-xl font-bold p-1"
              >
                ×
              </button>
            </div>

            <form onSubmit={handleSubmitAppeal} className="p-6 space-y-4">
              <div className="text-xs text-slate-600 space-y-1">
                <div><strong>Event:</strong> {appealEvent.event_type}</div>
                <div><strong>Score Penalty:</strong> <span className="text-rose-600 font-bold">{appealEvent.score_delta} points</span></div>
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-700 mb-1">
                  Appeal Rationale & Justification *
                </label>
                <textarea
                  required
                  rows={3}
                  value={appealReason}
                  onChange={(e) => setAppealReason(e.target.value)}
                  placeholder="Explain why this penalty was erroneous or outside merchant control..."
                  className="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-emerald-500"
                />
              </div>

              <div className="flex justify-end gap-2 pt-2">
                <button
                  type="button"
                  onClick={() => setAppealEvent(null)}
                  className="px-4 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-100"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={submittingAppeal}
                  className="px-5 py-2 text-xs font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50"
                >
                  {submittingAppeal ? 'Submitting...' : 'Lodge Formal Appeal'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </main>
  );
}
