'use client';

import React, { useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import Link from 'next/link';
import { authService, User } from '../../services/authService';
import { merchantService } from '../../services/merchantService';
import { Merchant, StatusAuditLog } from '../../types/merchant';
import { MerchantStatusBadge } from '../../features/merchants/components/MerchantStatusBadge';

export default function AdminDashboardPage() {
  const router = useRouter();
  const [user, setUser] = useState<User | null>(null);
  const [merchants, setMerchants] = useState<Merchant[]>([]);
  const [loading, setLoading] = useState(true);
  const [filter, setFilter] = useState<string>('all');
  const [auditLogs, setAuditLogs] = useState<{ merchantId: string; logs: StatusAuditLog[] } | null>(null);
  const [actionModal, setActionModal] = useState<{ merchant: Merchant; action: 'verify' | 'under_review' | 'reject' | 'suspend' } | null>(null);
  const [actionReason, setActionReason] = useState('');
  const [actionError, setActionError] = useState<string | null>(null);
  const [actionLoading, setActionLoading] = useState(false);

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
      setLoading(true);
      const res = await merchantService.getByIdentifier('');
      // apiClient with listMerchants
      const listRes = await fetch('http://127.0.0.1:8000/api/v1/merchants', {
        headers: {
          Accept: 'application/json',
          Authorization: `Bearer ${authService.getToken()}`,
        },
      });
      const data = await listRes.json();
      if (data.data) {
        setMerchants(data.data);
      }
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
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
    if (filter === 'all') return true;
    return m.status === filter;
  });

  const totalCount = merchants.length;
  const pendingCount = merchants.filter((m) => m.status === 'pending_review' || m.status === 'under_review').length;
  const verifiedCount = merchants.filter((m) => m.status === 'verified').length;

  return (
    <div className="min-h-screen bg-slate-100 flex flex-col text-slate-900">
      {/* Top Navbar */}
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
              Admin Console
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

      {/* Main Content */}
      <main className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full flex-1 space-y-8">
        {/* KPI Summary Cards */}
        <div className="grid grid-cols-1 sm:grid-cols-3 gap-5">
          <div className="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
            <span className="text-xs font-semibold text-slate-500 uppercase tracking-wider">
              Total Registered
            </span>
            <div className="text-2xl font-bold text-slate-900 mt-1">{totalCount}</div>
            <p className="text-xs text-slate-400 mt-1">E-Commerce merchant entities</p>
          </div>

          <div className="bg-white p-5 rounded-2xl border border-amber-200 shadow-sm bg-amber-50/20">
            <span className="text-xs font-semibold text-amber-700 uppercase tracking-wider">
              Pending Vetting
            </span>
            <div className="text-2xl font-bold text-amber-900 mt-1">{pendingCount}</div>
            <p className="text-xs text-amber-700 mt-1">Awaiting review or agreement</p>
          </div>

          <div className="bg-white p-5 rounded-2xl border border-emerald-200 shadow-sm bg-emerald-50/20">
            <span className="text-xs font-semibold text-emerald-700 uppercase tracking-wider">
              Verified & Operable
            </span>
            <div className="text-2xl font-bold text-emerald-900 mt-1">{verifiedCount}</div>
            <p className="text-xs text-emerald-700 mt-1">Active purchase protection</p>
          </div>
        </div>

        {/* Merchant Review Queue */}
        <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
          <div className="p-6 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
              <h2 className="text-lg font-bold text-slate-900">Merchant Applications & Governance</h2>
              <p className="text-xs text-slate-500 mt-0.5">
                Review registrations, enforce policy agreement verification, and update network status.
              </p>
            </div>

            {/* Filter Tabs */}
            <div className="flex gap-1.5 p-1 bg-slate-100 rounded-xl text-xs font-semibold">
              {(['all', 'pending_review', 'under_review', 'verified', 'rejected'] as const).map((tab) => (
                <button
                  key={tab}
                  onClick={() => setFilter(tab)}
                  className={`px-3 py-1.5 rounded-lg capitalize transition-all ${
                    filter === tab
                      ? 'bg-white text-slate-900 shadow-sm font-bold'
                      : 'text-slate-600 hover:text-slate-900'
                  }`}
                >
                  {tab.replace('_', ' ')}
                </button>
              ))}
            </div>
          </div>

          {loading ? (
            <div className="p-12 text-center text-sm text-slate-500">
              Loading merchant registry...
            </div>
          ) : filteredMerchants.length === 0 ? (
            <div className="p-12 text-center text-sm text-slate-500 space-y-2">
              <p>No merchants match the selected filter.</p>
              <Link
                href="/"
                className="text-xs text-indigo-600 font-semibold hover:underline"
              >
                Register a new test merchant →
              </Link>
            </div>
          ) : (
            <div className="overflow-x-auto">
              <table className="w-full text-left text-sm">
                <thead className="bg-slate-50 text-slate-500 text-xs font-semibold border-b border-slate-200 uppercase tracking-wider">
                  <tr>
                    <th className="px-6 py-3.5">IntentGuard ID</th>
                    <th className="px-6 py-3.5">Business / Legal Name</th>
                    <th className="px-6 py-3.5">Registry Number</th>
                    <th className="px-6 py-3.5">Platform</th>
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
                      <td className="px-6 py-4 font-mono text-xs text-slate-600">
                        {m.business_registration_number}
                      </td>
                      <td className="px-6 py-4 text-xs uppercase font-medium text-slate-700">
                        {m.platform_type}
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
      </main>

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
