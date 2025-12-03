<script setup lang="ts">
import { computed, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { Icon } from '@iconify/vue';

interface Plant {
  id: number;
  name: string;
  building: string;
  floor: string;
  waterLevel: number;
  status: 'ok' | 'alert';
  image: string;
}

const router = useRouter();

const filters = reactive({
  building: 'Bâtiment A',
  floor: 'Étage 3',
  search: '',
});

const buildings = ['Bâtiment A', 'Bâtiment B', 'Bâtiment C'];
const floors = ['Étage 1', 'Étage 2', 'Étage 3', 'Étage 4'];

const plants = ref<Plant[]>([
  {
    id: 1,
    name: 'Monstera',
    building: 'Bâtiment A',
    floor: 'Étage 3',
    waterLevel: 0.7,
    status: 'alert',
    image: 'https://images.unsplash.com/photo-1501004318641-b39e6451bec6?auto=format&fit=crop&w=400&q=80',
  },
  {
    id: 2,
    name: 'Bananier',
    building: 'Bâtiment A',
    floor: 'Étage 3',
    waterLevel: 0.55,
    status: 'ok',
    image: 'https://images.unsplash.com/photo-1498855926480-d98e83099315?auto=format&fit=crop&w=480&q=80',
  },
  {
    id: 3,
    name: 'Ficus',
    building: 'Bâtiment B',
    floor: 'Étage 2',
    waterLevel: 0.23,
    status: 'alert',
    image: 'https://images.unsplash.com/photo-1587502536263-968ebc0b73d0?auto=format&fit=crop&w=480&q=80',
  },
  {
    id: 4,
    name: 'Yucca',
    building: 'Bâtiment A',
    floor: 'Étage 1',
    waterLevel: 0.45,
    status: 'ok',
    image: 'https://images.unsplash.com/photo-1545239351-11e2c67d53ee?auto=format&fit=crop&w=480&q=80',
  },
  {
    id: 5,
    name: 'Pachicia',
    building: 'Bâtiment C',
    floor: 'Étage 3',
    waterLevel: 0.23,
    status: 'alert',
    image: 'https://images.unsplash.com/photo-1524593166156-312f362cada3?auto=format&fit=crop&w=480&q=80',
  },
  {
    id: 6,
    name: 'Codieaum',
    building: 'Bâtiment C',
    floor: 'Étage 4',
    waterLevel: 0.6,
    status: 'ok',
    image: 'https://images.unsplash.com/photo-1573878735868-80f5073c063c?auto=format&fit=crop&w=480&q=80',
  },
]);

const filteredPlants = computed(() => {
  const query = filters.search.trim().toLowerCase();
  return plants.value.filter((plant) => {
    const matchesBuilding = plant.building === filters.building;
    const matchesFloor = plant.floor === filters.floor;
    const matchesQuery = query ? plant.name.toLowerCase().includes(query) : true;
    return matchesBuilding && matchesFloor && matchesQuery;
  });
});

const triggerScan = () => {
  alert('Scanner QR: fonctionnalité à venir');
};

const triggerFilters = () => {
  console.info('Filters panel coming soon');
};

const openProblemReport = (plant: Plant) => {
  router.push({ name: 'report-problem', params: { id: plant.id } }).catch(() => undefined);
};
</script>

<template>
  <section class="space-y-6 pb-24">
    <div class="rounded-3xl bg-[#f4ece7] p-5 shadow-[0_12px_36px_rgba(90,80,60,0.12)]">
      <button
        type="button"
        class="flex w-full items-center justify-center gap-3 rounded-3xl bg-gradient-to-r from-[#da9e86] to-[#d68977] px-5 py-4 text-sm font-semibold text-white shadow"
        @click="triggerScan"
      >
        <Icon icon="ph:qr-code-duotone" class="h-5 w-5" />
        Scanner le QR d'une plante
      </button>
      <p class="my-4 text-center text-xs font-semibold uppercase tracking-wide text-[#a09388]">ou</p>
      <div class="flex flex-col gap-4 sm:flex-row">
        <div class="flex-1 rounded-full border border-[#deae96] bg-[#fcefe7] px-4 py-2.5 text-sm font-semibold text-[#8a4e37]">
          <label class="flex items-center justify-between">
            <select v-model="filters.building" class="w-full bg-transparent focus:outline-none">
              <option v-for="building in buildings" :key="building">{{ building }}</option>
            </select>
            <Icon icon="ph:caret-down" class="h-4 w-4" />
          </label>
        </div>
        <div class="flex-1 rounded-full border border-[#b9a59a] bg-[#f5ece7] px-4 py-2.5 text-sm font-semibold text-[#6f6157]">
          <label class="flex items-center justify-between">
            <select v-model="filters.floor" class="w-full bg-transparent focus:outline-none">
              <option v-for="floor in floors" :key="floor">{{ floor }}</option>
            </select>
            <Icon icon="ph:caret-down" class="h-4 w-4" />
          </label>
        </div>
      </div>
    </div>

    <div class="flex flex-row gap-3 rounded-3xl bg-white p-4 shadow-[0_12px_40px_rgba(90,80,60,0.08)] sm:flex-row sm:items-center">
      <div class="relative flex-1">
        <Icon icon="ph:magnifying-glass" class="absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-[#b8aba1]" />
        <input
          v-model="filters.search"
          type="search"
          placeholder="Search"
          class="w-full rounded-full bg-[#f6f0eb] py-3 pl-12 pr-4 text-sm text-[#5f5148] placeholder:text-[#c7bbb1] focus:outline-none focus:ring-2 focus:ring-[#c8d7a4]"
        />
      </div>
      <button
        type="button"
        class="flex h-11 w-11 items-center justify-center rounded-full border border-[#c4b2a6] bg-[#f9f5f1] text-[#5f5148] shadow"
        @click="triggerFilters"
      >
        <Icon icon="ph:sliders-horizontal" class="h-5 w-5" />
      </button>
    </div>

    <div class="grid gap-4 rounded-3xl bg-[#f7f3ef] p-4 shadow-[0_12px_30px_rgba(90,80,60,0.1)]">
      <div
        v-if="!filteredPlants.length"
        class="flex min-h-[240px] flex-col items-center justify-center rounded-2xl bg-white/70 text-sm text-[#8d7e72]"
      >
        Aucune plante ne correspond à vos filtres actuels.
      </div>
      <ul v-else class="grid grid-cols-2 gap-3 md:grid-cols-3">
        <li
          v-for="plant in filteredPlants"
          :key="plant.id"
          class="relative overflow-hidden rounded-3xl bg-white shadow-[0_12px_25px_rgba(80,70,55,0.16)] transition hover:-translate-y-1 hover:shadow-[0_16px_30px_rgba(80,70,55,0.2)]"
        >
          <button type="button" class="flex h-full w-full flex-col text-left" @click="openProblemReport(plant)">
            <div
              class="relative h-32 w-full overflow-hidden bg-[#d9c9b8]"
              :style="{ backgroundImage: `url(${plant.image})`, backgroundSize: 'cover', backgroundPosition: 'center' }"
            >
              <span
                class="absolute left-3 top-3 rounded-full px-3 py-1 text-sm font-semibold"
                :class="plant.status === 'ok'
                  ? 'bg-[#dbe9c4] text-[#4f6631]'
                  : 'bg-[#f5d7cc] text-[#a35b3d]'"
              >
                {{ plant.name }}
                <Icon
                  v-if="plant.status === 'alert'"
                  icon="ph:warning-duotone"
                  class="ml-1 inline h-4 w-4 align-text-bottom"
                />
              </span>
            </div>
            <div class="flex items-center gap-2 rounded-3xl bg-white px-3 py-2">
              <Icon icon="ph:drop-fill" class="h-5 w-5 text-[#76a7f3]" />
              <div class="h-2 flex-1 rounded-full bg-[#e5d7ce]">
                <div
                  class="h-full rounded-full bg-gradient-to-r from-[#8cc3ff] to-[#5fa8f6]"
                  :style="{ width: `${plant.waterLevel * 100}%` }"
                />
              </div>
            </div>
          </button>
        </li>
      </ul>
    </div>
  </section>
</template>
