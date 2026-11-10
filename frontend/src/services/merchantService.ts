import { apiRequest, ApiResponse } from './apiClient';
import { Merchant, RegisterMerchantPayload, StatusAuditLog } from '../types/merchant';

export const merchantService = {
  async register(payload: RegisterMerchantPayload): Promise<ApiResponse<Merchant>> {
    return apiRequest<Merchant>('/merchants/register', {
      method: 'POST',
      body: JSON.stringify(payload),
    });
  },

  async getByIdentifier(id: string | number): Promise<ApiResponse<Merchant>> {
    return apiRequest<Merchant>(`/merchants/${id}`);
  },

  async getAuditLogs(id: string | number): Promise<ApiResponse<StatusAuditLog[]>> {
    return apiRequest<StatusAuditLog[]>(`/merchants/${id}/audit-log`);
  },

  async updateStatus(
    id: string | number,
    status: string,
    reason: string
  ): Promise<ApiResponse<Merchant>> {
    return apiRequest<Merchant>(`/merchants/${id}/status`, {
      method: 'POST',
      body: JSON.stringify({ status, reason }),
    });
  },
};
