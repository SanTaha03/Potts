import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import { fetchCurrentUser, login as loginRequest, logout as logoutRequest } from '@/services/authService';
import { resolveHttpErrorMessage } from '@/services/http';
import type { AuthUser, LoginPayload } from '@/services/authService';

export const useAuthStore = defineStore('auth', () => {
  const currentUser = ref<AuthUser | null>(null);
  const loading = ref(false);
  const error = ref<string | null>(null);
  const bootstrapping = ref(false);
  const initialized = ref(false);

  const isAuthenticated = computed(() => currentUser.value !== null);

  function setUser(user: AuthUser | null) {
    currentUser.value = user;
  }

  function clearError() {
    error.value = null;
  }

  async function login(payload: LoginPayload): Promise<AuthUser> {
    loading.value = true;
    clearError();
    try {
      const user = await loginRequest(payload);
      setUser(user);
      initialized.value = true;
      return user;
    } catch (err) {
      error.value = resolveHttpErrorMessage(err, 'Connexion impossible.');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function logout(): Promise<void> {
    loading.value = true;
    clearError();
    try {
      await logoutRequest();
      setUser(null);
      initialized.value = true;
    } catch (err) {
      error.value = resolveHttpErrorMessage(err, 'La déconnexion a échoué.');
      throw err;
    } finally {
      loading.value = false;
    }
  }

  async function bootstrap(): Promise<void> {
    if (initialized.value || bootstrapping.value) {
      return;
    }

    bootstrapping.value = true;
    try {
      const user = await fetchCurrentUser();
      setUser(user);
    } catch {
      setUser(null);
    } finally {
      initialized.value = true;
      bootstrapping.value = false;
    }
  }

  return {
    currentUser,
    loading,
    error,
    bootstrapping,
    initialized,
    isAuthenticated,
    login,
    logout,
    bootstrap,
    setUser,
    clearError,
  };
});
