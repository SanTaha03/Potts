<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue';
import { useRouter } from 'vue-router';
import { Icon } from '@iconify/vue';
import WaterGauge from '@/components/charts/WaterGauge.vue';
import { useDeviceStore } from '@/stores/deviceStore';

interface Plant {
  id: number;
  name: string;
  building: string;
  floor: string;
  waterLevel: number;
  status: 'ok' | 'alert' | 'offline'; // Added offline
  image: string;
}

const router = useRouter();
const deviceStore = useDeviceStore();

const viewMode = ref<'list' | 'plan'>('list');

const filters = reactive({
  building: 'Showroom',
  floor: 'Étage 1',
  search: '',
});

onMounted(() => {
  deviceStore.fetchDevices();
});

// Map API devices to Plant interface for the UI
const plants = computed<Plant[]>(() => {
  return deviceStore.items.map(d => ({
    id: d.id,
    name: d.name || d.device_id, // fallback to ID
    // Derive building/floor from location or defaults
    building: d.location?.site || 'Showroom',
    floor: d.location?.floor ? `Étage ${d.location.floor}` : 'Rez-de-chaussée',
    // Calculate water level 0-1 from 0-100 soil_pct
    waterLevel: (d.last_values?.soil_pct ?? 0) / 100,
    // Status logic
    status: d.is_online ? 'ok' : 'offline', 
    image: '/images/monstera.png' // Placeholder for now
  }));
});

const buildings = ['Bâtiment A', 'Bâtiment B', 'Bâtiment C',  'Showroom'];
const floors = ['Rez-de-chaussée', 'Étage 1', 'Étage 2', 'Étage 3', 'Étage 4'];
const showBuildingSheet = ref(false);
const showFloorSheet = ref(false);

// Data Cleaned

const filteredPlants = computed(() => {
  const query = filters.search.trim().toLowerCase();
  return plants.value.filter((plant) => {
    const matchBuilding = plant.building === filters.building;
    const matchFloor = plant.floor === filters.floor;
    const matchQuery = query ? plant.name.toLowerCase().includes(query) : true;
    return matchBuilding && matchFloor && matchQuery;
  });
});

// Ouvre la carte en redirigeant vers la future page détail.
const openPlantDetail = (plant: Plant) => {
  router.push({ name: 'plant-detail', params: { id: plant.id } }).catch(() => undefined);
};

const selectBuilding = (value: string) => {
  filters.building = value;
  showBuildingSheet.value = false;
};

const selectFloor = (value: string) => {
  filters.floor = value;
  showFloorSheet.value = false;
};

// Permettra d’ouvrir un panneau de filtres avancés (placeholder pour l’instant).
const triggerFilters = () => {
  console.info('Filters panel coming soon');
};
</script>

<template>
  <section class="relative space-y-2">
    <header class="absolute bottom-0 right-0 w-fit flex items-center justify-between rounded-xl mx-2 -mb-2 p-0.5 bg-gray-50 z-40 ">
        <button
          type="button"
          class="flex-1 rounded-xl px-4 py-2 transition duration-150"
          :class="viewMode === 'list' ? 'bg-primary-green text-tertiary-green' : 'bg-transparent'"
          @click="viewMode = 'list'"
        >
          <Icon icon="ph:list-bullets" class="inline h-5 w-5 align-text-bottom" />
        </button>
        <button
          type="button"
          class="flex-1 rounded-xl px-4 py-2 transition duration-150"
          :class="viewMode === 'plan' ? 'bg-primary-green text-tertiary-green' : 'bg-transparent'"
          @click="viewMode = 'plan'"
        >
          <Icon icon="ph:map-trifold" class="inline h-5 w-5 align-text-bottom" />
        </button>
    </header>
    <div class="flex flex-row gap-3 rounded-3xl bg-white sm:flex-row sm:items-center sm:gap-4">
      <div class="relative flex-1">
        <Icon icon="ph:magnifying-glass" class="absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2" />
        <input
          v-model="filters.search"
          type="search"
          placeholder="Search"
          class="w-full rounded-full bg-gray-100 py-3 pl-12 pr-4 text-sm  placeholder:text-gray-600 focus:outline-none focus:ring-2 focus:ring-[#c8d7a4]"
        />
      </div>
      <button
        type="button"
        class="flex h-11 w-11 items-center justify-center rounded-full border  bg-[#f9f5f1] "
        @click="triggerFilters"
      >
        <Icon icon="ph:sliders-horizontal" class="h-5 w-5" />
      </button>
    </div>
    <!-- Filtres et recherche -->
    <div class="rounded-3xl ">
      <div class="flex flex-row gap-4 sm:flex-row">
        <div class="flex flex-1 items-center gap-2">
          <button
            type="button"
            class="w-full rounded-full border border-primary-pink bg-secondary-pink px-4 py-2.5 text-sm font-semibold flex items-center justify-between"
            @click="showBuildingSheet = true"
          >
            <span>{{ filters.building }}</span>
            <Icon icon="ph:caret-down" class="h-4 w-4" />
          </button>
        </div>

        <div class="flex items-center gap-2">
          <button
            type="button"
            class="w-fit rounded-full border border-primary-green bg-secondary-green px-4 py-2.5 text-sm font-semibold flex items-center gap-2"
            @click="showFloorSheet = true"
          >
            <span>{{ filters.floor }}</span>
            <Icon icon="ph:caret-down" class="h-4 w-4" />
          </button>
        </div>
      </div>
    </div>
    <!-- divider -->
    <hr class="border-gray-200 " />


    <div v-if="viewMode === 'list'" class="relative bg-gray-100 grid gap-3 rounded-3xl  p-3">
      <div
        v-if="!filteredPlants.length"
        class="flex min-h-[240px] flex-col items-center justify-center rounded-2xl bg-white/70 text-center text-sm "
      >
        Aucune plante ne correspond à vos filtres actuels.
      </div>
      <ul v-else class="grid grid-cols-2 gap-3 md:grid-cols-3 overflow-visible ">
        <li
          v-for="plant in filteredPlants"
          :key="plant.id"
          class="relative h-[135px] rounded-2xl  bg-white transition hover:shadow-md"
        >
          <button type="button" class="relative w-full h-full flex flex-col text-left" @click="openPlantDetail(plant)">
            <!-- Image qui dépasse de la card -->
            <img
              :src="plant.image"
              :alt="plant.name"
              class="absolute -top-4  left-1/2 -translate-x-1/2 h-36 w-auto object-contain z-20  hover:-top-5  transition-all duration-300 "
            />
            <!-- Zone info bas de la card -->
            <div class="absolute inset-x-0 top-0 my-2 rounded-2xl flex items-center justify-between px-1.5 z-30">
              <span
                class="rounded-full px-2 py-1 text-xs font-semibold truncate"
                :class="plant.status === 'ok'
                  ? 'bg-[#dbe9c4] text-[#4f6631]'
                  : 'bg-[#f5d7cc] text-[#a35b3d]'"
              >
                {{ plant.name }}
                <Icon
                  v-if="plant.status === 'alert'"
                  icon="ph:warning-duotone"
                  class="ml-0.5 inline h-3 w-3 align-text-bottom"
                />
              </span>
            </div>
            <div class="absolute right-0 bottom-0  gap-2 z-20 p-2">
              <!-- Jauge d'eau -->
              <WaterGauge :value="plant.waterLevel" :size="32" :stroke-width="8" />
            </div>
          </button>
        </li>
      </ul>
      </div>
    

    <div v-else class="flex min-h-[240px] items-center justify-center rounded-3xl bg-white text-sm text-[#8d7e72]">
      La vue "Plan" arrive très bientôt.
    </div>

    <!-- Bottom sheet Building -->
    <div
      v-if="showBuildingSheet"
      class="fixed inset-0 z-40 flex items-end bg-black/40 backdrop-blur-sm"
      @click.self="showBuildingSheet = false"
    >
      <div class="w-full rounded-t-3xl bg-white p-4 max-h-[70vh] overflow-y-auto shadow-2xl">
        <div class="mb-3 flex items-center justify-between">
          <h3 class="text-base font-semibold text-[#2F2C36]">Choisir un bâtiment</h3>
          <button type="button" class="p-2" @click="showBuildingSheet = false">
            <Icon icon="ph:x" class="h-5 w-5" />
          </button>
        </div>
        <ul class="space-y-2">
          <li v-for="building in buildings" :key="building">
            <button
              type="button"
              class="flex w-full items-center justify-between rounded-2xl border px-4 py-3 text-left transition hover:border-primary-pink"
              :class="filters.building === building ? 'border-primary-pink bg-secondary-pink text-tertiary-green' : 'border-gray-200 bg-white text-[#2F2C36]'"
              @click="selectBuilding(building)"
            >
              <span class="text-sm font-semibold">{{ building }}</span>
              <Icon v-if="filters.building === building" icon="ph:check-circle" class="h-5 w-5" />
            </button>
          </li>
        </ul>
      </div>
    </div>

    <!-- Bottom sheet Floor -->
    <div
      v-if="showFloorSheet"
      class="fixed inset-0 z-40 flex items-end bg-black/40 backdrop-blur-sm"
      @click.self="showFloorSheet = false"
    >
      <div class="w-full rounded-t-3xl bg-white p-4 max-h-[70vh] overflow-y-auto shadow-2xl">
        <div class="mb-3 flex items-center justify-between">
          <h3 class="text-base font-semibold text-[#2F2C36]">Choisir un étage</h3>
          <button type="button" class="p-2" @click="showFloorSheet = false">
            <Icon icon="ph:x" class="h-5 w-5" />
          </button>
        </div>
        <ul class="space-y-2">
          <li v-for="floor in floors" :key="floor">
            <button
              type="button"
              class="flex w-full items-center justify-between rounded-2xl border px-4 py-3 text-left transition hover:border-primary-green"
              :class="filters.floor === floor ? 'border-primary-green bg-secondary-green text-tertiary-green' : 'border-gray-200 bg-white text-[#2F2C36]'"
              @click="selectFloor(floor)"
            >
              <span class="text-sm font-semibold">{{ floor }}</span>
              <Icon v-if="filters.floor === floor" icon="ph:check-circle" class="h-5 w-5" />
            </button>
          </li>
        </ul>
      </div>
    </div>
  </section>
</template>
