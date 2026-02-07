<script setup lang="ts">
import { computed, reactive } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/authStore';

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();

const form = reactive({
  email: 'tech@potts.app',
  password: 'password',
  remember: false,
});

const loading = computed(() => authStore.loading);
const error = computed(() => authStore.error);

async function submit() {
  if (loading.value) {
    return;
  }

  try {
    await authStore.login({
      email: form.email,
      password: form.password,
      remember: form.remember,
    });

    const redirect = typeof route.query.redirect === 'string' ? route.query.redirect : '/';
    router.replace(redirect || '/');
  } catch {
    // error already handled by the store
  }
}
</script>

<template>
  <section class="mx-auto flex min-h-[70vh] max-w-md flex-col justify-center px-6 py-12">
    <div class="mb-10 text-center">
      <h1 class="text-2xl font-semibold text-slate-900">Connexion</h1>
      <p class="mt-2 text-sm text-slate-500">
        Identifiez-vous pour accéder à vos devices.
      </p>
    </div>

    <form class="space-y-6 rounded-xl border border-slate-200 bg-white p-8 shadow-sm" @submit.prevent="submit">
      <div class="space-y-1">
        <label for="email" class="text-sm font-medium text-slate-700">Email</label>
        <input
          id="email"
          v-model="form.email"
          type="email"
          inputmode="email"
          autocomplete="email"
          required
          class="w-full rounded-lg border border-slate-200 px-4 py-2 text-sm shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200"
        />
      </div>

      <div class="space-y-1">
        <label for="password" class="text-sm font-medium text-slate-700">Mot de passe</label>
        <input
          id="password"
          v-model="form.password"
          type="password"
          autocomplete="current-password"
          required
          class="w-full rounded-lg border border-slate-200 px-4 py-2 text-sm shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200"
        />
      </div>

      <div class="flex items-center justify-between">
        <label class="flex items-center gap-2 text-sm text-slate-600">
          <input
            v-model="form.remember"
            type="checkbox"
            class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500"
          />
          Se souvenir de moi
        </label>
        <a href="/forgot-password" class="text-sm font-medium text-emerald-600 hover:text-emerald-500">
          Mot de passe oublié ?
        </a>
      </div>

      <div v-if="error" class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
        {{ error }}
      </div>

      <button
        type="submit"
        :disabled="loading"
        class="inline-flex w-full items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-emerald-500 disabled:cursor-not-allowed disabled:opacity-60"
      >
        <svg
          v-if="loading"
          class="-ms-1 me-2 h-4 w-4 animate-spin"
          fill="none"
          viewBox="0 0 24 24"
          xmlns="http://www.w3.org/2000/svg"
        >
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
          <path class="opacity-75" d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-linecap="round" stroke-width="4" />
        </svg>
        Se connecter
      </button>
    </form>
  </section>
</template>
