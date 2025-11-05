import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import { createDevice as createDeviceRequest, listDevices } from '@/services/deviceService';
import { resolveHttpErrorMessage } from '@/services/http';
import type { PaginationLinks, PaginationMeta } from '@/types/api';
import type { Device, DeviceFilters, DevicePayload } from '@/types/device';

export const useDeviceStore = defineStore('devices', () => {
  const items = ref<Device[]>([]);
  const pagination = ref<PaginationMeta | null>(null);
  const links = ref<PaginationLinks | null>(null);
  const filters = ref<DeviceFilters>({ page: 1, perPage: 10, search: '' });
  const loading = ref(false);
  const error = ref<string | null>(null);
  const creating = ref(false);
  const createError = ref<string | null>(null);

  async function fetchDevices(partial: DeviceFilters = {}) {
    loading.value = true;
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
      loading.value = false;
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
    fetchDevices,
    createDevice,
  };
});
