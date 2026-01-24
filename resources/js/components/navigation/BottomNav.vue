<script setup lang="ts">
import { computed } from 'vue';
import { Icon } from '@iconify/vue';
import { useRoute, useRouter } from 'vue-router';

type NavKey = 'dashboard' | 'plants' | 'problems' | 'account';

interface NavItem {
  key: NavKey;
  label: string;
  icon: string;
  to: { name: NavKey };
}

const route = useRoute();
const router = useRouter();

const items = computed<NavItem[]>(() => [
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
    label: 'Scanner',
    to: { name: 'problems' },
    icon: 'ph:qr-code',
  },
  {
    key: 'account',
    label: 'Mon compte',
    to: { name: 'account' },
    icon: 'ph:user-circle',
  },
]);

// Retourne true si l'élément correspond à la route active.
const isActive = (item: NavItem) => route.name === item.key;

// Navigue vers la cible en évitant les redirections redondantes.
const navigate = (item: NavItem) => {
  if (isActive(item)) {
    return;
  }
  router.push(item.to).catch(() => undefined);
};
</script>

<template>
  <div class="sticky bottom-0 z-20 block bg-gradient-to-b from-transparent via-transparent to-white pb-2 pt-2 md:hidden">
    <nav class="mx-auto w-[92%] max-w-3xl h-fit bg-dark-green px-3 rounded-2xl shadow-lg">
      <ul class="flex items-end justify-between gap-1">
        <li
          v-for="item in items"
          :key="item.key"
          class="relative flex w-20 flex-col items-center justify-end py-1"
        >
          <button
            type="button"
            class="flex h-14 w-full flex-col items-center justify-center gap-1 rounded-2xl transition-all duration-200 active:scale-95"
            :class="isActive(item)
              ? 'bg-primary-green text-dark-green shadow-[0_10px_25px_rgba(0,0,0,0.25)] -translate-y-2 ring-4 ring-dark-green'
              : 'text-white hover:text-gray-200/90'"
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
