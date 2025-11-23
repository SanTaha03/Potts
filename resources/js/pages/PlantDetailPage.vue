<script setup lang="ts">
import { computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { Icon } from '@iconify/vue';

const route = useRoute();
const router = useRouter();

const plant = computed(() => ({
  id: Number(route.params.id),
  name: 'Calathea',
  subtitle: 'Ma plante de bureau',
  location: 'Bureau 13L',
  exposure: {
    label: 'Sud/Est',
    status: 'Adéquate',
  },
  humidity: {
    value: 12,
    status: 'Insuffisante',
  },
  temperature: {
    value: 21,
    status: 'Adéquate',
  },
  image: 'https://images.unsplash.com/photo-1483799524323-66a1ad26c228?auto=format&fit=crop&w=680&q=80',
  description: `Le Monstera fait partie de la famille des Aracées, tout comme l’Anthurium et le Philodendron.

Ses tiges sont épaisses et ses feuilles, souvent vertes, sont grandes. Avec suffisamment de lumière et d’humidité,
les feuilles se développent, formant des incisions profondes et/ou des trous. Les feuilles matures peuvent atteindre
un diamètre d’un mètre !

En raison de la taille, des incisions et des trous des feuilles, cette plante a été nommée « Monstrum », ce qui signifie « monstrueux » en latin.`,
  waterLevel: 0.68,
}));

const weeklyHealth = [25, 32, 42, 58, 51, 66, 78];
const sameFloorPlants = [
  { id: 12, name: 'Calathea', health: 89, water: 0.7, icon: 'ph:flower-lotus-bold' },
  { id: 18, name: 'Strelitzia', health: 87, water: 0.35, icon: 'ph:flower-tulip-bold' },
  { id: 22, name: 'Monstera', health: 96, water: 0.6, icon: 'ph:leaf-bold' },
];

// Construit les points du graphique de santé en simple SVG.
const healthPolyline = computed(() => {
  const maxY = Math.max(...weeklyHealth);
  return weeklyHealth
    .map((value, index) => {
      const x = (index / (weeklyHealth.length - 1)) * 100;
      const y = 100 - (value / maxY) * 80;
      return `${x},${y}`;
    })
    .join(' ');
});

// Redirige vers la liste des plantes du bâtiment.
const goToPlants = () => {
  router.push({ name: 'plants' }).catch(() => undefined);
};
</script>

<template>
  <section class="space-y-6 pb-28">
    <div class="grid grid-cols-2 gap-6 rounded-3xl bg-[#f2eee9] p-5 shadow-[0_18px_45px_rgba(80,70,55,0.12)] lg:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)]">
      <div class="relative overflow-hidden rounded-3xl bg-white shadow-[0_12px_30px_rgba(80,70,55,0.15)]">
        <div
          class="h-80 w-full bg-cover bg-center"
          :style="{ backgroundImage: `url(${plant.image})` }"
        />
        <!-- Jauge d'eau -->
        <div class="absolute inset-y-6 left-4 flex w-5 gap-4 flex-col items-center justify-between rounded-full bg-[#eaf2fd] pt-3 pb-2 text-[#6da6f5] shadow">
          <Icon icon="ph:drop-fill" class="h-4 w-4" />
          <div class="h-full relative w-2 rounded-full bg-[#cbd7f4] ">
            <div
              class="absolute bottom-0 w-full rounded-full bg-[#7cb4ff]"
              :style="{ height: `${plant.waterLevel * 100}%` }"
            />
          </div>
        </div>
        <button
          type="button"
          class="absolute right-4 top-4 flex h-11 w-11 items-center justify-center rounded-full bg-white text-[#d28d7b] shadow"
        >
          <Icon icon="ph:heart-straight-bold" class="h-6 w-6" />
        </button>
      </div>

      <div class="flex flex-col justify-between gap-3">
        <div>
          <h1 class="text-2xl font-semibold text-[#5d7a38]">{{ plant.name }}</h1>
          <p class="mt-1 text-sm font-medium text-[#8a7c71]">{{ plant.subtitle }}</p>
        </div>

        <div class="rounded-2xl bg-white px-4 py-3 text-sm font-semibold text-[#5f5148] shadow">
          <div class="flex items-center justify-between gap-3">
            <span class="flex items-center gap-2">
              <Icon icon="ph:map-pin-line-duotone" class="h-5 w-5 text-[#857564]" />
              {{ plant.location }}
            </span>
            <button type="button" class="text-sm text-[#9b7f6b]">
              <Icon icon="ph:pencil-simple-line" class="h-5 w-5" />
            </button>
          </div>
        </div>

        <div class="rounded-3xl bg-white p-5 shadow-[0_12px_28px_rgba(74,64,51,0.12)]">
          <header class="mb-4 flex items-center justify-between text-[#5f5148]">
            <span class="text-base font-semibold">Santé global</span>
            <span class="rounded-full bg-[#eef7d8] px-3 py-1 text-xs font-semibold text-[#6c7a3d]">Stable</span>
          </header>
          <div class="h-36 w-full">
            <svg viewBox="0 0 100 100" class="h-full w-full" preserveAspectRatio="none">
              <defs>
                <linearGradient id="healthGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                  <stop offset="0%" stop-color="#8cc3ff" />
                  <stop offset="100%" stop-color="#5ea8f7" />
                </linearGradient>
              </defs>
              <polyline
                :points="healthPolyline"
                fill="none"
                stroke="url(#healthGradient)"
                stroke-width="4"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
              <line v-for="i in 7" :key="i" x1="0" :x2="100" :y1="i * 12" :y2="i * 12" stroke="#f1ede9" stroke-width="0.5" />
            </svg>
          </div>
          <div class="mt-2 flex justify-between text-[11px] font-semibold uppercase tracking-wide text-[#a09286]">
            <span>Lun</span>
            <span>Mar</span>
            <span>Mer</span>
            <span>Jeu</span>
            <span>Ven</span>
            <span>Sam</span>
            <span>Dim</span>
          </div>
        </div>
      </div>
    </div>
    <!-- card Sud/est -->
    <div class="grid grid-cols-3 gap-4 rounded-3xl bg-[#f2eee9] p-5 shadow-[0_18px_45px_rgba(80,70,55,0.12)] sm:grid-cols-3">
      <div class="flex flex-col gap-2 items-center justify-between rounded-2xl bg-white p-4 text-center shadow">
        <div class="flex flex-row gap-2">
          <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-[#f5ded0] text-[#c0784f]">
            <Icon icon="ph:sun-horizon-duotone" class="h-6 w-6" />
          </div>
          <p class="mt-3 text-sm font-semibold text-[#5f5148]">{{ plant.exposure.label }}</p>

        </div>
        <div class="h-full flex flex-col justify-center items-center">
          <p class=" text-xs uppercase tracking-wide text-[#a09286]">Exposition</p>
          <span class="mt-2 inline-flex rounded-full bg-[#e7efd1] px-3 py-1 text-xs font-semibold text-[#6c7a3d]">
            {{ plant.exposure.status }}
          </span>
        </div>
      </div>
      
      <!-- Card humidité -->
      <div class="flex flex-col gap-2 items-center justify-between rounded-2xl bg-white p-4 text-center shadow">
        <div class="flex flex-row gap-2 items-center">
          <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-[#dfeefd] text-[#5ea8f7]@">
            <Icon icon="ph:drop-duotone" class="h-6 w-6" />
          </div>
          <p class=" text-2xl font-semibold text-[#5f5148]">{{ plant.humidity.value }}%</p>
        </div>
        <div class="h-full flex flex-col justify-center items-center">
          <p class=" text-xs uppercase tracking-wide text-[#a09286]">Humidité</p>
          <span class="mt-2 inline-flex rounded-full bg-[#f9e0d6] px-3 py-1 text-xs font-semibold text-[#b86a49]">
            {{ plant.humidity.status }}
          </span>
        </div>
      </div>
      <!-- Reprendre d'ici en dupliquant la mise en page de la div au dessus -->
      
      <!-- Card température -->
      <div class="flex flex-col gap-2 items-center justify-between rounded-2xl bg-white p-4 text-center shadow">
        <div class="flex flex-row gap-2 items-center">
          <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-[#dfeefd] text-[#5ea8f7]@">
          <Icon icon="ph:thermometer-simple-duotone" class="h-6 w-6" />
          </div>
          <p class=" text-2xl font-semibold text-[#5f5148]">{{ plant.temperature.value }}º</p>
        </div>
        <div class="h-full flex flex-col justify-center items-center ">
          <p class=" text-xs uppercase tracking-wide text-[#a09286]">Température</p>
          <span class="mt-2 inline-flex rounded-full bg-[#e7efd1] px-3 py-1 text-xs font-semibold text-[#6c7a3d]">
            {{ plant.temperature.status }}
          </span>
        </div>
      </div>

      <!-- <div class="rounded-2xl bg-white p-4 text-center shadow">
        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-[#e1f2da] text-[#6c7a3d]">
          <Icon icon="ph:thermometer-simple-duotone" class="h-6 w-6" />
        </div>
        <p class="mt-3 text-sm font-semibold text-[#5f5148]">{{ plant.temperature.value }}º</p>
        <p class="mt-1 text-xs uppercase tracking-wide text-[#a09286]">Température</p>
        <span class="mt-2 inline-flex rounded-full bg-[#e7efd1] px-3 py-1 text-xs font-semibold text-[#6c7a3d]">
          {{ plant.temperature.status }}
        </span>
      </div> -->
    </div>

    <div class="space-y-4 rounded-3xl bg-white p-6 shadow-[0_18px_45px_rgba(80,70,55,0.12)]">
      <header class="flex items-center gap-3 text-[#5f5148]">
        <span class="flex h-12 w-12 items-center justify-center rounded-full bg-[#e7efd1] text-[#6c7a3d]">
          <Icon icon="ph:leaf-duotone" class="h-6 w-6" />
        </span>
        <h2 class="text-lg font-semibold">Descriptif de la plante</h2>
      </header>
      <article class="prose prose-sm max-w-none text-justify text-[#6f6255]">
        <p v-for="(paragraph, index) in plant.description.split('\n\n')" :key="index">
          {{ paragraph }}
        </p>
      </article>
    </div>

    <div class="space-y-4 rounded-3xl bg-white p-6 shadow-[0_18px_45px_rgba(80,70,55,0.12)]">
      <header class="flex items-center justify-between text-[#5f5148]">
        <div class="flex items-center gap-3">
          <span class="flex h-11 w-11 items-center justify-center rounded-full bg-[#f5ded0] text-[#bf7152]">
            <Icon icon="ph:flower-duotone" class="h-5 w-5" />
          </span>
          <h2 class="text-lg font-semibold">Plantes du même étage</h2>
        </div>
        <button
          type="button"
          class="flex items-center gap-1 rounded-full bg-[#f6efe8] px-4 py-2 text-xs font-semibold text-[#7b685d]"
          @click="goToPlants"
        >
          Voir tout
          <Icon icon="ph:caret-right" class="h-4 w-4" />
        </button>
      </header>

      <ul class="space-y-3">
        <li
          v-for="item in sameFloorPlants"
          :key="item.id"
          class="flex items-center justify-between rounded-2xl bg-[#f8f3ef] px-4 py-3"
        >
          <div class="flex items-center gap-3">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white text-[#c0784f] shadow">
              <Icon :icon="item.icon" class="h-5 w-5" />
            </span>
            <div>
              <p class="font-semibold text-[#5f5148]">{{ item.name }}</p>
              <p class="text-xs text-[#9b7f6b]">Santé : {{ item.health }}%</p>
            </div>
          </div>
          <div class="flex w-40 flex-col gap-1 text-xs text-[#9b7f6b]">
            <span>Quantité d'eau</span>
            <div class="h-2 rounded-full bg-[#e0d5ce]">
              <div
                class="h-full rounded-full bg-gradient-to-r from-[#8cc3ff] to-[#5ea8f7]"
                :style="{ width: `${item.water * 100}%` }"
              />
            </div>
          </div>
        </li>
      </ul>
    </div>
  </section>
</template>
