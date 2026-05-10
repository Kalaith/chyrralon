import axios from 'axios';
import { getActiveAuthToken, useAuthStore } from '../stores/authStore';

const configuredApiBase = import.meta.env.VITE_API_URL as string | undefined;
const configuredLoginUrl = import.meta.env.VITE_WEB_HATCHERY_LOGIN_URL as string | undefined;

if (!configuredApiBase || configuredApiBase.trim().length === 0) {
  throw new Error('VITE_API_URL is required');
}

if (!configuredLoginUrl || configuredLoginUrl.trim().length === 0) {
  throw new Error('VITE_WEB_HATCHERY_LOGIN_URL is required');
}

const apiBase = configuredApiBase.trim().replace(/\/$/, '');

/**
 * Standardized Web Hatchery Axios Instance
 * Automatically handles Bearer tokens and 401 Unauthorized login prompts.
 */
export const apiClient = axios.create({
  baseURL: apiBase,
  headers: {
    'Content-Type': 'application/json',
  },
});

// Request Interceptor: Attach Auth Token
apiClient.interceptors.request.use(
  config => {
    const token = getActiveAuthToken();
    const hasAuthorization = Boolean(config.headers.Authorization || config.headers.authorization);
    if (token && !hasAuthorization) {
      config.headers.Authorization = `Bearer ${token}`;
    }

    return config;
  },
  error => Promise.reject(error)
);

// Response Interceptor: Handle 401s and standardize errors
apiClient.interceptors.response.use(
  response => response,
  error => {
    if (axios.isAxiosError(error) && error.response?.status === 401) {
      const payload = error.response.data as { login_url?: string } | undefined;
      const loginUrl = payload?.login_url || configuredLoginUrl.trim();
      useAuthStore.getState().setLoginUrl(loginUrl);
      window.dispatchEvent(new CustomEvent('webhatchery:login-required', { detail: { loginUrl } }));
    }

    return Promise.reject(error);
  }
);
