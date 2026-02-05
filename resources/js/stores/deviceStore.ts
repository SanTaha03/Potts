import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import { createDevice as createDeviceRequest, getDevice as getDeviceRequest, getDeviceHistory as getDeviceHistoryRequest, listDevices } from '@/services/deviceService';
import { resolveHttpErrorMessage } from '@/services/http';
import type { PaginationLinks, PaginationMeta } from '@/types/api';
import type { Device, DeviceFilters, DevicePayload } from '@/types/device';

export const useDeviceStore = defineStore('devices', () => {
  const items = ref<Device[]>([]);
  const currentDevice = ref<Device | null>(null); // For detail view
  
  const pagination = ref<PaginationMeta | null>(null);
  const links = ref<PaginationLinks | null>(null);
  const filters = ref<DeviceFilters>({ page: 1, perPage: 10, search: '' });
  
  const loading = ref(false);
  const error = ref<string | null>(null);
  const creating = ref(false);
  const createError = ref<string | null>(null);

  // Polling state
  const pollingDetailId = ref<number | null>(null);
  const pollingListId = ref<number | null>(null);

  async function fetchDevices(partial: DeviceFilters = {}, silent = false) {
    if (!silent) loading.value = true;
    error.value = null;
    filters.value = { ...filters.value, ...partial };

    try {
      const response = await listDevices(filters.value);
      items.value = response.data;
      pagination.value = response.meta;
      links.value = response.links;
    } catch (err) {
      error.value = resolveHttpErrorMessage(err, 'Impossible de charger les appareils.');
      throw err;
    } finally {
      if (!silent) loading.value = false;
    }
  }

  async function fetchDevice(id: number | string, silent = false) {
    if (!silent) loading.value = true;
    // Don't reset error on silent refresh to avoid hiding persistent errors if wanted, 
    // but usually we want to clear.
    if (!silent) error.value = null; 
    
    // Don't clear currentDevice on silent refresh to avoid flickering
    if (!silent) currentDevice.value = null; 
    
    try {
        const device = await getDeviceRequest(id);
        currentDevice.value = device;
        return device;
    } catch (err) {
        error.value = resolveHttpErrorMessage(err, 'Impossible de charger le device.');
        throw err;
    } finally {
        if (!silent) loading.value = false;
    }
  }

  async function fetchHistory(id: number | string, sensor: 'soil_pct' | 'temp_c' | 'light_pct', period: '24h' | '7d' = '24h') {
      return getDeviceHistoryRequest(id, sensor, period);
  }

  // Polling Actions
  function startPollingDevice(id: number | string, intervalMs = 10000) {
      stopPollingDevice();
      // First fetch immediate
      fetchDevice(id).catch(() => {});
      
      pollingDetailId.value = window.setInterval(() => {
          fetchDevice(id, true).catch(() => {});
      }, intervalMs);
  }

  function stopPollingDevice() {
      if (pollingDetailId.value) {
          clearInterval(pollingDetailId.value);
          pollingDetailId.value = null;
      }
  }

  function startPollingList(intervalMs = 20000) {
      stopPollingList();
      fetchDevices({}, false).catch(() => {});

      pollingListId.value = window.setInterval(() => {
          fetchDevices({}, true).catch(() => {});
      }, intervalMs);
  }

  function stopPollingList() {
      if (pollingListId.value) {
          clearInterval(pollingListId.value);
          pollingListId.value = null;
      }
  }

  async function createDevice(payload: DevicePayload) {
    creating.value = true;
    createError.value = null;

    try {
      await createDeviceRequest(payload);
      await fetchDevices({ page: 1 });
    } catch (err) {
      createError.value = resolveHttpErrorMessage(err, 'Impossible de créer le device.');
      throw err;
    } finally {
      creating.value = false;
    }
  }

  const hasDevices = computed(() => items.value.length > 0);
  const hasNextPage = computed(() => {
    if (!pagination.value) {
      return false;
    }
    return pagination.value.current_page < pagination.value.last_page;
  });

  return {
    items,
    pagination,
    links,
    filters,
    loading,
    error,
    creating,
    createError,
    hasDevices,
    hasNextPage,
    currentDevice, 
    fetchDevices,
    fetchDevice,
    fetchHistory,
    createDevice,
    startPollingDevice,
    stopPollingDevice,
    startPollingList,
    stopPollingList,
  };
});
