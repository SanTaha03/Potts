export type DeviceStatus = "active" | "inactive" | "archived" | "offline"; // Added offline for UI

export interface DeviceLocation {
    site?: string | null;
    floor?: number | null;
    zone?: string | null;
}

export interface DeviceLastValues {
    soil_pct?: number;
    temp_c?: number;
    light_pct?: number;
    sent_at?: string;
    battery?: number;
}

export interface Device {
    id: number;
    device_id: string; // Serial/External ID
    name: string | null;

    // Legacy fields (optional or mapped)
    serial: string;
    alias: string | null;

    status: DeviceStatus;
    db_status?: string;
    is_online?: boolean;

    location: DeviceLocation | null;

    // Update Meta Type for Alerts
    meta: {
        battery_level?: number;
        type?: string;
        alerts?: string[];
        health_score?: number;
        profile?: string;
        [key: string]: unknown;
    } | null;

    last_values: DeviceLastValues | null;
    last_seen_at: string | null;

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
