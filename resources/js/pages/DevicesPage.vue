<script setup lang="ts">
import { computed, onMounted, reactive } from 'vue';
import DeviceList from '@/components/devices/DeviceList.vue';
import { useDeviceStore } from '@/stores/deviceStore';
import type { DeviceStatus } from '@/types/device';

interface CreateFormState {
  serial: string;
  alias: string;
  status: DeviceStatus;
  location: {
    site: string;
    floor: number | null;
    zone: string;
  };
}

const deviceStore = useDeviceStore();

const filters = reactive({
  search: '',
});

const createForm = reactive<CreateFormState>({
  serial: '',
  alias: '',
  status: 'active',
  location: {
    site: '',
    floor: null,
    zone: '',
  },
});

const statuses: DeviceStatus[] = ['active', 'inactive', 'archived'];

const loading = computed(() => deviceStore.loading);
const error = computed(() => deviceStore.error);
const creating = computed(() => deviceStore.creating);
const createError = computed(() => deviceStore.createError);
const devices = computed(() => deviceStore.items);
const hasDevices = computed(() => deviceStore.hasDevices);
const pagination = computed(() => deviceStore.pagination);

onMounted(() => {
  deviceStore.fetchDevices().catch(() => undefined);
});

async function loadDevices() {
  try {
    await deviceStore.fetchDevices({
      page: 1,
      search: filters.search.trim() || undefined,
    });
  } catch {
    // handled via store error state
  }
}

async function submitCreate() {
  if (!createForm.serial.trim()) {
    return;
  }

  try {
    await deviceStore.createDevice({
      serial: createForm.serial.trim(),
      alias: createForm.alias.trim() || null,
      status: createForm.status,
      location: normaliseLocation(createForm.location),
    });
    resetCreateForm();
  } catch {
    // handled via store createError state
  }
}

function normaliseLocation(location: CreateFormState['location']) {
  const site = location.site.trim();
  const zone = location.zone.trim();
  const floor = location.floor ?? null;

  if (!site && floor === null && !zone) {
    return null;
  }

  return {
    site: site || null,
    floor,
    zone: zone || null,
  };
}

function resetCreateForm() {
  createForm.serial = '';
  createForm.alias = '';
  createForm.status = 'active';
  createForm.location.site = '';
  createForm.location.floor = null;
  createForm.location.zone = '';
}

function clearSearch() {
  filters.search = '';
  loadDevices();
}

function goToPage(page: number) {
  if (!pagination.value) {
    return;
  }
  const target = Math.max(1, Math.min(page, pagination.value.last_page));
  if (target === pagination.value.current_page) {
    return;
  }

  deviceStore.fetchDevices({ page: target }).catch(() => undefined);
}
</script>

<template>
  <section class="space-y-10">
    <header class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
      <div>
        <h1 class="text-2xl font-semibold text-slate-900">Devices</h1>
        <p class="mt-1 max-w-2xl text-sm text-slate-600">
          Exemple complet pour récupérer, afficher et créer des appareils avec Laravel Sanctum, Axios, Pinia et Vue 3.
        </p>
      </div>

      <form class="flex w-full max-w-md items-center gap-3" @submit.prevent="loadDevices">
        <label class="sr-only" for="device-search">Recherche</label>
        <input
          id="device-search"
          v-model="filters.search"
          type="search"
          placeholder="Rechercher un device par alias ou n° de série…"
          class="w-full rounded-lg border border-slate-200 px-4 py-2 text-sm shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200"
        />
        <button
          type="submit"
          class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200"
        >
          Rechercher
        </button>
        <button
          v-if="filters.search"
          type="button"
          class="rounded-lg px-3 py-2 text-sm text-slate-500 transition hover:bg-slate-100"
          @click="clearSearch"
        >
          Effacer
        </button>
      </form>
    </header>

    <form class="grid gap-4 rounded-lg border border-slate-200 bg-white p-6 shadow-sm" @submit.prevent="submitCreate">
      <div class="flex items-center justify-between">
        <h2 class="text-lg font-medium text-slate-800">Ajouter un device</h2>
        <span v-if="createError" class="text-sm text-rose-600">{{ createError }}</span>
      </div>

      <div class="grid gap-4 sm:grid-cols-2">
        <div class="flex flex-col gap-1">
          <label for="device-serial" class="text-sm font-medium text-slate-700">Numéro de série *</label>
          <input
            id="device-serial"
            v-model.trim="createForm.serial"
            type="text"
            required
            placeholder="DEM00001"
            class="rounded-lg border border-slate-200 px-3 py-2 text-sm shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200"
          />
        </div>
        <div class="flex flex-col gap-1">
          <label for="device-alias" class="text-sm font-medium text-slate-700">Alias</label>
          <input
            id="device-alias"
            v-model.trim="createForm.alias"
            type="text"
            placeholder="Plante cuisine"
            class="rounded-lg border border-slate-200 px-3 py-2 text-sm shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200"
          />
        </div>
        <div class="flex flex-col gap-1">
          <label for="device-status" class="text-sm font-medium text-slate-700">Statut</label>
          <select
            id="device-status"
            v-model="createForm.status"
            class="rounded-lg border border-slate-200 px-3 py-2 text-sm shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200"
          >
            <option v-for="status in statuses" :key="status" :value="status">
              {{ status }}
            </option>
          </select>
        </div>
      </div>

      <div class="grid gap-4 sm:grid-cols-3">
        <div class="flex flex-col gap-1">
          <label for="device-site" class="text-sm font-medium text-slate-700">Site</label>
          <input
            id="device-site"
            v-model.trim="createForm.location.site"
            type="text"
            placeholder="Siège social"
            class="rounded-lg border border-slate-200 px-3 py-2 text-sm shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200"
          />
        </div>
        <div class="flex flex-col gap-1">
          <label for="device-floor" class="text-sm font-medium text-slate-700">Étage</label>
          <input
            id="device-floor"
            v-model.number="createForm.location.floor"
            type="number"
            min="0"
            placeholder="0"
            class="rounded-lg border border-slate-200 px-3 py-2 text-sm shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200"
          />
        </div>
        <div class="flex flex-col gap-1">
          <label for="device-zone" class="text-sm font-medium text-slate-700">Zone</label>
          <input
            id="device-zone"
            v-model.trim="createForm.location.zone"
            type="text"
            placeholder="Zone A"
            class="rounded-lg border border-slate-200 px-3 py-2 text-sm shadow-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-200"
          />
        </div>
      </div>

      <div class="flex items-center justify-end gap-3">
        <button
          type="submit"
          :disabled="creating"
          class="inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white transition hover:bg-emerald-500 disabled:cursor-not-allowed disabled:opacity-60"
        >
          <svg
            v-if="creating"
            class="-ms-1 me-2 h-4 w-4 animate-spin"
            fill="none"
            viewBox="0 0 24 24"
            xmlns="http://www.w3.org/2000/svg"
          >
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
            <path
              class="opacity-75"
              d="M4 12a8 8 0 018-8"
              stroke="currentColor"
              stroke-linecap="round"
              stroke-width="4"
            />
          </svg>
          Ajouter
        </button>
      </div>
    </form>

    <section class="space-y-4">
      <div class="flex items-center justify-between">
        <h2 class="text-lg font-medium text-slate-800">Liste des devices</h2>
        <p v-if="pagination" class="text-sm text-slate-500">
          Page {{ pagination.current_page }} / {{ pagination.last_page }} · {{ pagination.total }} éléments
        </p>
      </div>

      <div class="min-h-[160px]">
        <div v-if="loading" class="space-y-3">
          <div v-for="n in 3" :key="n" class="h-16 animate-pulse rounded-lg bg-slate-200/60"></div>
        </div>
        <div
          v-else-if="error"
          class="flex flex-col items-start justify-between gap-3 rounded-lg border border-rose-200 bg-rose-50 p-5 text-sm text-rose-700 sm:flex-row sm:items-center"
        >
          <span>{{ error }}</span>
          <button
            class="rounded-lg bg-rose-600 px-4 py-2 text-xs font-medium text-white transition hover:bg-rose-500"
            @click="loadDevices"
          >
            Réessayer
          </button>
        </div>
        <DeviceList v-else-if="hasDevices" :devices="devices" />
        <div
          v-else
          class="rounded-lg border border-dashed border-slate-300 bg-slate-50 p-10 text-center text-sm text-slate-500"
        >
          Aucun device pour le moment. Ajoutez un premier device pour peupler la liste.
        </div>
      </div>

      <div v-if="pagination && pagination.last_page > 1" class="flex items-center justify-end gap-2">
        <button
          class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm text-slate-600 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-50"
          :disabled="pagination.current_page === 1"
          @click="goToPage(pagination.current_page - 1)"
        >
          Précédent
        </button>
        <button
          class="rounded-lg border border-slate-200 px-3 py-1.5 text-sm text-slate-600 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-50"
          :disabled="pagination.current_page === pagination.last_page"
          @click="goToPage(pagination.current_page + 1)"
        >
          Suivant
        </button>
      </div>
    </section>
  </section>
</template>
