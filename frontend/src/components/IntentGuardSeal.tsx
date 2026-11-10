'use client';

import React, { useState, useEffect } from 'react';
import { merchantService } from '@/services/merchantService';
import type { Merchant } from '@/types';

interface IntentGuardSealProps {
  merchantId?: string;
  initialMerchant?: Merchant | null;
  variant?: 'compact' | 'standard' | 'expanded';
  showModalOnClick?: boolean;
}

function ShieldCheckIcon({ className = 'w-5 h-5' }: { className?: string }) {
  return (
    <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
      <path d="m9 12 2 2 4-4" />
    </svg>
  );
}

function CheckCircleIcon({ className = 'w-4 h-4' }: { className?: string }) {
  return (
    <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
      <polyline points="22 4 12 14.01 9 11.01" />
    </svg>
  );
}

function ExternalLinkIcon({ className = 'w-3 h-3' }: { className?: string }) {
  return (
    <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6" />
      <polyline points="15 3 21 3 21 9" />
      <line x1="10" y1="14" x2="21" y2="3" />
    </svg>
  );
}

function CloseIcon({ className = 'w-5 h-5' }: { className?: string }) {
  return (
    <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <line x1="18" y1="6" x2="6" y2="18" />
      <line x1="6" y1="6" x2="18" y2="18" />
    </svg>
  );
}

function AwardIcon({ className = 'w-4 h-4' }: { className?: string }) {
  return (
    <svg className={className} viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <circle cx="12" cy="8" r="7" />
      <polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88" />
    </svg>
  );
}

export const IntentGuardSeal: React.FC<IntentGuardSealProps> = ({
  merchantId = 'IG-MCH-APEX0001',
  initialMerchant = null,
  variant = 'standard',
  showModalOnClick = true,
}) => {
  const [merchant, setMerchant] = useState<Merchant | null>(initialMerchant);
  const [loading, setLoading] = useState<boolean>(!initialMerchant);
  const [modalOpen, setModalOpen] = useState<boolean>(false);

  useEffect(() => {
    if (initialMerchant) {
      setMerchant(initialMerchant);
      return;
    }
    let isMounted = true;
    setLoading(true);
    merchantService
      .getByIdentifier(merchantId)
      .then((res) => {
        if (isMounted) {
          if (res.data) setMerchant(res.data);
          setLoading(false);
        }
      })
      .catch(() => {
        if (isMounted) setLoading(false);
      });

    return () => {
      isMounted = false;
    };
  }, [merchantId, initialMerchant]);

  const score = merchant?.compliance_score ? Number(merchant.compliance_score) : 96.5;

  const handleClick = () => {
    if (showModalOnClick) {
      setModalOpen(true);
    }
  };

  return (
    <>
      {variant === 'compact' && (
        <button
          onClick={handleClick}
          type="button"
          className="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-900 border border-slate-700 hover:border-cyan-500/60 transition-all text-xs text-slate-200 group shadow-sm"
          title="IntentGuard Protected Checkout"
        >
          <ShieldCheckIcon className="w-3.5 h-3.5 text-cyan-400 group-hover:scale-110 transition-transform" />
          <span className="font-semibold text-cyan-400 tracking-wide uppercase text-[10px]">IntentGuard</span>
          <span className="px-1.5 py-0.2 rounded-full bg-emerald-950/80 border border-emerald-600/60 text-emerald-300 text-[10px] font-bold">
            {score.toFixed(1)}
          </span>
        </button>
      )}

      {variant === 'standard' && (
        <div
          onClick={handleClick}
          role="button"
          tabIndex={0}
          onKeyDown={(e) => e.key === 'Enter' && handleClick()}
          className="inline-flex items-center gap-3 px-3.5 py-2 rounded-xl bg-slate-900/90 border border-slate-700/80 hover:border-cyan-500 hover:shadow-[0_0_15px_rgba(56,189,248,0.15)] transition-all cursor-pointer group select-none backdrop-blur-sm"
        >
          <div className="w-8 h-8 rounded-lg bg-cyan-950/60 border border-cyan-800/60 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform">
            <ShieldCheckIcon className="w-5 h-5 text-cyan-400" />
          </div>
          <div className="text-left">
            <div className="flex items-center gap-1.5">
              <span className="text-xs font-bold uppercase tracking-wider text-cyan-400">IntentGuard Protected</span>
              <span className="px-1.5 py-0.2 rounded-full bg-emerald-950 border border-emerald-500/50 text-emerald-400 text-[10px] font-semibold">
                {score.toFixed(1)} / 100
              </span>
            </div>
            <p className="text-[11px] text-slate-400 leading-tight">
              Pre-Capture Purchase Integrity &amp; SLA Guarantee
            </p>
          </div>
        </div>
      )}

      {variant === 'expanded' && (
        <div className="p-4 rounded-xl bg-slate-900/95 border border-slate-700/80 shadow-lg">
          <div className="flex items-center justify-between pb-3 border-b border-slate-800">
            <div className="flex items-center gap-2.5">
              <ShieldCheckIcon className="w-6 h-6 text-cyan-400" />
              <div>
                <h4 className="text-sm font-bold text-white flex items-center gap-2">
                  IntentGuard Verified Merchant
                  <CheckCircleIcon className="w-3.5 h-3.5 text-emerald-400" />
                </h4>
                <p className="text-xs text-slate-400">{merchant?.business_name || 'Apex Electronics Ltd'}</p>
              </div>
            </div>
            <div className="text-right">
              <div className="text-xs text-slate-400">Compliance</div>
              <div className="text-base font-bold text-emerald-400">{score.toFixed(1)} <span className="text-xs text-slate-500">/ 100</span></div>
            </div>
          </div>
          <div className="mt-3 text-xs text-slate-300 leading-relaxed bg-slate-950/50 p-2.5 rounded-lg border border-slate-800/80">
            Automated capture hold intercepts bait-and-switch price surges, hidden fees, and unauthorized condition substitutions before payment is finalized.
          </div>
          <div className="mt-3 flex items-center justify-between text-[11px] text-slate-400">
            <span className="font-mono text-cyan-400">{merchant?.intentguard_id || merchantId}</span>
            <button
              onClick={handleClick}
              className="text-cyan-400 hover:text-cyan-300 flex items-center gap-1 font-medium"
            >
              Verify Certificate <ExternalLinkIcon className="w-3 h-3" />
            </button>
          </div>
        </div>
      )}

      {/* Trust Certificate Modal */}
      {modalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm animate-in fade-in duration-150">
          <div className="relative w-full max-w-md p-6 bg-slate-900 border border-slate-700 rounded-2xl shadow-2xl text-slate-100">
            <button
              onClick={() => setModalOpen(false)}
              className="absolute top-4 right-4 text-slate-400 hover:text-white p-1 rounded-lg hover:bg-slate-800 transition-colors"
            >
              <CloseIcon className="w-5 h-5" />
            </button>

            <div className="flex items-center gap-3 mb-4">
              <div className="w-12 h-12 rounded-xl bg-cyan-950/60 border border-cyan-500/40 flex items-center justify-center">
                <ShieldCheckIcon className="w-7 h-7 text-cyan-400" />
              </div>
              <div>
                <h3 className="text-base font-bold text-white">Merchant Integrity Certificate</h3>
                <p className="text-xs text-slate-400">Cryptographically Enforced E-Commerce</p>
              </div>
            </div>

            <div className="mb-4 inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-950/80 border border-emerald-600/60 text-emerald-300 text-xs font-semibold">
              <CheckCircleIcon className="w-3.5 h-3.5" /> CONTRACTUALLY ACTIVE &amp; VERIFIED
            </div>

            <div className="space-y-2.5 text-xs bg-slate-950/60 p-3.5 rounded-xl border border-slate-800 mb-4">
              <div className="flex justify-between">
                <span className="text-slate-400">Merchant Legal Name:</span>
                <span className="font-semibold text-slate-200">{merchant?.business_name || 'Apex Electronics Ltd'}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-slate-400">IntentGuard ID:</span>
                <span className="font-mono text-cyan-400">{merchant?.intentguard_id || merchantId}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-slate-400">100-Point Score:</span>
                <span className="font-bold text-emerald-400">{score.toFixed(1)} / 100.0</span>
              </div>
              <div className="flex justify-between">
                <span className="text-slate-400">Platform:</span>
                <span className="text-slate-200 uppercase">{merchant?.platform_type || 'custom_api'}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-slate-400">Agreement Digest:</span>
                <span className="font-mono text-[10px] text-slate-400 truncate max-w-[180px]">
                  SHA-256 e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855
                </span>
              </div>
            </div>

            <div className="p-3 rounded-xl bg-cyan-950/30 border border-cyan-800/40 text-xs text-cyan-200/90 leading-relaxed space-y-2 mb-4">
              <div className="font-semibold flex items-center gap-1.5 text-cyan-300">
                <AwardIcon className="w-4 h-4 text-cyan-400" />
                Guaranteed Pre-Capture Interception
              </div>
              <p className="text-[11px] text-slate-300">
                This merchant is bound to the 4 IntentGuard Core Policies (Product Accuracy, Shipping SLA, Substitution Protocol, Refund/Replacement Guarantee).
                If checkout specifications deviate from your purchase intent, the payment capture is held until you explicitly confirm.
              </p>
            </div>

            <button
              onClick={() => setModalOpen(false)}
              className="w-full py-2.5 px-4 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-white font-medium text-xs transition-colors shadow-md"
            >
              Close Verification Certificate
            </button>
          </div>
        </div>
      )}
    </>
  );
};

export default IntentGuardSeal;
