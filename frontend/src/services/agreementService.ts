import { apiRequest, ApiResponse } from './apiClient';
import {
  AcceptAgreementPayload,
  MerchantAgreement,
  MerchantAgreementAcceptance,
} from '../types/agreement';

export const agreementService = {
  async getActive(): Promise<ApiResponse<MerchantAgreement>> {
    return apiRequest<MerchantAgreement>('/agreements/active');
  },

  async accept(
    merchantId: string | number,
    payload: AcceptAgreementPayload
  ): Promise<ApiResponse<MerchantAgreementAcceptance>> {
    return apiRequest<MerchantAgreementAcceptance>(
      `/merchants/${merchantId}/agreements/accept`,
      {
        method: 'POST',
        body: JSON.stringify(payload),
      }
    );
  },

  async getMerchantAgreements(
    merchantId: string | number
  ): Promise<ApiResponse<MerchantAgreementAcceptance[]>> {
    return apiRequest<MerchantAgreementAcceptance[]>(
      `/merchants/${merchantId}/agreements`
    );
  },
};
