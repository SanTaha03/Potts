<script setup lang="ts">
import { computed, watch, onMounted, onUnmounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { Icon } from '@iconify/vue';
import BottomSheet from '@/components/BottomSheet.vue';
import AnimatedNumber from '@/components/AnimatedNumber.vue';
import { useDeviceStore } from '@/stores/deviceStore';
import { useAuthStore } from '@/stores/authStore';
import { formatDistanceToNow } from 'date-fns';
import { fr } from 'date-fns/locale';

const route = useRoute();
const router = useRouter();
const deviceStore = useDeviceStore();
const authStore = useAuthStore();

const showAnomalySheet = ref(false);
const selectedLocation = ref('Étage 5');
const floors = ['Étage 1', 'Étage 2', 'Étage 3', 'Étage 4', 'Étage 5'];

const isTech = computed(() => authStore.currentUser?.role === 'tech');

// History polling timer
let historyInterval: number | null = null;

// Fetch real data on mount with polling
onMounted(async () => {
    const id = route.params.id as string;
    
    // Start polling device (live status)
    deviceStore.startPollingDevice(id, 10000); // 10s
    
    // Initial history load
    await loadHistory(id);
    
    // Poll history every 30s
    historyInterval = window.setInterval(() => {
        loadHistory(id);
    }, 30000);
});

onUnmounted(() => {
    deviceStore.stopPollingDevice();
    if (historyInterval) {
        clearInterval(historyInterval);
        historyInterval = null;
    }
});

async function loadHistory(id: string) {
    // Get last 7 readings (mocking daily for now via '7d' parameter limit if implemented or just slice)
    // Actually our API supports '7d'.
    try {
        const history = await deviceStore.fetchHistory(id, 'soil_pct', '7d');
        // Update Chart Data
        // Simple decimation or mapping: take last 7 points or average.
        // For MVP demo, just take last 7 points.
        if (history.data.length > 0) {
            weeklyHealth.value = history.data.slice(-7).map(d => Math.round(d.val));
        }
    } catch (e) {
        console.error("Failed to load history", e);
    }
}

const plant = computed(() => {
    const d = deviceStore.currentDevice;
    if (!d) return {
         id: 0, name: 'Loading...', subtitle: '', location: '', tag: '', 
         exposure: { label: '-', value: 0, status: '' }, humidity: { value: 0, status: '' }, temperature: { value: 0, status: '' },
         image: '', description: '', waterLevel: 0
    };

    return {
      id: d.id,
      name: d.name || d.device_id,
      subtitle: d.device_id,
      location: d.location ? `${d.location.site ?? ''} ${d.location.floor ? 'Etage '+d.location.floor : ''}` : 'Non localisé',
      tag: `#${d.device_id}`,
      exposure: {
        label: `${d.last_values?.light_pct ?? 0}%`,
        value: d.last_values?.light_pct ?? 0,
        status: (d.last_values?.light_pct ?? 0) > 50 ? 'Adéquate' : 'Faible',
      },

      humidity: {
        value: d.last_values?.soil_pct ?? 0,
        status: (d.last_values?.soil_pct ?? 0) > 30 ? 'Suffisante' : 'Faible',
      },
      // adapter la température pour n'afficher qu'un chiffre après la virgule et ajouter un statut
      temperature: {
        value: parseFloat((d.last_values?.temp_c ?? 0).toFixed(1)),
        status: (d.last_values?.temp_c ?? 0) > 18 ? 'Adéquate' : 'Froide',
      },
      image: '/images/monstera.png',
      description: ` La Monstera Deliciosa, également connue sous le nom de "plante fromage suisse" en raison de ses feuilles perforées caractéristiques, est une plante d'intérieur populaire originaire des forêts tropicales d'Amérique centrale. Elle est appréciée pour son feuillage luxuriant et sa capacité à purifier l'air ambiant.`,
      waterLevel: (d.last_values?.soil_pct ?? 0) / 100,
      isOffline: !d.is_online,
      lastUpdated: d.last_values?.sent_at 
        ? formatDistanceToNow(new Date(d.last_values.sent_at), { addSuffix: true, locale: fr })
        : 'Jamais'
    };
});

// Reactivity for Chart
const weeklyHealth = ref([25, 24, 32, 10, 51, 80, 23]); // Default mock
const statusColor = computed(() => {
    // Si offline -> gris
    if (plant.value.isOffline) return 'text-slate-400 bg-slate-100';
    
    // Si alerts -> rouge
    // Note: status vient du backend (ok, warning, alert)
    const backendStatus = deviceStore.currentDevice?.db_status || 'active'; // db_status contains real analysis
    
    // Mapping backend status to colors
    if (backendStatus === 'alert') return 'text-red-600 bg-red-100';
    if (backendStatus === 'warning') return 'text-amber-600 bg-amber-100';
    
    // Default OK
    return 'text-emerald-600 bg-emerald-100';
});

const alerts = computed(() => {
    return deviceStore.currentDevice?.meta?.alerts || [];
});
const sameFloorPlants = [
  { id: 12, name: 'Calathea', health: 89, water: 0.7, icon: 'ph:flower-lotus-bold', color: 'text-[#B1ED12]' },
  { id: 18, name: 'Strelitzia', health: 89, water: 0.35, icon: 'ph:flower-tulip-bold', color: 'text-[#FD9BD2]' },
  { id: 22, name: 'Monstera', health: 89, water: 0.6, icon: 'ph:leaf-bold', color: 'text-[#B899FF]' },
];

// Construit les points du graphique de santé en simple SVG.
const healthPolyline = computed(() => {
  const maxY = 100;
  return weeklyHealth.value
    .map((value, index) => {
      const x = (index / (Math.max(weeklyHealth.value.length - 1, 1))) * 100;
      const y = 100 - (value / maxY) * 100; // Full height usage
      return `${x} ${y}`;
    })
    .join(',');
});

// Redirige vers la page des plantes depuis le raccourci "Voir tout".
const goToPlants = () => {
    router.push({ name: "plants" }).catch(() => undefined);
};
</script>

<template>
  <div class="relative min-h-screen w-full bg-white font-popins pb-20">
    <!-- Header -->
    <header class="relative pb-2">
      <div class="flex items-start gap-4">
        <!-- Back Button -->
        <button 
          @click="router.back()"
          class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#F1F2F3] text-dark-green-2"
        >
          <Icon icon="ph:arrow-left-bold" class="h-5 w-5" />
        </button>

        <!-- Title & Info -->
        <div class="flex flex-col">
          <div class="flex items-center gap-3">
            <h1 class="text-[32px] font-popart leading-none text-dark-green-2">
              {{ plant.name }}
            </h1>
            <span class="rounded-md bg-[#EDE5FF] px-1.5 py-1 text-sm font-bold text-[#2E0099]">
              {{ plant.location }}
            </span>
          </div>
          <div class=" flex items-center gap-3">
            <span class="flex items-center justify-center rounded-lg bg-white py-2 text-sm text-dark-green-2">
              {{ plant.tag }}
            </span>
            <span v-if="plant.lastUpdated" class="flex items-center gap-1 text-xs text-gray-400">
              <Icon icon="ph:clock" class="text-xs" /> 
              {{ plant.lastUpdated }}
            </span>
          </div>
        </div>
      </div>
    </header>

    <main class=" space-y-4">
      <!-- Hero Section: Image + Floating Stats -->
      <div class="relative h-[380px] w-full">
        <!-- Plant Image Container (Left/Center) -->
        <div class="absolute -left-1/3 top-3 bottom-0 right-[100px] rounded-[32px]">
           <img 
            :src="plant.image" 
            alt="Monstera" 
            class="h-full w-full object-cover sm:object-contain"
          />
          
          <!-- Decorative Water Gauge on Image Edge -->
          <div class="absolute right-8 bottom-6  flex flex-col items-center">
             <div class="relative h-32 w-2 rounded-full bg-gray-100 backdrop-blur-sm">
                <div 
                  class="absolute bottom-0 w-full rounded-full bg-[#2072DF] transition-all duration-2000 ease-out" 
                  :style="{ height: `${plant.waterLevel * 100}%` }"
                ></div>
             </div>
             <div class="mt-2 flex h-8 w-8 items-center justify-center rounded-full bg-gray-100  backdrop-blur">
                <Icon icon="ph:drop-fill" class="h-4 w-4 text-[#2072DF]" />
             </div>
          </div>
        </div>

        <!-- Floating Stats Widgets (Right Column) -->
        <div class="absolute right-0 top-0 flex w-[120px] flex-col gap-3 z-10">
          
          <!-- Temp Card -->
          <div class="rotate-[2deg] rounded-3xl bg-[#F7F7F8] p-3  border border-white/50">
            <div class="flex items-center justify-between mb-2">
              <!-- condition sur la couleur de shadow  -->
              <div class="flex h-6 w-6 items-center justify-center rounded-full bg-white shadow-[0_0_0_2px_#B1ED12]">
                    <Icon icon="ph:thermometer-simple-bold" class="text-dark-green-2 text-xs" />
              </div>
              <span class="text-2xl font-bold text-dark-green-2">
                <AnimatedNumber :value="plant.temperature.value" :precision="1" suffix="°" />
              </span>
            </div>
            <div>
              <p class="text-[10px] text-[#454C54]">Température</p>
              <!-- un chiffre après la virgule -->
              <span class="inline-block rounded-md bg-[#EFFBD0] px-1.5 py-[2px] text-[10px] font-bold text-[#475F07]">
                {{ plant.temperature.status  }}
              </span>
            </div>
          </div>

          <!-- Humidity Card -->
          <div class="-rotate-[2deg] rounded-3xl bg-[#F7F7F8] p-3  border border-white/50">
            <div class="flex items-center justify-between mb-2">
              <div class="flex h-6 w-6 items-center justify-center rounded-full bg-white shadow-[0_0_0_2px_#FC69BB]">
                 <div class="h-4 w-4 bg-white rounded-full flex items-center justify-center text-[10px]">
                    <Icon icon="ph:drop-bold" class="text-dark-green-2" />
                 </div>
              </div>
              <span class="text-2xl font-bold text-dark-green-2">
                <AnimatedNumber :value="plant.humidity.value" suffix="%" />
              </span>
            </div>
            <div>
              <p class="text-[10px] text-[#454C54]">Humidité</p>
              <span class="inline-block rounded-md bg-[#FFE6F4] px-1.5 py-[2px] text-[10px] font-bold text-[#640239]">
                {{ plant.humidity.status }}
              </span>
            </div>
          </div>

          <!-- Exposure Card -->
          <div class="rotate-[2deg] rounded-3xl bg-[#F7F7F8] p-3  border border-white/50">
            <div class="flex items-center justify-between mb-2">
              <div class="flex h-6 w-6 items-center justify-center rounded-full bg-white shadow-[0_0_0_2px_#B1ED12]">
                    <Icon icon="ph:sun-dim-bold" class="text-dark-green-2 text-xs" />
              </div>
             
            </div>
             <p class="text-xl font-bold text-dark-green-2 mb-1 leading-tight">
                <AnimatedNumber :value="plant.exposure.value" suffix="%" />
             </p>
            <div>
              <p class="text-[10px] text-[#454C54]">Exposition</p>
              <span class="inline-block rounded-md bg-[#EFFBD0] px-1.5 py-[2px] text-[10px] font-bold text-[#475F07]">
                {{ plant.exposure.status }}
              </span>
            </div>
          </div>

        </div>
      </div>

      <!-- Status Alerts Section -->
      <div v-if="!plant.isOffline && alerts.length > 0" class=" mt-4 mb-4 p-3 bg-red-50 border border-red-200 rounded-2xl">
          <div class="flex items-center gap-2 mb-2">
            <Icon icon="ph:warning-circle-bold" class="text-red-600 text-lg" />
            <h3 class="font-bold text-red-800 text-sm">Attention  </h3>
          </div>
          <ul class="list-disc list-inside text-sm text-red-700 space-y-1">
              <li v-for="(alert, idx) in alerts" :key="idx">{{ alert }}</li>
          </ul>
      </div>

            <!-- Tech Action Button -->
      <div v-if="isTech" class="mb-4">
          <button 
             class="w-full py-3 bg-gray-900 text-white rounded-xl font-medium shadow-lg flex items-center justify-center gap-2 active:scale-95 transition-transform"
             @click="showAnomalySheet = true"
          >
             <Icon icon="ph:warning-bold" class="text-[#D0F471]" />
             Signaler une anomalie
          </button>
      </div>

      <!-- Health Graph -->
      <div class="rounded-3xl bg-[#F7F7F8] px-4 pt-4 pb-11">
        <div class="mb-4 flex items-center gap-2">
          <Icon icon="ph:heart-fill" class="text-base text-dark-green-2" />
          <h2 class="font-popins font-bold text-dark-green-2">Santé global</h2>
        </div>
        
        <div class="relative h-24 w-full">
           <!-- Graph Line -->
            <svg viewBox="0 0 100 100" class="h-full w-full overflow-visible" preserveAspectRatio="none">
               <defs>
                <linearGradient id="healthGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                  <stop offset="0%" stop-color="#8cc3ff" />
                  <stop offset="100%" stop-color="#5ea8f7" />
                </linearGradient>
              </defs>
              <polyline
                :points="healthPolyline"
                fill="none"
                stroke="url(#healthGradient)"
                stroke-width="3"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>
            <!-- X Axis -->
            <div class="absolute bottom-0 left-0 right-0 border-t border-[#747F8B]"></div>
            <div class="mt-2 flex justify-between text-[10px] font-bold uppercase text-[#454C54]">
            <div v-for="day in ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim']" :key="day" class="flex flex-col items-center gap-1">
                 <div class="h-[6px] w-[1px] bg-[#747F8B]"></div>
                 <span>{{ day }}</span>
            </div>
          </div>
        </div>
      </div>
      
      

      <!-- Description -->
      <div class="rounded-3xl bg-[#F7F7F8] p-4">
        <div class="mb-3 flex items-center gap-2">
          <Icon icon="ph:plant-fill" class=" text-base text-dark-green-2" /> <!-- Grass icon replacement -->
          <h2 class="font-popins text-base font-bold text-dark-green-2">Descriptif de la plante</h2>
        </div>
        <div class="text-xs leading-relaxed text-[#454C54] text-justify">
          <p v-for="(paragraph, index) in plant.description.split('\n\n')" :key="index" class="mb-3 last:mb-0">
            {{ paragraph }}
          </p>
        </div>
      </div>

       <!-- Same Floor Plants -->
       <div class="rounded-3xl bg-[#F7F7F8] p-4 ">
        <div class="flex justify-between items-center mb-4">
          <div class="flex items-center gap-2">
            <Icon icon="ph:plant-fill" class="text-base text-dark-green-2" /> 
            <h2 class=" font-popins font-bold text-base text-dark-green-2">Plante du même étage</h2>
          </div>
          <button
                    type="button"
                    class="font-popins rounded-lg bg-[#EEEEEE] px-4 py-2 text-xs text-black"
                    @click="goToPlants"
                >
                    Voir tout
                </button>
        </div>
        <div class="flex flex-col gap-3">
          <div 
            v-for="item in sameFloorPlants" 
            :key="item.id"
            class="flex items-center justify-between rounded-xl bg-white p-2 pl-3 "
          >
            <div class="flex items-center gap-3">
               <div class="flex h-8 w-8 items-center justify-center rounded-full bg-[#f6f6f6]">
                  <Icon :icon="item.icon" class="h-5 w-5" :class="item.color" />
               </div>
               <div>
                  <p class="font-semibold text-xs text-dark-green-2">{{ item.name }}</p>
                  <p class="text-[10px] text-[#747F8B] opacity-75">Santé : {{ item.health }}%</p>
               </div>
            </div>
            <div class="relative h-6 w-6 flex items-center justify-center rounded-full bg-[#F1F2F3]">
               <Icon icon="ph:drop-fill" class="h-3 w-3 text-[#2072DF] z-10" />
               <!-- Radial progress ring could go here -->
               <svg class="absolute inset-0 -rotate-90" viewBox="0 0 24 24">
                  <circle cx="12" cy="12" r="10" stroke="#F1F2F3" stroke-width="2" fill="none" />
                  <circle cx="12" cy="12" r="10" stroke="#2072DF" stroke-width="2" fill="none" stroke-dasharray="62.8" :stroke-dashoffset="62.8 * (1 - item.water)" />
               </svg>
            </div>
          </div>

        </div>
      </div>
    </main>

    <!-- Floating Bottom Action Bar-->
    <div class="fixed bottom-20 right-0  z-40 mx-5">
      <button 
        @click="showAnomalySheet = true"
        class="flex items-center justify-center gap-2 rounded-xl bg-[#D0F471] px-4 py-3 text-sm text-dark-green-2  hover:bg-[#c2e666] transition-colors"
      >
        Signaler une anomalie
        <div class="flex h-4 w-4 items-center justify-center rounded-full bg-[#2F2C36]">
           <Icon icon="ph:warning-fill" class="h-3 w-3 text-white" />
        </div>
      </button>
    </div> 

    <!-- Anomaly Bottom Sheet -->
    <BottomSheet v-model="showAnomalySheet" :showClose="false" title="Fiche anomalie">
      <div class="space-y-6 pb-24">
         <!-- Heading Info -->
         <div class="">
            <div class="flex items-center gap-2">
               <span class="text-base font-bold text-[#454C54]">{{ plant.name }}</span>
               <span class="rounded-[6px] bg-[#F1F2F3] px-1.5 py-1 text-xs font-bold text-[#454C54]">
                  {{ plant.location }}
               </span>
            </div>
            <p class="text-sm text-[#747F8B]">{{ plant.tag }}</p>
         </div>

         <!-- Forms -->
         <div class="space-y-4">
            <!-- Location Input -->
            <div class="space-y-2 ">
               <label class="text-base font-medium text-dark-green-2">Localisation de l'incident</label>
               <div class="relative">
                  <select 
                     v-model="selectedLocation"
                     class="w-full appearance-none rounded-lg border border-[#C7CCD1] bg-white px-3 py-2 text-dark-green-2 focus:outline-none focus:ring-1 focus:ring-gray-300"
                  >
                     <option v-for="floor in floors" :key="floor" :value="floor">
                        {{ floor }}
                     </option>
                  </select>
                   <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-dark-green-2">
                     <Icon icon="ph:caret-down" class="h-4 w-4" />
                   </div>
               </div>
            </div>

            <!-- Description Input -->
            <div class="space-y-2">
               <label class="text-base font-medium text-dark-green-2">Difficultés rencontrées</label>
               <textarea 
                  class="w-full h-32 rounded-lg border border-[#C7CCD1] px-3 py-2  resize-none focus:outline-none focus:ring-1 focus:ring-gray-300"
                  placeholder="Le niveau d’eau ne correspond pas à la quantité d’eau réel, potentiel problème de sonde."
               ></textarea>
            </div>
         </div>
      </div>
      
      <!-- Float Button Group in Sheet -->
      <div class="fixed bottom-8 left-1/2 flex -translate-x-1/2 items-center gap-2 rounded-2xl p-2  z-50">
        
        <button 
           class="flex items-center justify-center gap-2 border border-transparent rounded-lg bg-[#D0F471] px-4 py-3 text-sm text-dark-green-2 font-medium min-w-[100px]"
        >
          Envoyer
          <div class="flex h-4 w-4 items-center justify-center rounded-full">
             <Icon icon="ph:arrow-right-bold" class="h-3 w-3 text-dark-green" />
          </div>
        </button>
      </div>

    </BottomSheet>

  </div>
</template>

<style scoped>

/* Hide scrollbar for clean UI */
::-webkit-scrollbar {
  width: 0px;
  background: transparent;
}
</style>
