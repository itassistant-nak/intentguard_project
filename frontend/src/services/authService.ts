import { apiRequest, ApiResponse } from './apiClient';

export interface User {
  id: number;
  name: string;
  email: string;
  role: 'admin' | 'merchant' | 'customer';
}

export interface AuthResponse {
  token: string;
  token_type: string;
  user: User;
}

const TOKEN_KEY = 'intentguard_auth_token';
const USER_KEY = 'intentguard_auth_user';

export const authService = {
  async login(email: string, password: string): Promise<ApiResponse<AuthResponse>> {
    const res = await apiRequest<AuthResponse>('/auth/login', {
      method: 'POST',
      body: JSON.stringify({ email, password }),
    });

    if (res.success && res.data?.token) {
      if (typeof window !== 'undefined') {
        localStorage.setItem(TOKEN_KEY, res.data.token);
        localStorage.setItem(USER_KEY, JSON.stringify(res.data.user));
      }
    }

    return res;
  },

  async register(name: string, email: string, password: string, role: 'customer' | 'merchant' = 'customer'): Promise<ApiResponse<AuthResponse>> {
    const res = await apiRequest<AuthResponse>('/auth/register', {
      method: 'POST',
      body: JSON.stringify({ name, email, password, role }),
    });

    if (res.success && res.data?.token) {
      if (typeof window !== 'undefined') {
        localStorage.setItem(TOKEN_KEY, res.data.token);
        localStorage.setItem(USER_KEY, JSON.stringify(res.data.user));
      }
    }

    return res;
  },

  logout(): void {
    const token = this.getToken();
    if (token) {
      apiRequest('/auth/logout', {
        method: 'POST',
        headers: {
          Authorization: `Bearer ${token}`,
        },
      }).catch(() => {});
    }

    if (typeof window !== 'undefined') {
      localStorage.removeItem(TOKEN_KEY);
      localStorage.removeItem(USER_KEY);
    }
  },

  getToken(): string | null {
    if (typeof window === 'undefined') return null;
    return localStorage.getItem(TOKEN_KEY);
  },

  getUser(): User | null {
    if (typeof window === 'undefined') return null;
    const raw = localStorage.getItem(USER_KEY);
    if (!raw) return null;
    try {
      return JSON.parse(raw);
    } catch {
      return null;
    }
  },

  isAuthenticated(): boolean {
    return !!this.getToken();
  },

  isAdmin(): boolean {
    const user = this.getUser();
    return user?.role === 'admin';
  },
};
