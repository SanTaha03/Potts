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
    label: 'Problème plante',
    to: { name: 'problems' },
    icon: 'ph:chat-circle-dots',
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
  <div class="sticky bottom-0 z-20 block bg-gradient-to-b from-transparent via-transparent to-white pb-5 pt-3 md:hidden">
    <nav class="mx-auto w-[92%] max-w-md rounded-full bg-gradient-to-r from-[#cbdba5] via-[#b8cf8c] to-[#a9c27b] p-2 shadow-[0_10px_25px_rgba(71,85,41,0.25)]">
      <ul class="grid grid-cols-4 gap-1">
        <li
          v-for="item in items"
          :key="item.key"
          class="flex flex-col items-center justify-center"
        >
          <button
            type="button"
            class="flex w-fit  items-center rounded-full p-3 text-xl font-medium tracking-tight text-[#2f2a24] transition"
            :class="isActive(item)
              ? 'bg-white text-[#564a3a] shadow-[0_6px_18px_rgba(60,47,34,0.2)]'
              : 'bg-transparent hover:text-[#3a2f24]'"
            @click="navigate(item)"
          >
            <Icon
              :icon="item.icon"
              class=""
            />
             <!-- <span class="text-sm font-semibold text-nowrap text-ellipsis">
              {{ item.label }}
            </span>  -->
          </button>
        </li>
      </ul>
    </nav>
  </div>
</template>
