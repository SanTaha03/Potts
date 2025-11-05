<template>
  <div class="min-h-screen bg-slate-50 text-slate-900">
    <header class="border-b border-slate-200 bg-white">
      <div class="mx-auto flex max-w-5xl items-center justify-between px-6 py-4">
        <RouterLink to="/" class="text-lg font-semibold text-emerald-600">
          Potts
        </RouterLink>
        <nav class="flex items-center gap-6 text-sm text-slate-600">
          <RouterLink
            v-if="isAuthenticated"
            to="/"
            class="transition hover:text-emerald-600"
            active-class="text-emerald-600 font-medium"
          >
            Devices
          </RouterLink>
        </nav>
        <div class="flex items-center gap-3 text-sm">
          <span v-if="currentUser" class="hidden text-slate-500 sm:block">
            Bonjour, <span class="font-semibold text-slate-700">{{ currentUser.name }}</span>
          </span>
          <RouterLink
            v-if="!isAuthenticated"
            to="/login"
            class="rounded-lg border border-emerald-200 px-4 py-2 text-sm font-medium text-emerald-600 transition hover:bg-emerald-50"
          >
            Se connecter
          </RouterLink>
          <button
            v-else
            type="button"
            class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-emerald-500"
            @click="handleLogout"
          >
            Se déconnecter
          </button>
        </div>
      </div>
    </header>

    <main class="mx-auto max-w-5xl px-6 py-10">
      <RouterView />
    </main>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { RouterLink, RouterView, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/authStore';

const router = useRouter();
const authStore = useAuthStore();

const currentUser = computed(() => authStore.currentUser);
const isAuthenticated = computed(() => authStore.isAuthenticated);

async function handleLogout() {
  try {
    await authStore.logout();
  } finally {
    router.push({ name: 'login' });
  }
}
</script>
