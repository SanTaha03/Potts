import { ensureCsrfCookie, http, resetCsrfState } from './http';

export interface AuthUser {
  id: number;
  name: string;
  email: string;
  role: 'client' | 'tech' | 'admin';
}

export interface LoginPayload {
  email: string;
  password: string;
  remember?: boolean;
}

export async function login(payload: LoginPayload): Promise<AuthUser> {
  await ensureCsrfCookie();
  const { data } = await http.post<{ user: AuthUser }>('/login', payload);
  return data.user;
}

export async function logout(): Promise<void> {
  await ensureCsrfCookie();
  await http.post('/logout');
  resetCsrfState();
}

export async function fetchCurrentUser(): Promise<AuthUser> {
  await ensureCsrfCookie();
  const { data } = await http.get<{ user: AuthUser }>('/me');
  return data.user;
}
