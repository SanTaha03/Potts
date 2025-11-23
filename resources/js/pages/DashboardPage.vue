<script setup lang="ts">
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import { Icon } from '@iconify/vue';

const router = useRouter();

const summary = {
  healthyPlants: 34,
  healthLabel: 'Bonnes santé',
  healthPercent: 82,
  avgTemperature: 19,
  avgHumidity: 63,
};

const visits = [
  {
    id: 'next',
    title: 'Prochaine visite',
    subtitle: 'Dans 6 jours',
    date: 'Mer. 30 Avril',
    icon: 'ph:arrow-right-up',
    actionIcon: 'ph:calendar-check',
    type: 'primary',
  },
  {
    id: 'last',
    title: 'Dernière visite',
    subtitle: 'Il y a 20 jours',
    date: 'Lun. 10 Avril',
    icon: 'ph:clock-counter-clockwise',
    actionIcon: 'ph:file-pdf',
    type: 'secondary',
  },
];

const recentPlants = [
  {
    id: 1,
    name: 'Calathea',
    health: 89,
    water: 0.7,
    icon: 'ph:flower-lotus-bold',
  },
  {
    id: 2,
    name: 'Strelitzia',
    health: 87,
    water: 0.45,
    icon: 'ph:flower-tulip-bold',
  },
  {
    id: 3,
    name: 'Monstera',
    health: 96,
    water: 0.82,
    icon: 'ph:leaf-bold',
  },
];

const healthGaugeStyle = computed(() => ({
  background: `conic-gradient(#e8796f 0deg ${Math.max(summary.healthPercent - 40, 0)}deg, #cce5a0 ${Math.max(
    summary.healthPercent - 40,
    0
  )}deg ${summary.healthPercent * 1.8}deg, #d9d9d9 ${summary.healthPercent * 1.8}deg 180deg)`,
}));

// Redirige vers la page des plantes depuis le raccourci "Voir tout".
const goToPlants = () => {
  router.push({ name: 'plants' }).catch(() => undefined);
};
</script>

<template>
  <section class="grid gap-6 pb-24">
    <div class="grid gap-4 rounded-3xl bg-gradient-to-b from-[#f8f4ec] via-[#f7efe0] to-[#f3ebdc] p-5">
      <div class="grid grid-cols-2 gap-3 sm:grid-cols-[minmax(0,1.5fr)_repeat(2,minmax(0,1fr))]">
        <div class="rounded-2xl bg-white p-5 shadow-[0_12px_28px_rgba(66,81,50,0.12)]">
          <div class="flex items-start justify-between">
            <div>
              <p class="text-5xl font-semibold text-[#3a2f24]">{{ summary.healthyPlants }}</p>
              <p class="mt-1 text-sm font-medium text-[#7a6c62]">Plantes en bonne santé</p>
            </div>
            <span class="rounded-lg bg-[#f1f5d8] px-2 py-1 text-xs font-semibold text-[#6d7a3d]">
              {{ summary.healthLabel }}
            </span>
          </div>
          <div class="mt-6 flex flex-col items-center">
            <div class="relative h-36 w-36">
              <div class="absolute inset-x-0 bottom-0 mx-auto h-32 w-32 overflow-hidden rounded-full">
                <div class="h-full w-full rounded-full p-3" :style="healthGaugeStyle">
                  <div class="h-full w-full rounded-full bg-[#fdfaf3]"></div>
                </div>
              </div>
              <div class="absolute bottom-1 left-1/2 flex -translate-x-1/2 gap-2 text-xs font-semibold text-[#8b7c70]">
                <span class="rounded-full bg-[#f7e6e0] px-2 py-0.5">État moyen</span>
                <span class="rounded-full bg-[#edf7d8] px-2 py-0.5 text-[#657736]">Bonne santé</span>
              </div>
            </div>
          </div>
        </div>

        <div class="flex flex-col justify-between h-full gap-3">
          <div class="flex-1 rounded-2xl bg-white  p-4 text-center shadow-[0_12px_28px_rgba(66,81,50,0.12)]">
            <div class="flex items-center gap-2 justify-start w-full overflow-hidden">
              <div class=" flex min-h-10 min-w-10 h-10 w-10 items-center justify-center rounded-full bg-[#e6eed3] text-[#6c7b3d]">
                <Icon icon="ph:thermometer-simple-duotone" class="h-5 w-5" />
              </div>
              <p class="text-xs font-semibold uppercase text-ellipsis tracking-wide text-[#7a6c62]">temp. moy.</p>
            </div>
            <p class="mt-3 text-4xl font-semibold text-[#3a2f24]">{{ summary.avgTemperature }}º</p>
          </div>
          <div class="flex-1 rounded-2xl bg-white  p-4 text-center shadow-[0_12px_28px_rgba(66,81,50,0.12)]">
            <div class="flex items-center gap-2 justify-start w-full overflow-hidden">
              <div class=" flex min-h-10 min-w-10 h-10 w-10 items-center justify-center rounded-full bg-[#d8ebf7] text-[#2d7593]">
                <Icon icon="ph:drop-duotone" class="h-5 w-5" />
              </div>
              <p class="text-xs font-semibold uppercase text-ellipsis tracking-wide text-[#7a6c62]">Humidité moy.</p>
            </div>
            <p class="mt-3 text-4xl font-semibold text-[#3a2f24]">{{ summary.avgHumidity }}%</p>
          </div>
        </div>
      </div>
    </div>

    <div class="space-y-5 rounded-3xl bg-white p-6 shadow-[0_16px_32px_rgba(51,61,36,0.12)]">
      <header class="flex items-center justify-between">
        <div class="flex items-center gap-3 text-[#5b3f2b]">
          <span class="flex h-10 w-10 items-center justify-center rounded-full bg-[#f1d7c7] text-[#a0603d]">
            <Icon icon="ph:user-list" class="h-5 w-5" />
          </span>
          <div>
            <h2 class="text-lg font-semibold">Visite du prestataire</h2>
            <p class="text-sm text-[#8d7e72]">Suivi des interventions et rapports associés</p>
          </div>
        </div>
        <button
          type="button"
          class="rounded-full bg-[#f9f1e7] px-3 py-2 text-xs font-semibold text-[#9a5c3d] shadow-sm"
        >
          Historique
        </button>
      </header>

      <ul class="space-y-3">
        <li
          v-for="visit in visits"
          :key="visit.id"
          class="flex items-center justify-between rounded-2xl bg-[#f5f1eb] px-4 py-3 text-sm text-[#4e4439]"
        >
          <div class="flex items-center gap-3">
            <span
              class="flex h-9 w-9 items-center justify-center rounded-xl"
              :class="visit.type === 'primary' ? 'bg-[#e9f1d8] text-[#6c7b3d]' : 'bg-[#ebe6e0] text-[#7a6c62]'"
            >
              <Icon :icon="visit.icon" class="h-5 w-5" />
            </span>
            <div>
              <p class="font-semibold text-[#3a2f24]">{{ visit.title }}</p>
              <p class="text-xs text-[#8d7e72]">{{ visit.subtitle }}</p>
            </div>
          </div>
          <div class="flex items-center gap-3">
            <p class="text-sm font-semibold text-[#3a2f24]">{{ visit.date }}</p>
            <button
              type="button"
              class="flex h-9 w-9 items-center justify-center rounded-xl bg-white text-[#7a6c62] shadow"
            >
              <Icon :icon="visit.actionIcon" class="h-5 w-5" />
            </button>
          </div>
        </li>
      </ul>
    </div>

    <div class="space-y-5 rounded-3xl bg-white p-6 shadow-[0_16px_32px_rgba(51,61,36,0.12)]">
      <header class="flex items-center justify-between">
        <div class="flex items-center gap-3 text-[#425133]">
          <span class="flex h-10 w-10 items-center justify-center rounded-full bg-[#e4f1d0] text-[#5f733d]">
            <Icon icon="ph:leaf-duotone" class="h-5 w-5" />
          </span>
          <h2 class="text-lg font-semibold">Plantes récemment consultées</h2>
        </div>
        <button
          type="button"
          class="flex items-center gap-1 rounded-full bg-[#f5f1eb] px-4 py-2 text-xs font-semibold text-[#5f5148]"
          @click="goToPlants"
        >
          Voir tout
          <Icon icon="ph:caret-right" class="h-4 w-4" />
        </button>
      </header>

      <ul class="space-y-3">
        <li
          v-for="plant in recentPlants"
          :key="plant.id"
          class="flex items-center justify-between rounded-2xl bg-[#f6f1ee] px-4 py-3"
        >
          <div class="flex items-center gap-3">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-white text-[#9a5c3d] shadow">
              <Icon :icon="plant.icon" class="h-5 w-5" />
            </span>
            <div>
              <p class="font-semibold text-[#3a2f24]">{{ plant.name }}</p>
              <p class="text-xs text-[#8d7e72]">Santé : {{ plant.health }}%</p>
            </div>
          </div>
          <div class="flex w-40 flex-col gap-1 text-xs text-[#7a6c62]">
            <div class="flex items-center justify-between">
              <span>Quantité d'eau</span>
            </div>
            <div class="h-2 rounded-full bg-[#ded6d2]">
              <div
                class="h-full rounded-full bg-gradient-to-r from-[#7fb4f6] to-[#57a6f2]"
                :style="{ width: `${plant.water * 100}%` }"
              />
            </div>
          </div>
        </li>
      </ul>
    </div>
  </section>
</template>
