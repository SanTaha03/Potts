import type { PaginatedResponse } from '@/types/api';
import type { Device, DeviceFilters, DevicePayload } from '@/types/device';
import { ensureCsrfCookie, http } from './http';

export async function listDevices(filters: DeviceFilters = {}): Promise<PaginatedResponse<Device>> {
  await ensureCsrfCookie();

  const params: Record<string, unknown> = {
    page: filters.page,
    per_page: filters.perPage,
    search: filters.search?.trim() || undefined,
  };

  Object.keys(params).forEach((key) => {
    if (params[key] === undefined || params[key] === '') {
      delete params[key];
    }
  });

  const { data } = await http.get<PaginatedResponse<Device>>('/devices', { params });
  return data;
}

export async function createDevice(payload: DevicePayload): Promise<Device> {
  await ensureCsrfCookie();
  const { data } = await http.post<{ data: Device }>('/devices', payload);
  return data.data;
}
