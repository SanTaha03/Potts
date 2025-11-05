export type DeviceStatus = 'active' | 'inactive' | 'archived';

export interface DeviceLocation {
  site?: string | null;
  floor?: number | null;
  zone?: string | null;
}

export interface Device {
  id: number;
  serial: string;
  alias: string | null;
  status: DeviceStatus;
  location: DeviceLocation | null;
  meta: Record<string, unknown> | null;
  readings_count?: number;
  created_at: string | null;
  updated_at: string | null;
}

export interface DeviceFilters {
  page?: number;
  perPage?: number;
  search?: string;
}

export interface DevicePayload {
  serial: string;
  alias?: string | null;
  status?: DeviceStatus;
  location?: DeviceLocation | null;
  meta?: Record<string, unknown> | null;
  org_id?: number;
}
