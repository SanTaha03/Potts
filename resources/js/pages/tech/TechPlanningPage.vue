<script setup lang="ts">
import { ref, onMounted, computed, watch } from 'vue';
import { useRouter } from 'vue-router';
import { useMissionStore } from '@/stores/missionStore';
import { Icon } from '@iconify/vue';
import { format, startOfMonth, endOfMonth, eachDayOfInterval, isSameDay, isToday, addMonths, subMonths, getDay } from 'date-fns';
import { fr } from 'date-fns/locale';

const router = useRouter();
const store = useMissionStore();

const currentDate = ref(new Date()); // Pour gérer le mois affiché
const selectedDate = ref(new Date()); // La date sélectionnée

const monthLabel = computed(() => {
    return format(currentDate.value, 'MMMM yyyy', { locale: fr });
});

// Génération du calendrier
const calendarDays = computed(() => {
    const start = startOfMonth(currentDate.value);
    const end = endOfMonth(currentDate.value);
    const days = eachDayOfInterval({ start, end });

    // Ajout de padding pour le début du mois (si le mois ne commence pas le lundi)
    const paddingDays = (getDay(start) + 6) % 7; 
    const paddedDays = Array(paddingDays).fill(null).concat(days);
    
    return paddedDays;
});

const missions = computed(() => store.missions);

const formattedSelectedDate = computed(() => {
    return format(selectedDate.value, 'd MMMM', { locale: fr });
});

// Actions
function prevMonth() {
    currentDate.value = subMonths(currentDate.value, 1);
}

function nextMonth() {
    currentDate.value = addMonths(currentDate.value, 1);
}

function selectDay(day: Date | null) {
    if (!day) return;
    selectedDate.value = day;
    store.fetchMissions(format(day, 'yyyy-MM-dd'));
}

function goToDetail(id: number) {
    router.push({ name: 'tech-mission', params: { id } });
}

function getBadgeStyle(type: string) {
    if (type === 'maintenance') return 'bg-[#EFFBD0] text-[#475F07]';
    if (type === 'replacement') return 'bg-[#EDE5FF] text-[#2E0099]';
    if (type === 'installation') return 'bg-[#FFE6F4] text-[#640239]';
    return 'bg-gray-100 text-gray-800';
}

function getBadgeLabel(type: string) {
    if (type === 'maintenance') return 'Entretiens régulier';
    if (type === 'replacement') return 'Remplacement';
    if (type === 'installation') return 'Installation';
    return type;
}

// Initial fetch
onMounted(() => {
    // Si le store a déjà une date, on l'utilise
    if (store.selectedDate) {
        selectedDate.value = new Date(store.selectedDate);
        currentDate.value = new Date(store.selectedDate);
    }
    store.fetchMissions(format(selectedDate.value, 'yyyy-MM-dd'));
});

// Days of week header
const weekDays = ['L', 'M', 'M', 'J', 'V', 'S', 'D'];

</script>

<template>
<div class="min-h-screen bg-[#FDFEFE] pb-6">
    <!-- Header -->
    <div class="px-6 pt-6 pb-2 flex items-center justify-between">
        <button 
          @click="router.back()"
          class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#F1F2F3] text-dark-green-2 active:scale-95 transition-transform"
        >
          <Icon icon="ph:arrow-left-bold" class="h-5 w-5" />
        </button>
        
        <div class="flex items-center gap-2">
            <button @click="prevMonth" class="p-2 text-gray-400 hover:text-gray-800"><Icon icon="ph:caret-left-bold" /></button>
            <h1 class="text-lg font-bold text-[#1E1E1E] capitalize">{{ monthLabel }}</h1>
            <button @click="nextMonth" class="p-2 text-gray-400 hover:text-gray-800"><Icon icon="ph:caret-right-bold" /></button>
        </div>
        
        <div class="w-10"></div> <!-- Spacer -->
    </div>

    <!-- Calendar -->
    <div class="px-4 mt-4">
        <div class="bg-white rounded-[24px] p-4 shadow-sm border border-gray-100">
            <!-- Days Header -->
            <div class="grid grid-cols-7 mb-2">
                <div v-for="day in weekDays" :key="day" class="text-center text-xs text-gray-400 font-medium py-2">
                    {{ day }}
                </div>
            </div>

            <!-- Calendar Grid -->
            <div class="grid grid-cols-7 gap-y-2">
                <div v-for="(day, index) in calendarDays" :key="index" class="aspect-square flex justify-center items-center relative">
                    <button 
                        v-if="day"
                        @click="selectDay(day)"
                        class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium transition-all relative z-10"
                        :class="[
                            isSameDay(day, selectedDate) 
                                ? 'bg-[#1E1E1E] text-white shadow-md' 
                                : isToday(day) 
                                    ? 'text-[#D0F471] font-bold border border-[#D0F471]' 
                                    : 'text-gray-600 hover:bg-gray-50'
                        ]"
                    >
                        {{ format(day, 'd') }}
                    </button>
                    <!-- Small dot for missions (simulated for now, could be passed as prop) -->
                     <!-- <div v-if="day && hasMission(day)" class="absolute bottom-1 w-1 h-1 rounded-full bg-[#D0F471]"></div> -->
                </div>
            </div>
        </div>
    </div>


    <!-- Selected Day Missions -->
    <div class="px-6 mt-8">
        <h2 class="text-xl font-bold text-[#1E1E1E] capitalize mb-4">{{ formattedSelectedDate }}</h2>

        <div v-if="store.isLoading" class="py-10 text-center opacity-50">
            Chargement...
        </div>

        <div v-else-if="missions.length === 0" class="py-10 flex flex-col items-center text-center text-gray-400">
            <Icon icon="ph:calendar-x-duotone" class="w-12 h-12 mb-2 opacity-30" />
            <p class="text-sm">Aucune mission prévue ce jour.</p>
        </div>

        <div v-else class="space-y-4">
            <div 
                v-for="mission in missions" 
                :key="mission.id"
                class="bg-white rounded-2xl p-4 border border-gray-100 flex items-start gap-4 active:scale-[0.99] transition-transform"
                @click="goToDetail(mission.id)"
            >
                <!-- Time Column -->
                <div class="flex flex-col items-center mt-1 w-12 shrink-0">
                    <span class="text-sm font-bold text-[#1E1E1E]">{{ format(new Date(mission.scheduled_for), 'HH:mm') }}</span>
                    <div class="h-full w-0.5 bg-gray-100 mt-2 rounded-full min-h-[30px]"></div>
                </div>

                <!-- Card Content -->
                <div class="flex-1 pb-2">
                    <div class="flex justify-between items-start mb-1">
                        <span 
                            class="inline-block px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide mb-1"
                            :class="getBadgeStyle(mission.type)"
                        >
                            {{ getBadgeLabel(mission.type) }}
                        </span>
                        <Icon icon="ph:caret-right-bold" class="text-gray-300 w-4 h-4" />
                    </div>
                    
                    <h3 class="font-bold text-[#1E1E1E] text-sm">{{ mission.org.name }}</h3>
                    <p class="text-xs text-gray-500 mt-0.5 line-clamp-1">{{ mission.address }}</p>

                    <div class="flex items-center gap-3 mt-3">
                         <div class="flex -space-x-2">
                            <!-- Fake avatars for team or just one -->
                            <div class="w-6 h-6 rounded-full border-2 border-white bg-gray-200"></div>
                         </div>
                         <span class="text-xs text-gray-400" v-if="mission.items?.length">{{ mission.items.length }} plantes</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</template>
