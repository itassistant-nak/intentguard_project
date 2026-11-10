'use client';

import React, { useState } from 'react';
import { Merchant } from '../../../types/merchant';
import { merchantService } from '../../../services/merchantService';
import { MerchantStatusBadge } from './MerchantStatusBadge';

export function MerchantLookupWidget() {
  const [query, setQuery] = useState('');
  const [loading, setLoading] = useState(false);
  const [merchant, setMerchant] = useState<Merchant | null>(null);
  const [error, setError] = useState<string | null>(null);

  const handleLookup = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!query.trim()) return;

    setLoading(true);
    setError(null);
    setMerchant(null);

    try {
      const res = await merchantService.getByIdentifier(query.trim());
      setMerchant(res.data);
    } catch (err: unknown) {
      setError(err instanceof Error ? err.message : 'Merchant not found.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="bg-white rounded-2xl shadow-sm border border-slate-200/80 p-6 space-y-4">
      <div className="flex items-center justify-between">
        <h3 className="text-base font-bold text-[#101D35] flex items-center gap-2">
          <span className="w-2.5 h-2.5 rounded-full bg-[#12B981]" />
          Verify Merchant Integrity &amp; Compliance
        </h3>
        <span className="text-xs text-slate-500 font-mono">Live Directory</span>
      </div>

      <form onSubmit={handleLookup} className="flex gap-2">
        <input
          type="text"
          value={query}
          onChange={(e) => setQuery(e.target.value)}
          placeholder="Enter IntentGuard ID (e.g. IG-MCH-APEX0001)"
          className="flex-1 px-4 py-2 text-sm border border-slate-300 rounded-xl focus:ring-2 focus:ring-[#176BEB] focus:border-[#176BEB] focus:outline-none font-mono"
        />
        <button
          type="submit"
          disabled={loading}
          className="px-5 py-2 bg-[#176BEB] hover:bg-[#1258C2] text-white text-sm font-semibold rounded-xl shadow-xs disabled:opacity-50 transition-colors cursor-pointer"
        >
          {loading ? 'Verifying...' : 'Verify'}
        </button>
      </form>

      {error && (
        <div className="p-3 bg-red-50 border border-red-200 text-red-700 text-xs rounded-xl font-medium">
          {error}
        </div>
      )}

      {merchant && (
        <div className="mt-4 p-5 bg-[#F3F6FB] border border-slate-200 rounded-xl space-y-3 text-sm">
          <div className="flex items-start justify-between">
            <div>
              <h4 className="font-bold text-[#101D35] text-base">{merchant.business_name}</h4>
              <p className="text-xs text-slate-500 font-mono mt-0.5">{merchant.intentguard_id}</p>
            </div>
            <MerchantStatusBadge status={merchant.status} />
          </div>

          <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 pt-3 border-t border-slate-200 text-xs">
            <div>
              <span className="text-slate-500 block">Platform</span>
              <span className="font-semibold text-[#101D35] uppercase">{merchant.platform_type}</span>
            </div>
            <div>
              <span className="text-slate-500 block">Compliance Score</span>
              <span className="font-bold text-[#12B981] text-sm">
                {merchant.compliance_score} / 100
              </span>
            </div>
            <div>
              <span className="text-slate-500 block">Agreement</span>
              <span className="font-semibold text-[#101D35]">
                {merchant.agreement_accepted ? '✓ Accepted' : '✗ Pending'}
              </span>
            </div>
            <div>
              <span className="text-slate-500 block">Registered</span>
              <span className="font-semibold text-[#101D35]">
                {new Date(merchant.created_at).toLocaleDateString()}
              </span>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}

export default MerchantLookupWidget;
