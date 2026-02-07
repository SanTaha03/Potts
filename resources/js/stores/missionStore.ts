import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import * as missionService from '@/services/missionService';
import { format } from 'date-fns';

export interface Mission {
    id: number;
    title: string;
    type: string;
    status: string;
    scheduled_for: string;
    address: string;
    org: {
        id: number;
        name: string;
        address: string;
        slug: string;
    };
    items?: MissionItem[];
    notes?: MissionNote[];
}

export interface MissionItem {
    id: number;
    action: string;
    status: string;
    device_id: number;
    device?: {
        id: number;
        name: string;
        location: any;
    };
    meta?: any;
}

export interface MissionNote {
    id: number;
    message: string;
    created_at: string;
    user: {
        id: number;
        name: string;
    };
}

export const useMissionStore = defineStore('missions', () => {
    // State
    const missions = ref<Mission[]>([]);
    const currentMission = ref<Mission | null>(null);
    const selectedDate = ref<string>(format(new Date(), 'yyyy-MM-dd'));
    const isLoading = ref(false);
    const error = ref<string | null>(null);
    const isSavingItem = ref<Record<number, boolean>>({});

    // Getters
    const itemsDonePct = computed(() => {
        if (!currentMission.value?.items?.length) return 0;
        const total = currentMission.value.items.length;
        const done = currentMission.value.items.filter(i => i.status === 'done').length;
        return Math.round((done / total) * 100);
    });

    const pendingItemsCount = computed(() => {
        if (!currentMission.value?.items) return 0;
        return currentMission.value.items.filter(i => i.status === 'todo').length;
    });

    // Actions
    async function fetchMissions(date?: string) {
        isLoading.value = true;
        error.value = null;
        if (date) selectedDate.value = date;
        
        try {
            const response = await missionService.getMissions(selectedDate.value);
            missions.value = response.data || [];
        } catch (e: any) {
            console.error(e);
            error.value = "Impossible de charger les missions.";
        } finally {
            isLoading.value = false;
        }
    }

    async function fetchMission(id: number) {
        isLoading.value = true;
        error.value = null;
        currentMission.value = null; // reset display
        
        try {
            const response = await missionService.getMission(id);
            currentMission.value = response.data;
        } catch (e: any) {
             console.error(e);
            error.value = "Impossible de charger le détail de la mission.";
        } finally {
            isLoading.value = false;
        }
    }

    async function toggleItem(itemId: number, done: boolean) {
        isSavingItem.value[itemId] = true;
        const status = done ? 'done' : 'todo';
        
        try {
            await missionService.updateMissionItem(itemId, { status });
            // Optimistic update locally
            if (currentMission.value && currentMission.value.items) {
                const item = currentMission.value.items.find(i => i.id === itemId);
                if (item) item.status = status;
            }
        } catch(e) {
            console.error(e);
            // Revert if needed, but for MVP minimal handling
        } finally {
            isSavingItem.value[itemId] = false;
        }
    }

    async function addNote(missionId: number, message: string) {
        try {
            const response = await missionService.createNote(missionId, message);
            if (currentMission.value && currentMission.value.notes) {
                currentMission.value.notes.unshift(response.data);
            }
        } catch(e) {
            console.error(e);
            throw e; 
        }
    }

    async function closeMission(missionId: number) {
         try {
            // Check pending items logic could be here
            const response = await missionService.updateMission(missionId, { status: 'done' });
            if (currentMission.value) {
                currentMission.value.status = 'done';
                currentMission.value.closed_at = response.data.closed_at;
            }
         } catch(e) {
             console.error(e);
             throw e;
         }
    }
    
    async function reportIncident(missionId: number, payload: any) {
        return await missionService.createIncident(missionId, payload);
    }

    return {
        missions,
        currentMission,
        selectedDate,
        isLoading,
        error,
        isSavingItem,
        itemsDonePct,
        pendingItemsCount,
        fetchMissions,
        fetchMission,
        toggleItem,
        addNote,
        closeMission,
        reportIncident
    };
});
