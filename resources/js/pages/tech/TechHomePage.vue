<script setup lang="ts">
import { onMounted, computed, ref } from 'vue';
import { useRouter } from 'vue-router';
import { useMissionStore } from '@/stores/missionStore';
import { Icon } from '@iconify/vue';
import { format } from 'date-fns';
import { fr } from 'date-fns/locale';
import BottomSheet from '@/components/BottomSheet.vue';
import TechMaterialsSheet from '@/components/tech/TechMaterialsSheet.vue';

const router = useRouter();
const store = useMissionStore();

const showMaterials = ref(false);

onMounted(() => {
    store.fetchMissions(); // defaults to today
});

const formattedDate = computed(() => {
    const d = new Date(store.selectedDate);
    // ex: Mardi 5 Novembre
    return format(d, 'EEEE d MMMM', { locale: fr });
});

const missions = computed(() => store.missions);

function getBadgeStyle(type: string) {
    if (type === 'maintenance') return 'bg-[#EFFBD0] text-[#475F07]';
    if (type === 'replacement') return 'bg-[#EDE5FF] text-[#2E0099]';
    if (type === 'installation') return 'bg-[#FFE6F4] text-[#640239]';
    return 'bg-gray-100 text-gray-800';
}

function getBadgeLabel(type: string) {
    if (type === 'maintenance') return 'Entretiens régulier';
    if (type === 'replacement') return 'Remplacement de plantes';
    if (type === 'installation') return 'Installation de plantes';
    return type;
}

function goToDetail(id: number) {
    router.push({ name: 'tech-mission', params: { id } });
}
</script>

<template>
<div class="p-6">
    <!-- Header -->
    <h1 class="text-2xl font-bold text-[#1E1E1E] capitalize mb-6">{{ formattedDate }}</h1>

    <!-- List -->
    <div class="space-y-4">
        <div v-if="store.isLoading" class="text-center py-10 opacity-50">Chargement...</div>
        
        <div 
            v-for="mission in missions" 
            :key="mission.id"
            class="bg-white rounded-2xl p-4 border border-gray-100 flex items-center justify-between"
            @click="goToDetail(mission.id)"
        >
            <div class="flex-1">
                <span 
                    class="inline-block  px-3 py-1 rounded-md text-xs font-bold mb-2"
                    :class="getBadgeStyle(mission.type)"
                >
                    {{ getBadgeLabel(mission.type) }}
                </span>
                <h3 class="px-2 text-sm font-medium text-[#1E1E1E]">{{ mission.org.name }}</h3>
                <p class="px-2 text-sm text-gray-500 mt-1">{{ mission.address }}</p>
            </div>
            
            <button 
                class="w-10 h-10 rounded-full bg-gray-100 bg-opacity-10 flex items-center justify-center text-[#556987]"
            >
                <Icon icon="ph:arrow-right-bold" />
            </button>
        </div>
    </div>

    <!-- Actions Rapides -->
    <div class="mt-8 space-y-3">
        <button class="w-full bg-[#D0F471] text-[#1E1E1E] font-semibold py-4 rounded-xl flex items-center justify-center gap-2">
            Clôturer ma journée
            <Icon icon="ph:sun-horizon-fill" />
        </button>

         <button 
             @click="showMaterials = true"
             class="w-full bg-white border border-gray-200 text-[#1E1E1E] font-medium py-4 rounded-xl flex items-center justify-center gap-2 active:scale-95 transition-transform"
         >
            Matériel nécessaire
            <Icon icon="ph:briefcase-fill" />
        </button>
    </div>

    <!-- Materials Bottom Sheet -->
    <BottomSheet v-model="showMaterials">
        <TechMaterialsSheet 
            :missions="missions" 
            :date="store.selectedDate" 
            @close="showMaterials = false" 
        />
    </BottomSheet>
</div>
</template>
