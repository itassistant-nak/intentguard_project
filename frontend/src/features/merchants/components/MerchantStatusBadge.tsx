'use client';

import React from 'react';
import { MerchantStatus } from '../../../types/merchant';

interface Props {
  status: MerchantStatus;
}

export function MerchantStatusBadge({ status }: Props) {
  const configs: Record<MerchantStatus, { label: string; bg: string; text: string; dot: string }> = {
    pending_review: {
      label: 'Pending Review',
      bg: 'bg-amber-50 border-amber-200',
      text: 'text-amber-700',
      dot: 'bg-amber-400',
    },
    under_review: {
      label: 'Under Review',
      bg: 'bg-blue-50 border-blue-200',
      text: 'text-blue-700',
      dot: 'bg-blue-400',
    },
    verified: {
      label: 'IntentGuard Verified',
      bg: 'bg-emerald-50 border-emerald-200',
      text: 'text-emerald-700',
      dot: 'bg-emerald-500',
    },
    rejected: {
      label: 'Application Rejected',
      bg: 'bg-rose-50 border-rose-200',
      text: 'text-rose-700',
      dot: 'bg-rose-500',
    },
    suspended: {
      label: 'Account Suspended',
      bg: 'bg-slate-100 border-slate-300',
      text: 'text-slate-700',
      dot: 'bg-slate-500',
    },
  };

  const config = configs[status] || configs.pending_review;

  return (
    <span
      className={`inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold border ${config.bg} ${config.text}`}
    >
      <span className={`w-2 h-2 rounded-full ${config.dot}`} />
      {config.label}
    </span>
  );
}
