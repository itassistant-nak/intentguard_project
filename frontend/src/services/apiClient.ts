const BASE_URL = process.env.NEXT_PUBLIC_API_URL || 'http://127.0.0.1:8000/api/v1';

export interface ApiResponse<T> {
  success: boolean;
  data: T;
  message?: string;
  error?: {
    code: string;
    message: string;
    details?: Record<string, string[]>;
  };
}

export class ApiError extends Error {
  status: number;
  code?: string;
  details?: Record<string, string[]>;

  constructor(message: string, status: number, code?: string, details?: Record<string, string[]>) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
    this.code = code;
    this.details = details;
  }
}

export async function apiRequest<T>(
  endpoint: string,
  options: RequestInit = {}
): Promise<ApiResponse<T>> {
  const url = `${BASE_URL}${endpoint}`;
  
  let token: string | null = null;
  if (typeof window !== 'undefined') {
    token = localStorage.getItem('intentguard_auth_token');
  }

  const headers: Record<string, string> = {
    'Content-Type': 'application/json',
    Accept: 'application/json',
    ...(token ? { Authorization: `Bearer ${token}` } : {}),
    ...((options.headers as Record<string, string>) || {}),
  };

  const response = await fetch(url, {
    ...options,
    headers,
  });

  const data = await response.json();

  if (!response.ok) {
    if (response.status === 401 && typeof window !== 'undefined') {
      localStorage.removeItem('intentguard_auth_token');
      localStorage.removeItem('intentguard_auth_user');
    }
    throw new ApiError(
      data.error?.message || data.message || `API error (${response.status})`,
      response.status,
      data.error?.code,
      data.error?.details
    );
  }

  return data;
}
