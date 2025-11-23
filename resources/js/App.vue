<template>
  <div class="flex min-h-screen flex-col bg-slate-50 text-slate-900">
    <header class="border-b border-slate-200 bg-white">
      <div class="mx-auto flex max-w-5xl items-center justify-between px-6 py-4">
        <RouterLink to="/" class="text-lg font-semibold text-emerald-600">
          Potts
        </RouterLink>
        <nav v-if="isAuthenticated" class="hidden items-center gap-6 text-sm text-slate-600 md:flex">
          <RouterLink
            v-for="item in desktopNav"
            :key="item.name"
            :to="item.to"
            class="transition hover:text-emerald-600"
            active-class="text-emerald-600 font-medium"
          >
            {{ item.label }}
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
            class="rounded-full bg-white p-4 text-lg font-medium text-green transition hover:bg-gray-200"
            @click="handleLogout"
          >
            <Icon icon="mdi:logout" />
          </button>
        </div>
      </div>
    </header>

    <main class="mx-auto flex w-full max-w-5xl flex-1 px-4 py-8 sm:px-6 sm:py-10">
      <RouterView class="w-full" />
    </main>

    <BottomNav v-if="isAuthenticated" />
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { RouterLink, RouterView, useRouter } from 'vue-router';
import BottomNav from '@/components/navigation/BottomNav.vue';
import { useAuthStore } from '@/stores/authStore';
import { Icon } from '@iconify/vue';

const router = useRouter();
const authStore = useAuthStore();

const currentUser = computed(() => authStore.currentUser);
const isAuthenticated = computed(() => authStore.isAuthenticated);

const desktopNav = [
  { name: 'dashboard', label: 'Tableau de bord', to: { name: 'dashboard' } },
  { name: 'plants', label: 'Plantes', to: { name: 'plants' } },
  { name: 'problems', label: 'Problèmes', to: { name: 'problems' } },
  { name: 'account', label: 'Mon compte', to: { name: 'account' } },
];

// Gère la déconnexion puis redirige vers la page de connexion.
async function handleLogout() {
  try {
    await authStore.logout();
  } finally {
    router.push({ name: 'login' });
  }
}
</script>
