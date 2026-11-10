'use client';

import React, { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import { authService, User } from '@/services/authService';
import { merchantService } from '@/services/merchantService';
import { complianceService } from '@/services/complianceService';
import { auditService } from '@/services/auditService';
import { MerchantStatusBadge } from '@/features/merchants/components/MerchantStatusBadge';
import type { Merchant, StatusAuditLog } from '@/types/merchant';
import type { CustomerDispute } from '@/types/compliance';
import type { SystemAuditLog, AuditChainVerificationResult, PlatformMetrics } from '@/types/audit';

export default function AdminDashboardPage() {
  const router = useRouter();
  const [user, setUser] = useState<User | null>(null);
  const [adminTab, setAdminTab] = useState<'merchants' | 'disputes' | 'audit_logs' | 'metrics'>('merchants');

  // Merchants State
  const [merchants, setMerchants] = useState<Merchant[]>([]);
  const [loadingMerchants, setLoadingMerchants] = useState(false);
  const [merchantFilter, setMerchantFilter] = useState<string>('all');
  const [auditLogs, setAuditLogs] = useState<{ merchantId: string; logs: StatusAuditLog[] } | null>(null);
  const [actionModal, setActionModal] = useState<{ merchant: Merchant; action: 'verify' | 'under_review' | 'reject' | 'suspend' } | null>(null);
  const [actionReason, setActionReason] = useState('');
  const [actionError, setActionError] = useState<string | null>(null);
  const [actionLoading, setActionLoading] = useState(false);

  // Dispute Adjudication State
  const [disputes, setDisputes] = useState<CustomerDispute[]>([]);
  const [loadingDisputes, setLoadingDisputes] = useState(false);
  const [adjudicateModal, setAdjudicateModal] = useState<CustomerDispute | null>(null);
  const [resolutionAction, setResolutionAction] = useState<'resolved_refunded' | 'resolved_replaced' | 'resolved_rejected'>('resolved_refunded');
  const [adminNotes, setAdminNotes] = useState('');
  const [adjudicating, setAdjudicating] = useState(false);

  // Audit Logs State
  const [systemAuditLogs, setSystemAuditLogs] = useState<SystemAuditLog[]>([]);
  const [loadingAuditLogs, setLoadingAuditLogs] = useState(false);
  const [chainResult, setChainResult] = useState<AuditChainVerificationResult | null>(null);
  const [verifyingChain, setVerifyingChain] = useState(false);

  // Platform Metrics State
  const [metrics, setMetrics] = useState<PlatformMetrics | null>(null);
  const [loadingMetrics, setLoadingMetrics] = useState(false);

  useEffect(() => {
    const currentUser = authService.getUser();
    if (!authService.isAuthenticated() || currentUser?.role !== 'admin') {
      router.push('/admin/login');
      return;
    }
    setUser(currentUser);
    loadMerchants();
  }, [router]);

  const loadMerchants = async () => {
    try {
      setLoadingMerchants(true);
      const res = await merchantService.getByIdentifier('');
      if (res.data) {
        setMerchants(Array.isArray(res.data) ? res.data : [res.data]);
      }
    } catch {
      // Fallback: list via direct fetch
      try {
        const listRes = await fetch('http://127.0.0.1:8000/api/v1/merchants', {
          headers: {
            Accept: 'application/json',
            Authorization: `Bearer ${authService.getToken()}`,
          },
        });
        const data = await listRes.json();
        if (data.data) setMerchants(data.data);
      } catch (err) {
        console.error(err);
      }
    } finally {
      setLoadingMerchants(false);
    }
  };

  const loadDisputes = async () => {
    try {
      setLoadingDisputes(true);
      const res = await complianceService.listDisputes();
      setDisputes(res || []);
    } catch (err) {
      console.error(err);
    } finally {
      setLoadingDisputes(false);
    }
  };

  const loadSystemAuditLogs = async () => {
    try {
      setLoadingAuditLogs(true);
      const res = await auditService.listAuditLogs();
      setSystemAuditLogs(res || []);
    } catch (err) {
      console.error(err);
    } finally {
      setLoadingAuditLogs(false);
    }
  };

  const loadMetrics = async () => {
    try {
      setLoadingMetrics(true);
      const res = await auditService.getPlatformMetrics();
      setMetrics(res || null);
    } catch (err) {
      console.error(err);
    } finally {
      setLoadingMetrics(false);
    }
  };

  const handleVerifyLinearChain = async () => {
    try {
      setVerifyingChain(true);
      const result = await auditService.verifyAuditChain(500);
      setChainResult(result);
    } catch (err) {
      alert(err instanceof Error ? err.message : 'Chain verification failed.');
    } finally {
      setVerifyingChain(false);
    }
  };

  const handleLogout = () => {
    authService.logout();
    router.push('/admin/login');
  };

  const handleStatusChange = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!actionModal) return;

    try {
      setActionLoading(true);
      setActionError(null);

      const targetStatus = actionModal.action === 'verify'
        ? 'verified'
        : actionModal.action === 'under_review'
        ? 'under_review'
        : actionModal.action === 'reject'
        ? 'rejected'
        : 'suspended';

      await merchantService.updateStatus(
        actionModal.merchant.intentguard_id,
        targetStatus,
        actionReason
      );

      setActionModal(null);
      setActionReason('');
      await loadMerchants();
    } catch (err: unknown) {
      setActionError(err instanceof Error ? err.message : 'Status update failed.');
    } finally {
      setActionLoading(false);
    }
  };

  const handleAdjudicateDispute = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!adjudicateModal) return;

    try {
      setAdjudicating(true);
      await complianceService.resolveDispute(adjudicateModal.id, {
        status: resolutionAction,
        notes: adminNotes,
      });

      alert('Dispute formally adjudicated.');
      setAdjudicateModal(null);
      setAdminNotes('');
      await loadDisputes();
    } catch (err: unknown) {
      alert(err instanceof Error ? err.message : 'Adjudication failed.');
    } finally {
      setAdjudicating(false);
    }
  };

  const viewAuditLogs = async (merchant: Merchant) => {
    try {
      const res = await merchantService.getAuditLogs(merchant.intentguard_id);
      setAuditLogs({
        merchantId: merchant.intentguard_id,
        logs: res.data || [],
      });
    } catch (err) {
      console.error(err);
    }
  };

  const filteredMerchants = merchants.filter((m) => {
    if (merchantFilter === 'all') return true;
    return m.status === merchantFilter;
  });

  return (
    <div className="min-h-screen bg-slate-100 flex flex-col text-slate-900 pb-16">
      {/* Top Banner */}
      <header className="bg-slate-900 text-white border-b border-slate-800">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
          <div className="flex items-center gap-3">
            <Link href="/" className="flex items-center gap-2">
              <div className="w-8 h-8 rounded-lg bg-indigo-600 flex items-center justify-center font-bold text-white text-sm">
                IG
              </div>
              <span className="font-black text-lg tracking-tight">
                INTENT<span className="text-indigo-400">GUARD</span>
              </span>
            </Link>
            <span className="text-xs bg-indigo-500/20 text-indigo-300 px-2.5 py-0.5 rounded-full border border-indigo-500/30 font-semibold">
              System Admin Console
            </span>
          </div>

          <div className="flex items-center gap-4 text-xs">
            <div className="text-right hidden sm:block">
              <div className="font-semibold text-slate-200">{user?.name}</div>
              <div className="text-slate-400 font-mono text-[11px]">{user?.email}</div>
            </div>
            <button
              onClick={handleLogout}
              className="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-slate-200 rounded-lg text-xs font-semibold border border-slate-700"
            >
              Sign Out
            </button>
          </div>
        </div>
      </header>

      {/* Main Console Container */}
      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full flex-1 space-y-6">
        {/* Navigation Tabs */}
        <div className="flex flex-wrap gap-2 border-b border-slate-200 pb-3">
          <button
            onClick={() => {
              setAdminTab('merchants');
              loadMerchants();
            }}
            className={`px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 ${
              adminTab === 'merchants'
                ? 'bg-slate-900 text-white shadow-sm'
                : 'bg-white text-slate-600 hover:bg-slate-200 border border-slate-200'
            }`}
          >
            <span>🏬 Merchant Vetting & Governance</span>
            <span className="px-1.5 py-0.5 bg-black/20 rounded-full text-[10px]">
              {merchants.length}
            </span>
          </button>

          <button
            onClick={() => {
              setAdminTab('disputes');
              loadDisputes();
            }}
            className={`px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 ${
              adminTab === 'disputes'
                ? 'bg-slate-900 text-white shadow-sm'
                : 'bg-white text-slate-600 hover:bg-slate-200 border border-slate-200'
            }`}
          >
            <span>⚖️ Customer Dispute Adjudication</span>
            <span className="px-1.5 py-0.5 bg-black/20 rounded-full text-[10px]">
              {disputes.length}
            </span>
          </button>

          <button
            onClick={() => {
              setAdminTab('audit_logs');
              loadSystemAuditLogs();
            }}
            className={`px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 ${
              adminTab === 'audit_logs'
                ? 'bg-slate-900 text-white shadow-sm'
                : 'bg-white text-slate-600 hover:bg-slate-200 border border-slate-200'
            }`}
          >
            <span>🔒 Cryptographic Audit Log Explorer</span>
          </button>

          <button
            onClick={() => {
              setAdminTab('metrics');
              loadMetrics();
            }}
            className={`px-4 py-2 rounded-xl text-xs font-bold transition-all flex items-center gap-2 ${
              adminTab === 'metrics'
                ? 'bg-slate-900 text-white shadow-sm'
                : 'bg-white text-slate-600 hover:bg-slate-200 border border-slate-200'
            }`}
          >
            <span>📈 Platform Metrics & Fraud Impact</span>
          </button>
        </div>

        {/* TAB 1: MERCHANTS VETTING */}
        {adminTab === 'merchants' && (
          <div className="space-y-6">
            {/* KPI Cards */}
            <div className="grid grid-cols-1 sm:grid-cols-3 gap-5">
              <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
                <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider">
                  Total Registered
                </span>
                <div className="text-2xl font-bold text-slate-900 mt-1">{merchants.length}</div>
                <p className="text-xs text-slate-400 mt-1">E-Commerce merchant entities</p>
              </div>

              <div className="bg-white p-5 rounded-2xl border border-amber-200 shadow-sm bg-amber-50/20">
                <span className="text-xs font-semibold text-amber-700 uppercase tracking-wider">
                  Pending Vetting
                </span>
                <div className="text-2xl font-bold text-amber-900 mt-1">
                  {merchants.filter((m) => m.status === 'pending_review' || m.status === 'under_review').length}
                </div>
                <p className="text-xs text-amber-700 mt-1">Awaiting review or agreement</p>
              </div>

              <div className="bg-white p-5 rounded-2xl border border-emerald-200 shadow-sm bg-emerald-50/20">
                <span className="text-xs font-semibold text-emerald-700 uppercase tracking-wider">
                  Verified & Operable
                </span>
                <div className="text-2xl font-bold text-emerald-900 mt-1">
                  {merchants.filter((m) => m.status === 'verified').length}
                </div>
                <p className="text-xs text-emerald-700 mt-1">Active purchase protection</p>
              </div>
            </div>

            {/* Merchant Review Table */}
            <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
              <div className="p-6 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                  <h2 className="text-lg font-bold text-slate-900">Merchant Applications & Governance</h2>
                  <p className="text-xs text-slate-500 mt-0.5">
                    Review registrations, enforce policy agreement verification, and update network status.
                  </p>
                </div>

                <div className="flex gap-1.5 p-1 bg-slate-100 rounded-xl text-xs font-semibold">
                  {(['all', 'pending_review', 'under_review', 'verified', 'rejected'] as const).map((tab) => (
                    <button
                      key={tab}
                      onClick={() => setMerchantFilter(tab)}
                      className={`px-3 py-1.5 rounded-lg capitalize transition-all ${
                        merchantFilter === tab
                          ? 'bg-white text-slate-900 shadow-sm font-bold'
                          : 'text-slate-600 hover:text-slate-900'
                      }`}
                    >
                      {tab.replace('_', ' ')}
                    </button>
                  ))}
                </div>
              </div>

              {loadingMerchants ? (
                <div className="p-12 text-center text-sm text-slate-500">
                  Loading merchant registry...
                </div>
              ) : filteredMerchants.length === 0 ? (
                <div className="p-12 text-center text-sm text-slate-500 space-y-2">
                  <p>No merchants match the selected filter.</p>
                </div>
              ) : (
                <div className="overflow-x-auto">
                  <table className="w-full text-left text-sm">
                    <thead className="bg-slate-50 text-slate-500 text-xs font-semibold border-b border-slate-200 uppercase tracking-wider">
                      <tr>
                        <th className="px-6 py-3.5">IntentGuard ID</th>
                        <th className="px-6 py-3.5">Business / Legal Name</th>
                        <th className="px-6 py-3.5">Score</th>
                        <th className="px-6 py-3.5">Agreement</th>
                        <th className="px-6 py-3.5">Status</th>
                        <th className="px-6 py-3.5 text-right">Actions</th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-slate-100">
                      {filteredMerchants.map((m) => (
                        <tr key={m.id} className="hover:bg-slate-50/80 transition-colors">
                          <td className="px-6 py-4 font-mono font-bold text-xs text-slate-900">
                            {m.intentguard_id}
                          </td>
                          <td className="px-6 py-4">
                            <div className="font-semibold text-slate-900">{m.business_name}</div>
                            <div className="text-xs text-slate-500">{m.legal_name || '—'}</div>
                          </td>
                          <td className="px-6 py-4 font-bold text-xs">
                            <span className={Number(m.compliance_score) >= 90 ? 'text-emerald-600' : 'text-amber-600'}>
                              {m.compliance_score} / 100
                            </span>
                          </td>
                          <td className="px-6 py-4">
                            {m.agreement_accepted ? (
                              <span className="inline-flex items-center gap-1 text-xs font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">
                                ✓ Signed
                              </span>
                            ) : (
                              <span className="inline-flex items-center gap-1 text-xs font-semibold text-amber-700 bg-amber-50 px-2 py-0.5 rounded-full border border-amber-200">
                                Pending
                              </span>
                            )}
                          </td>
                          <td className="px-6 py-4">
                            <MerchantStatusBadge status={m.status} />
                          </td>
                          <td className="px-6 py-4 text-right space-x-2">
                            {m.status === 'pending_review' && (
                              <button
                                onClick={() => {
                                  setActionModal({ merchant: m, action: 'under_review' });
                                  setActionReason('Administrator commenced review of registration documents.');
                                }}
                                className="px-2.5 py-1 text-xs font-semibold text-blue-700 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100"
                              >
                                Review
                              </button>
                            )}

                            {m.status === 'under_review' && (
                              <button
                                onClick={() => {
                                  setActionModal({ merchant: m, action: 'verify' });
                                  setActionReason('Company registration, platform integration, and signed policy agreement verified.');
                                }}
                                className="px-2.5 py-1 text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-lg hover:bg-emerald-100"
                              >
                                Verify & Approve
                              </button>
                            )}

                            <button
                              onClick={() => viewAuditLogs(m)}
                              className="px-2.5 py-1 text-xs font-semibold text-slate-700 bg-slate-100 border border-slate-200 rounded-lg hover:bg-slate-200"
                            >
                              Audit Log
                            </button>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              )}
            </div>
          </div>
        )}

        {/* TAB 2: DISPUTES ADJUDICATION */}
        {adminTab === 'disputes' && (
          <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden space-y-4 p-6">
            <div className="flex items-center justify-between">
              <div>
                <h2 className="text-base font-bold text-slate-900">Customer Disputes Adjudication Console</h2>
                <p className="text-xs text-slate-500">Review and legally adjudicate open purchase intent disputes.</p>
              </div>
              <button
                onClick={loadDisputes}
                className="text-xs text-indigo-600 hover:text-indigo-800 font-semibold"
              >
                🔄 Refresh
              </button>
            </div>

            {loadingDisputes ? (
              <div className="p-12 text-center text-xs text-slate-500">Loading disputes...</div>
            ) : disputes.length === 0 ? (
              <div className="p-12 text-center text-xs text-slate-500">No disputes in system.</div>
            ) : (
              <div className="overflow-x-auto">
                <table className="w-full text-left text-xs">
                  <thead className="bg-slate-50 text-slate-500 uppercase font-semibold border-b border-slate-200">
                    <tr>
                      <th className="px-4 py-2.5">ID</th>
                      <th className="px-4 py-2.5">Category</th>
                      <th className="px-4 py-2.5">Claim Amount</th>
                      <th className="px-4 py-2.5">Status</th>
                      <th className="px-4 py-2.5">Merchant Response</th>
                      <th className="px-4 py-2.5 text-right">Actions</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    {disputes.map((d) => (
                      <tr key={d.id} className="hover:bg-slate-50/80">
                        <td className="px-4 py-3 font-mono font-bold text-slate-900">#{d.id}</td>
                        <td className="px-4 py-3 font-semibold uppercase">{d.dispute_category_label || d.dispute_category}</td>
                        <td className="px-4 py-3 font-bold">${d.claimed_amount_cents ? (d.claimed_amount_cents / 100).toFixed(2) : '0.00'}</td>
                        <td className="px-4 py-3 capitalize">{d.status_label || d.status.replace(/_/g, ' ')}</td>
                        <td className="px-4 py-3 text-slate-600 max-w-xs truncate">{d.merchant_response || 'Pending Response'}</td>
                        <td className="px-4 py-3 text-right">
                          {!d.is_terminal && (
                            <button
                              onClick={() => {
                                setAdjudicateModal(d);
                                setAdminNotes('Adjudicated after evaluating evidence and merchant response.');
                              }}
                              className="px-2.5 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded font-bold text-[11px]"
                            >
                              Adjudicate
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

        {/* TAB 3: CRYPTOGRAPHIC AUDIT LOG EXPLORER */}
        {adminTab === 'audit_logs' && (
          <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden space-y-6 p-6">
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
              <div>
                <h2 className="text-base font-bold text-slate-900">
                  Linear SHA-256 Chained System Audit Logs
                </h2>
                <p className="text-xs text-slate-500">
                  Tamper-evident append-only ledger mathematically chained via previous_hash pointers.
                </p>
              </div>

              <button
                onClick={handleVerifyLinearChain}
                disabled={verifyingChain}
                className="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-md transition-all flex items-center gap-2 disabled:opacity-50"
              >
                {verifyingChain ? 'Verifying Linear Digests...' : '🛡️ Verify Linear Chain Cryptographic Integrity'}
              </button>
            </div>

            {/* Chain Verification Result Banner */}
            {chainResult && (
              <div
                className={`p-4 rounded-xl border flex items-center justify-between gap-4 text-xs ${
                  chainResult.is_intact
                    ? 'bg-emerald-50 border-emerald-200 text-emerald-900'
                    : 'bg-rose-50 border-rose-200 text-rose-900'
                }`}
              >
                <div>
                  <span className="font-bold text-sm block">
                    {chainResult.is_intact ? '✓ Cryptographic Ledger Integrity Confirmed' : '⚠ Cryptographic Tampering Detected!'}
                  </span>
                  <span>{chainResult.message}</span>
                </div>
                <div className="text-right font-mono font-bold">
                  <div>Verified Records: {chainResult.verified_count}</div>
                  <div>Status: {chainResult.broken_record_id ? `Broken at #${chainResult.broken_record_id}` : 'Intact'}</div>
                </div>
              </div>
            )}

            {/* Audit Log Table */}
            {loadingAuditLogs ? (
              <div className="p-12 text-center text-xs text-slate-500">Loading audit log entries...</div>
            ) : systemAuditLogs.length === 0 ? (
              <div className="p-12 text-center text-xs text-slate-500">No system audit records logged yet.</div>
            ) : (
              <div className="overflow-x-auto">
                <table className="w-full text-left text-xs">
                  <thead className="bg-slate-50 text-slate-500 uppercase font-semibold border-b border-slate-200">
                    <tr>
                      <th className="px-4 py-2.5">ID</th>
                      <th className="px-4 py-2.5">Timestamp</th>
                      <th className="px-4 py-2.5">Event</th>
                      <th className="px-4 py-2.5">Actor</th>
                      <th className="px-4 py-2.5">Target Entity</th>
                      <th className="px-4 py-2.5">Tamper Hash (SHA-256)</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100 font-mono text-[11px]">
                    {systemAuditLogs.map((log) => (
                      <tr key={log.id} className="hover:bg-slate-50/80">
                        <td className="px-4 py-3 font-bold text-slate-900">#{log.id}</td>
                        <td className="px-4 py-3 text-slate-500">
                          {new Date(log.created_at).toLocaleTimeString()}
                        </td>
                        <td className="px-4 py-3 font-semibold text-slate-800 font-sans">
                          {log.event}
                        </td>
                        <td className="px-4 py-3 text-slate-600">
                          {log.actor_type} #{log.actor_id || 'sys'}
                        </td>
                        <td className="px-4 py-3 text-slate-600">
                          {log.auditable_type ? `${log.auditable_type.split('\\').pop()} #${log.auditable_id}` : '—'}
                        </td>
                        <td className="px-4 py-3 text-indigo-600 font-bold truncate max-w-xs" title={log.tamper_hash}>
                          {log.tamper_hash.substring(0, 16)}...
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </div>
        )}

        {/* TAB 4: PLATFORM METRICS & FRAUD IMPACT */}
        {adminTab === 'metrics' && (
          <div className="space-y-6">
            <div className="flex items-center justify-between">
              <div>
                <h2 className="text-base font-bold text-slate-900">Platform Fraud Prevention & Volume Metrics</h2>
                <p className="text-xs text-slate-500">Empirical measurement of transaction integrity protection across the ecosystem.</p>
              </div>
              <button
                onClick={loadMetrics}
                className="text-xs text-indigo-600 hover:text-indigo-800 font-semibold"
              >
                🔄 Refresh Metrics
              </button>
            </div>

            {loadingMetrics || !metrics ? (
              <div className="p-12 text-center text-xs text-slate-500 bg-white rounded-2xl border border-slate-200">
                Loading platform metrics...
              </div>
            ) : (
              <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm space-y-1">
                  <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider">
                    Total Purchase Intents
                  </span>
                  <div className="text-2xl font-black text-slate-900">
                    {metrics.intent_analytics?.total_intents || 0}
                  </div>
                  <div className="text-[11px] text-slate-400">
                    Verified: {metrics.intent_analytics?.intents_by_status?.verified || 0} | Mismatched: {metrics.intent_analytics?.intents_by_status?.mismatched || 0}
                  </div>
                </div>

                <div className="bg-white p-5 rounded-2xl border border-emerald-200 shadow-sm bg-emerald-50/20 space-y-1">
                  <span className="text-xs font-semibold text-emerald-700 uppercase tracking-wider">
                    Total Verified Volume
                  </span>
                  <div className="text-2xl font-black text-emerald-900">
                    ${((metrics.payment_analytics?.total_captured_amount_cents || 0) / 100).toFixed(2)}
                  </div>
                  <div className="text-[11px] text-emerald-700">
                    Total Processed Captures
                  </div>
                </div>

                <div className="bg-white p-5 rounded-2xl border border-rose-200 shadow-sm bg-rose-50/20 space-y-1">
                  <span className="text-xs font-semibold text-rose-700 uppercase tracking-wider">
                    Prevented Fraud / Mismatch
                  </span>
                  <div className="text-2xl font-black text-rose-900">
                    ${((metrics.fraud_prevention?.prevented_surge_amount_cents || 0) / 100).toFixed(2)}
                  </div>
                  <div className="text-[11px] text-rose-700">
                    Prevented by Pre-Capture SPIE Void
                  </div>
                </div>

                <div className="bg-white p-5 rounded-2xl border border-amber-200 shadow-sm bg-amber-50/20 space-y-1">
                  <span className="text-xs font-semibold text-amber-700 uppercase tracking-wider">
                    Active Pre-Capture Holds
                  </span>
                  <div className="text-2xl font-black text-amber-900">
                    {metrics.payment_analytics?.held_authorizations_count || 0}
                  </div>
                  <div className="text-[11px] text-amber-700">
                    Awaiting Customer Confirmation
                  </div>
                </div>
              </div>
            )}
          </div>
        )}
      </main>

      {/* Adjudication Modal */}
      {adjudicateModal && (
        <div className="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl max-w-lg w-full shadow-2xl border border-slate-200 overflow-hidden">
            <div className="px-6 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
              <div>
                <h3 className="font-bold text-slate-900 text-sm">
                  Adjudicate Dispute #{adjudicateModal.id}
                </h3>
                <p className="text-[11px] text-slate-500 mt-0.5">
                  Category: {adjudicateModal.dispute_category_label || adjudicateModal.dispute_category} | Claim: ${adjudicateModal.claimed_amount_cents ? (adjudicateModal.claimed_amount_cents / 100).toFixed(2) : '0.00'}
                </p>
              </div>
              <button
                onClick={() => setAdjudicateModal(null)}
                className="text-slate-400 hover:text-slate-600 text-xl font-bold p-1"
              >
                ×
              </button>
            </div>

            <form onSubmit={handleAdjudicateDispute} className="p-6 space-y-4">
              <div className="p-3 bg-slate-50 border border-slate-200 rounded-lg text-xs space-y-1">
                <div><strong>Customer Claim:</strong> {adjudicateModal.description}</div>
                <div><strong>Merchant Response:</strong> {adjudicateModal.merchant_response || 'No response recorded'}</div>
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-700 mb-1">
                  Formal Adjudication Verdict *
                </label>
                <select
                  value={resolutionAction}
                  onChange={(e) => setResolutionAction(e.target.value as 'resolved_refunded' | 'resolved_replaced' | 'resolved_rejected')}
                  className="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 bg-white"
                >
                  <option value="resolved_refunded">Uphold Customer: Enforce Full Refund</option>
                  <option value="resolved_replaced">Uphold Customer: Enforce Express Replacement</option>
                  <option value="resolved_rejected">Dismiss Claim: Uphold Merchant (No Fault Found)</option>
                </select>
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-700 mb-1">
                  Administrative Resolution Notes *
                </label>
                <textarea
                  required
                  rows={3}
                  value={adminNotes}
                  onChange={(e) => setAdminNotes(e.target.value)}
                  className="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500"
                />
              </div>

              <div className="flex justify-end gap-2 pt-2">
                <button
                  type="button"
                  onClick={() => setAdjudicateModal(null)}
                  className="px-4 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-100"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={adjudicating}
                  className="px-5 py-2 text-xs font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50"
                >
                  {adjudicating ? 'Submitting...' : 'Record Binding Adjudication'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Action / Review Modal */}
      {actionModal && (
        <div className="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl max-w-lg w-full shadow-2xl border border-slate-200 overflow-hidden">
            <div className="px-6 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
              <h3 className="font-bold text-slate-900 text-sm">
                Update Merchant Status: {actionModal.merchant.intentguard_id}
              </h3>
              <button
                onClick={() => setActionModal(null)}
                className="text-slate-400 hover:text-slate-600 font-bold"
              >
                ×
              </button>
            </div>

            <form onSubmit={handleStatusChange} className="p-6 space-y-4">
              {actionError && (
                <div className="p-3 bg-red-50 border border-red-200 text-red-700 text-xs rounded-lg">
                  {actionError}
                </div>
              )}

              <div className="text-xs text-slate-600 space-y-1">
                <div><strong>Merchant:</strong> {actionModal.merchant.business_name}</div>
                <div><strong>Target Status:</strong> <span className="uppercase font-bold text-indigo-600">{actionModal.action}</span></div>
                <div>
                  <strong>Agreement Signed:</strong>{' '}
                  {actionModal.merchant.agreement_accepted ? (
                    <span className="text-emerald-600 font-bold">Yes (Validated)</span>
                  ) : (
                    <span className="text-rose-600 font-bold">No (Verification requires signed agreement)</span>
                  )}
                </div>
              </div>

              <div>
                <label className="block text-xs font-semibold text-slate-700 mb-1">
                  Administrative Justification / Reason *
                </label>
                <textarea
                  required
                  rows={3}
                  value={actionReason}
                  onChange={(e) => setActionReason(e.target.value)}
                  className="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                  placeholder="Record verification findings or audit trail notes..."
                />
              </div>

              <div className="flex justify-end gap-2 pt-2">
                <button
                  type="button"
                  onClick={() => setActionModal(null)}
                  className="px-4 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-100"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={actionLoading}
                  className="px-4 py-2 text-xs font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50"
                >
                  {actionLoading ? 'Saving...' : 'Confirm Status Transition'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Audit Log Modal */}
      {auditLogs && (
        <div className="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
          <div className="bg-white rounded-2xl max-w-xl w-full shadow-2xl border border-slate-200 overflow-hidden">
            <div className="px-6 py-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
              <h3 className="font-bold text-slate-900 text-sm">
                Audit Trail: {auditLogs.merchantId}
              </h3>
              <button
                onClick={() => setAuditLogs(null)}
                className="text-slate-400 hover:text-slate-600 font-bold"
              >
                ×
              </button>
            </div>

            <div className="p-6 max-h-96 overflow-y-auto space-y-4">
              {auditLogs.logs.length === 0 ? (
                <p className="text-xs text-slate-500 text-center py-4">
                  No status transitions logged yet.
                </p>
              ) : (
                auditLogs.logs.map((log) => (
                  <div
                    key={log.id}
                    className="p-3 bg-slate-50 border border-slate-200 rounded-xl space-y-1 text-xs"
                  >
                    <div className="flex items-center justify-between">
                      <span className="font-semibold text-slate-800">
                        {log.previous_status} → <span className="text-indigo-600">{log.new_status}</span>
                      </span>
                      <span className="text-slate-400 text-[11px]">
                        {new Date(log.created_at).toLocaleString()}
                      </span>
                    </div>
                    <p className="text-slate-600">{log.reason}</p>
                    <div className="text-[11px] text-slate-500 pt-1">
                      Actioned by: {log.actioned_by_name || 'System / Admin'}
                    </div>
                  </div>
                ))
              )}
            </div>

            <div className="p-4 bg-slate-50 border-t border-slate-200 text-right">
              <button
                onClick={() => setAuditLogs(null)}
                className="px-4 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-100"
              >
                Close
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
