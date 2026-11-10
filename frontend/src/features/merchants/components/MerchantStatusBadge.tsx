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
      bg: 'bg-[#EAA33A]/10 border-[#EAA33A]/30',
      text: 'text-[#A06813]',
      dot: 'bg-[#EAA33A]',
    },
    under_review: {
      label: 'Under Administrative Review',
      bg: 'bg-[#EAA33A]/15 border-[#EAA33A]/40',
      text: 'text-[#8C580A]',
      dot: 'bg-[#EAA33A]',
    },
    verified: {
      label: 'IntentGuard Verified',
      bg: 'bg-[#12B981]/10 border-[#12B981]/30',
      text: 'text-[#0E9B6C]',
      dot: 'bg-[#12B981]',
    },
    rejected: {
      label: 'Application Rejected',
      bg: 'bg-red-50 border-red-200',
      text: 'text-red-700',
      dot: 'bg-red-500',
    },
    suspended: {
      label: 'Account Suspended',
      bg: 'bg-red-100/70 border-red-300',
      text: 'text-red-800',
      dot: 'bg-red-600',
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

export default MerchantStatusBadge;
