import { http } from './http';

const BASE_URL = '/app/v1/tech';

export async function getMissions(date?: string) {
    const params: any = {};
    if (date) params.date = date;
    const { data } = await http.get(`${BASE_URL}/missions`, { params });
    // L'API Laravel renvoie { data: [...] } ou Resource collection
    return data;
}

export async function getMission(id: number) {
    const { data } = await http.get(`${BASE_URL}/missions/${id}`);
    return data;
}

export async function updateMission(id: number, payload: { status?: string; closed_at?: string }) {
    const { data } = await http.patch(`${BASE_URL}/missions/${id}`, payload);
    return data;
}

export async function updateMissionItem(id: number, payload: { status: string }) {
    const { data } = await http.patch(`${BASE_URL}/mission-items/${id}`, payload);
    return data;
}

export async function getNotes(missionId: number) {
    const { data } = await http.get(`${BASE_URL}/missions/${missionId}/notes`);
    return data;
}

export async function createNote(missionId: number, message: string) {
    const { data } = await http.post(`${BASE_URL}/missions/${missionId}/notes`, { message });
    return data;
}

export async function createIncident(missionId: number, payload: { severity: string; type: string; description: string; device_id?: number }) {
    const { data } = await http.post(`${BASE_URL}/missions/${missionId}/incidents`, payload);
    return data;
}
