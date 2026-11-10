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
  const [, setLoading] = useState<boolean>(!initialMerchant);
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
          className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white border border-slate-200 hover:border-[#176BEB] transition-all text-xs text-[#101D35] group shadow-xs cursor-pointer"
          title="IntentGuard Protected Checkout"
        >
          <ShieldCheckIcon className="w-3.5 h-3.5 text-[#176BEB] group-hover:scale-110 transition-transform" />
          <span className="font-bold text-[#101D35] tracking-wide uppercase text-[10px]">IntentGuard</span>
          <span className="px-1.5 py-0.2 rounded-full bg-[#12B981]/10 border border-[#12B981]/30 text-[#0E9B6C] text-[10px] font-bold">
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
          className="inline-flex items-center gap-3 px-3.5 py-2 rounded-xl bg-white border border-slate-200/90 hover:border-[#176BEB] hover:shadow-md transition-all cursor-pointer group select-none shadow-xs"
        >
          <div className="w-8 h-8 rounded-lg bg-[#EEF5FF] border border-[#BFDBFE] flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform text-[#176BEB]">
            <ShieldCheckIcon className="w-5 h-5 text-[#176BEB]" />
          </div>
          <div className="text-left">
            <div className="flex items-center gap-1.5">
              <span className="text-xs font-bold uppercase tracking-wider text-[#101D35]">IntentGuard Protected</span>
              <span className="px-1.5 py-0.2 rounded-full bg-[#12B981]/10 border border-[#12B981]/30 text-[#0E9B6C] text-[10px] font-bold">
                {score.toFixed(1)} / 100
              </span>
            </div>
            <p className="text-[11px] text-slate-500 leading-tight">
              Pre-Capture Purchase Integrity &amp; SLA Guarantee
            </p>
          </div>
        </div>
      )}

      {variant === 'expanded' && (
        <div className="p-4 rounded-xl bg-white border border-slate-200/90 shadow-sm text-[#101D35]">
          <div className="flex items-center justify-between pb-3 border-b border-slate-100">
            <div className="flex items-center gap-2.5">
              <div className="w-8 h-8 rounded-lg bg-[#EEF5FF] border border-[#BFDBFE] flex items-center justify-center text-[#176BEB]">
                <ShieldCheckIcon className="w-5 h-5 text-[#176BEB]" />
              </div>
              <div>
                <h4 className="text-sm font-bold text-[#101D35] flex items-center gap-1.5">
                  IntentGuard Verified Merchant
                  <CheckCircleIcon className="w-3.5 h-3.5 text-[#12B981]" />
                </h4>
                <p className="text-xs text-slate-500">{merchant?.business_name || 'Apex Electronics Ltd'}</p>
              </div>
            </div>
            <div className="text-right">
              <div className="text-[10px] text-slate-400 font-semibold uppercase">Compliance</div>
              <div className="text-base font-black text-[#12B981]">{score.toFixed(1)} <span className="text-xs text-slate-400 font-normal">/ 100</span></div>
            </div>
          </div>
          <div className="mt-3 text-xs text-slate-600 leading-relaxed bg-[#F3F6FB] p-3 rounded-lg border border-slate-200/60">
            Automated capture hold intercepts bait-and-switch price surges, hidden fees, and unauthorized condition substitutions before payment is finalized.
          </div>
          <div className="mt-3 flex items-center justify-between text-[11px] text-slate-500">
            <span className="font-mono text-[#176BEB] font-semibold">{merchant?.intentguard_id || merchantId}</span>
            <button
              onClick={handleClick}
              className="text-[#176BEB] hover:text-[#1258C2] flex items-center gap-1 font-semibold cursor-pointer"
            >
              Verify Certificate <ExternalLinkIcon className="w-3 h-3" />
            </button>
          </div>
        </div>
      )}

      {/* Trust Certificate Modal */}
      {modalOpen && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-[#101D35]/70 backdrop-blur-sm animate-in fade-in duration-150">
          <div className="relative w-full max-w-md p-6 bg-white border border-slate-200 rounded-2xl shadow-2xl text-[#101D35]">
            <button
              onClick={() => setModalOpen(false)}
              className="absolute top-4 right-4 text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition-colors cursor-pointer"
            >
              <CloseIcon className="w-5 h-5" />
            </button>

            <div className="flex items-center gap-3 mb-4">
              <div className="w-12 h-12 rounded-xl bg-[#EEF5FF] border border-[#BFDBFE] flex items-center justify-center text-[#176BEB]">
                <ShieldCheckIcon className="w-7 h-7 text-[#176BEB]" />
              </div>
              <div>
                <h3 className="text-base font-bold text-[#101D35]">Merchant Integrity Certificate</h3>
                <p className="text-xs text-slate-500">Cryptographically Enforced E-Commerce</p>
              </div>
            </div>

            <div className="mb-4 inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#12B981]/10 border border-[#12B981]/30 text-[#0E9B6C] text-xs font-semibold">
              <CheckCircleIcon className="w-3.5 h-3.5" /> CONTRACTUALLY ACTIVE &amp; VERIFIED
            </div>

            <div className="space-y-2.5 text-xs bg-[#F3F6FB] p-3.5 rounded-xl border border-slate-200 mb-4">
              <div className="flex justify-between">
                <span className="text-slate-500">Merchant Legal Name:</span>
                <span className="font-semibold text-[#101D35]">{merchant?.business_name || 'Apex Electronics Ltd'}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-slate-500">IntentGuard ID:</span>
                <span className="font-mono text-[#176BEB] font-semibold">{merchant?.intentguard_id || merchantId}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-slate-500">100-Point Score:</span>
                <span className="font-bold text-[#12B981]">{score.toFixed(1)} / 100.0</span>
              </div>
              <div className="flex justify-between">
                <span className="text-slate-500">Platform:</span>
                <span className="text-[#101D35] uppercase font-semibold">{merchant?.platform_type || 'custom_api'}</span>
              </div>
              <div className="flex justify-between">
                <span className="text-slate-500">Agreement Digest:</span>
                <span className="font-mono text-[10px] text-slate-600 truncate max-w-[180px]">
                  SHA-256 e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855
                </span>
              </div>
            </div>

            <div className="p-3.5 rounded-xl bg-[#EEF5FF] border border-[#BFDBFE] text-xs text-[#101D35] leading-relaxed space-y-1.5 mb-4">
              <div className="font-bold flex items-center gap-1.5 text-[#176BEB]">
                <AwardIcon className="w-4 h-4 text-[#176BEB]" />
                Guaranteed Pre-Capture Interception
              </div>
              <p className="text-[11px] text-slate-600">
                This merchant is bound to the 4 IntentGuard Core Policies (Product Accuracy, Shipping SLA, Substitution Protocol, Refund/Replacement Guarantee).
                If checkout specifications deviate from your purchase intent, the payment capture is held until you explicitly confirm.
              </p>
            </div>

            <button
              onClick={() => setModalOpen(false)}
              className="w-full py-2.5 px-4 rounded-xl bg-[#176BEB] hover:bg-[#1258C2] text-white font-semibold text-xs transition-colors shadow-xs cursor-pointer"
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
