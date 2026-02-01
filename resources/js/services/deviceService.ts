import type { PaginatedResponse } from '@/types/api';
import type { Device, DeviceFilters, DevicePayload } from '@/types/device';
import { ensureCsrfCookie, http } from './http';

const BASE_URL = '/app/v1/devices'; // Updated endpoint

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

  const { data } = await http.get<PaginatedResponse<Device>>(BASE_URL, { params });
  return data;
}

export async function getDevice(id: number | string): Promise<Device> {
    await ensureCsrfCookie();
    const { data } = await http.get<{ data: Device }>(`${BASE_URL}/${id}`);
    return data.data;
}

export async function getDeviceHistory(
    id: number | string, 
    sensor: 'soil_pct' | 'temp_c' | 'light_pct' | 'battery', 
    period: '24h' | '7d' | '30d' = '24h'
): Promise<{ device_id: string, sensor: string, period: string, data: { time: string, val: number }[] }> {
    await ensureCsrfCookie();
    const { data } = await http.get(`${BASE_URL}/${id}/history`, { params: { sensor, period } });
    return data;
}

export async function createDevice(payload: DevicePayload): Promise<Device> {
  await ensureCsrfCookie();
  // Note: Creation might still be on admin routes or we need to update to app routes if applicable
  // Keeping mapped to /devices (legacy admin) or change if needed. 
  // Assuming Admin still uses /api/devices (DeviceController resource)
  // But wait, the previous code was using '/devices'. 
  // If we split Admin vs App, creation usually is Admin.
  // Let's assume creation stays on /api/devices (DeviceController) and only Reading is on /api/app/v1
  const { data } = await http.post<{ data: Device }>('/devices', payload);
  return data.data;
}
