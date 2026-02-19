<script setup lang="ts">
import { computed } from 'vue';
import { Icon } from '@iconify/vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/authStore';

const route = useRoute();
const router = useRouter();
const authStore = useAuthStore();

// Role-based Nav
const role = computed(() => authStore.currentUser?.role || 'client');

// Styling Configuration
const isTech = computed(() => role.value === 'tech');

const navClasses = computed(() => isTech.value 
  ? 'bg-[#1E1E1E]' 
  : 'bg-dark-green'
);

const activeItemClasses = computed(() => isTech.value
  ? 'bg-[#D0F471] text-[#1E1E1E] shadow-[0_10px_25px_rgba(0,0,0,0.25)] -translate-y-2 ring-4 ring-[#1E1E1E]'
  : 'bg-primary-green text-dark-green shadow-[0_10px_25px_rgba(0,0,0,0.25)] -translate-y-2 ring-4 ring-dark-green'
);

const inactiveItemClasses = computed(() => isTech.value
  ? 'text-gray-400 hover:text-white'
  : 'text-white hover:text-gray-200/90'
);

interface NavItem {
  key: string;
  label: string;
  icon: string;
  to: { name: string };
}

const clientItems: NavItem[] = [
  {
    key: 'dashboard',
    label: 'Dashboard',
    to: { name: 'dashboard' },
    icon: 'ph:squares-four-duotone',
  },
  {
    key: 'plants',
    label: 'Plantes',
    to: { name: 'plants' },
    icon: 'ph:plant-bold',
  },
  {
    key: 'problems',
    label: 'Scanner', // Keeping label as requested/mocked but linking to proper route if exists
    to: { name: 'problems' },
    icon: 'ph:qr-code',
  },
  {
    key: 'account',
    label: 'Mon compte',
    to: { name: 'account' },
    icon: 'ph:user-circle',
  },
];

const techItems: NavItem[] = [
  {
    key: 'tech-home',
    label: 'Accueil',
    to: { name: 'tech-home' },
    icon: 'ph:squares-four-bold',
  },
  {
    key: 'tech-nav', // Placeholder
    label: 'Navigation',
    to: { name: 'tech-nav' },
    icon: 'ph:car-bold',
  },
  {
    key: 'tech-scan', // Placeholder
    label: 'Scan',
    to: { name: 'tech-scan' }, // TODO: Link to real page
    icon: 'ph:scan-bold',
  },
  {
    key: 'tech-account', // Placeholder
    label: 'Mes infos',
    to: { name: 'tech-account' }, // TODO: Link to real page
    icon: 'ph:user-circle-bold',
  },
];

const items = computed<NavItem[]>(() => {
  if (role.value === 'tech') return techItems;
  return clientItems;
});

// Retourne true si l'élément correspond à la route active.
const isActive = (item: NavItem) => {
  // Simple check for now, can be expanded for children routes
  return route.name === item.key || (item.key === 'tech-home' && route.name === 'tech-mission');
};

// Navigue vers la cible en évitant les redirections redondantes.
const navigate = (item: NavItem) => {
  if (isActive(item)) {
    return;
  }
  router.push(item.to).catch(() => undefined);
};
</script>

<template>
  <div class="fixed mx-auto inset-x-0 bottom-0 z-20 block bg-gradient-to-b from-transparent via-transparent to-white pb-2 pt-2 md:hidden">
    <nav 
      class="mx-auto w-[92%] max-w-3xl h-fit px-3 rounded-2xl shadow-lg transition-colors duration-300"
      :class="navClasses"
    >
      <ul class="flex items-end justify-between gap-1">
        <li
          v-for="item in items"
          :key="item.key"
          class="relative flex w-20 flex-col items-center justify-end py-1"
        >
          <button
            type="button"
            class="flex h-14 w-full flex-col items-center justify-center gap-1 rounded-2xl transition-all duration-200 active:scale-95"
            :class="isActive(item) ? activeItemClasses : inactiveItemClasses"
            @click="navigate(item)"
          >
            <Icon :icon="item.icon" class="h-5 w-5" />
            <span class="text-[11px] font-popins font-medium">{{ item.label }}</span>
          </button>
        </li>
      </ul>
    </nav>
  </div>
</template>
