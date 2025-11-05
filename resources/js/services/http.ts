import axios from 'axios';

const rawApiBaseUrl = import.meta.env.VITE_API_BASE_URL ?? '/api';
const apiBaseUrl = normalizeApiBase(rawApiBaseUrl);
const csrfEndpoint = import.meta.env.VITE_CSRF_ENDPOINT ?? '/sanctum/csrf-cookie';

function normalizeApiBase(value: string): string {
  if (!value) {
    return '/api';
  }
  if (value.endsWith('/')) {
    return value.replace(/\/+$/, '');
  }
  return value;
}

function apiOrigin(): string {
  if (apiBaseUrl.startsWith('http')) {
    return new URL(apiBaseUrl).origin;
  }

  if (typeof window === 'undefined') {
    return 'http://localhost';
  }

  return window.location.origin;
}

type ErrorBag = Record<string, Array<string>>;

interface ErrorResponse {
  message?: string;
  errors?: ErrorBag;
}

export const http = axios.create({
  baseURL: apiBaseUrl,
  withCredentials: true,
  headers: {
    'X-Requested-With': 'XMLHttpRequest',
    Accept: 'application/json',
  },
});

let csrfReady = false;
const csrfClient = axios.create({
  baseURL: apiOrigin(),
  withCredentials: true,
  headers: {
    'X-Requested-With': 'XMLHttpRequest',
  },
});

export async function ensureCsrfCookie(): Promise<void> {
  if (csrfReady) {
    return;
  }

  await csrfClient.get(csrfEndpoint);
  csrfReady = true;
}

export function resetCsrfState(): void {
  csrfReady = false;
}

export function resolveHttpErrorMessage(error: unknown, fallback = 'Une erreur inattendue est survenue.'): string {
  if (axios.isAxiosError(error)) {
    const response = error.response?.data as ErrorResponse | undefined;

    const validationMessage = firstValidationMessage(response?.errors);
    if (validationMessage) {
      return validationMessage;
    }

    if (response?.message) {
      return response.message;
    }

    if (error.message) {
      return error.message;
    }
  }

  if (error instanceof Error && error.message) {
    return error.message;
  }

  return fallback;
}

function firstValidationMessage(errors?: ErrorBag): string | null {
  if (!errors) {
    return null;
  }

  const [firstField] = Object.keys(errors);
  if (!firstField) {
    return null;
  }

  const [firstMessage] = errors[firstField] ?? [];
  return firstMessage ?? null;
}
