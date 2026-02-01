<script setup lang="ts">
import { computed, watch, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { Icon } from '@iconify/vue';
import BottomSheet from '@/components/BottomSheet.vue';
import { useDeviceStore } from '@/stores/deviceStore';

const route = useRoute();
const router = useRouter();
const deviceStore = useDeviceStore();

const showAnomalySheet = ref(false);
const selectedLocation = ref('Étage 5');
const floors = ['Étage 1', 'Étage 2', 'Étage 3', 'Étage 4', 'Étage 5'];

// Fetch real data on mount
onMounted(async () => {
    const id = route.params.id as string;
    await deviceStore.fetchDevice(id);
    await loadHistory(id);
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
         exposure: { label: '-', status: '' }, humidity: { value: 0, status: '' }, temperature: { value: 0, status: '' },
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
        status: (d.last_values?.light_pct ?? 0) > 50 ? 'Adéquate' : 'Faible',
      },
      humidity: {
        value: d.last_values?.soil_pct ?? 0,
        status: (d.last_values?.soil_pct ?? 0) > 30 ? 'Suffisante' : 'Critique',
      },
      temperature: {
        value: d.last_values?.temp_c ?? 0,
        status: (d.last_values?.temp_c ?? 0) > 18 ? 'Adéquate' : 'Froide',
      },
      image: '/images/monstera.png',
      description: `Données en temps réel du capteur.`,
      waterLevel: (d.last_values?.soil_pct ?? 0) / 100,
      isOffline: !d.is_online
    };
});

// Reactivity for Chart
const weeklyHealth = ref([25, 32, 42, 58, 51, 66, 78]); // Default mock

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
</script>

<template>
  <div class="relative min-h-screen w-full bg-white font-[Poppins] pb-20">
    <!-- Header -->
    <header class="relative pb-2">
      <div class="flex items-start gap-4">
        <!-- Back Button -->
        <button 
          @click="router.back()"
          class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#F1F2F3] text-[#2F2C36]"
        >
          <Icon icon="ph:arrow-left-bold" class="h-5 w-5" />
        </button>

        <!-- Title & Info -->
        <div class="flex flex-col">
          <div class="flex items-center gap-3">
            <h1 class="text-[32px] font-normal leading-none text-[#2F2C36]" style="font-family: 'Popart_Pixel', sans-serif">
              {{ plant.name }}
            </h1>
            <span class="rounded-[6px] bg-[#EDE5FF] px-[6px] py-[4px] text-sm font-bold text-[#2E0099]">
              {{ plant.location }}
            </span>
          </div>
          <div class=" flex">
            <span class="flex items-center justify-center rounded-lg bg-white py-2 text-sm text-[#2F2C36]">
              {{ plant.tag }}
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
                  class="absolute bottom-0 w-full rounded-full bg-[#2072DF]" 
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
                    <Icon icon="ph:thermometer-simple-bold" class="text-[#2F2C36] text-xs" />
              </div>
              <span class="text-2xl font-bold text-[#2F2C36]">{{ plant.temperature.value }}°</span>
            </div>
            <div>
              <p class="text-[10px] text-[#454C54]">Température</p>
              <span class="inline-block rounded-md bg-[#EFFBD0] px-[6px] py-[2px] text-[10px] font-bold text-[#475F07]">
                {{ plant.temperature.status }}
              </span>
            </div>
          </div>

          <!-- Humidity Card -->
          <div class="-rotate-[2deg] rounded-3xl bg-[#F7F7F8] p-3  border border-white/50">
            <div class="flex items-center justify-between mb-2">
              <div class="flex h-6 w-6 items-center justify-center rounded-full bg-white shadow-[0_0_0_2px_#FC69BB]">
                 <div class="h-4 w-4 bg-white rounded-full flex items-center justify-center text-[10px]">
                    <Icon icon="ph:drop-bold" class="text-[#2F2C36]" />
                 </div>
              </div>
              <span class="text-2xl font-bold text-[#2F2C36]">{{ plant.humidity.value }}%</span>
            </div>
            <div>
              <p class="text-[10px] text-[#454C54]">Humidité</p>
              <span class="inline-block rounded-md bg-[#FFE6F4] px-[6px] py-[2px] text-[10px] font-bold text-[#640239]">
                {{ plant.humidity.status }}
              </span>
            </div>
          </div>

          <!-- Exposure Card -->
          <div class="rotate-[2deg] rounded-3xl bg-[#F7F7F8] p-3  border border-white/50">
            <div class="flex items-center justify-between mb-2">
              <div class="flex h-6 w-6 items-center justify-center rounded-full bg-white shadow-[0_0_0_2px_#B1ED12]">
                    <Icon icon="ph:sun-dim-bold" class="text-[#2F2C36] text-xs" />
              </div>
             
            </div>
             <p class="text-xl font-bold text-[#2F2C36] mb-1 leading-tight">{{ plant.exposure.label }}</p>
            <div>
              <p class="text-[10px] text-[#454C54]">Exposition</p>
              <span class="inline-block rounded-md bg-[#EFFBD0] px-[6px] py-[2px] text-[10px] font-bold text-[#475F07]">
                {{ plant.exposure.status }}
              </span>
            </div>
          </div>

        </div>
      </div>

      <!-- Health Graph -->
      <div class="rounded-3xl bg-[#F7F7F8] px-4 pt-4 pb-11">
        <div class="mb-4 flex items-center gap-2">
          <Icon icon="ph:heart-fill" class="h-4 w-4 text-[#2F2C36]" />
          <h2 class="text-sm font-bold text-[#2F2C36]">Santé global</h2>
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
          <Icon icon="ph:plant-fill" class="h-4 w-4 text-[#2F2C36]" /> <!-- Grass icon replacement -->
          <h2 class="text-sm font-bold text-[#2F2C36]">Descriptif de la plante</h2>
        </div>
        <div class="text-xs leading-relaxed text-[#454C54] text-justify">
          <p v-for="(paragraph, index) in plant.description.split('\n\n')" :key="index" class="mb-3 last:mb-0">
            {{ paragraph }}
          </p>
        </div>
      </div>

       <!-- Same Floor Plants -->
       <div class="rounded-3xl bg-[#F7F7F8] p-4 pb-2">
        <div class="mb-4 flex items-center gap-2">
          <Icon icon="ph:plant-fill" class="h-4 w-4 text-[#2F2C36]" /> 
          <h2 class="text-sm font-bold text-[#2F2C36]">Plante du même étage</h2>
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
                  <p class="font-semibold text-xs text-[#2F2C36]">{{ item.name }}</p>
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

          <button class="mt-2 flex w-full items-center justify-center gap-2 rounded-lg py-2 text-sm text-[#2F2C36]">
            Voir tout
            <Icon icon="ph:caret-right-bold" class="h-4 w-4" />
          </button>
        </div>
      </div>
    </main>

    <!-- Floating Bottom Action Bar-->
    <div class="fixed bottom-20 right-0  z-40 mx-5">
      <button 
        @click="showAnomalySheet = true"
        class="flex items-center justify-center gap-2 rounded-xl bg-[#D0F471] px-4 py-3 text-sm text-[#2F2C36] shadow hover:bg-[#c2e666] transition-colors"
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
               <span class="rounded-[6px] bg-[#F1F2F3] px-[6px] py-[4px] text-xs font-bold text-[#454C54]">
                  {{ plant.location }}
               </span>
            </div>
            <p class="text-sm text-[#747F8B]">{{ plant.tag }}</p>
         </div>

         <!-- Forms -->
         <div class="space-y-4">
            <!-- Location Input -->
            <div class="space-y-2 ">
               <label class="text-base font-medium text-[#2F2C36]">Localisation de l'incident</label>
               <div class="relative">
                  <select 
                     v-model="selectedLocation"
                     class="w-full appearance-none rounded-lg border border-[#C7CCD1] bg-white px-3 py-2 text-[#2F2C36] focus:outline-none focus:ring-1 focus:ring-gray-300"
                  >
                     <option v-for="floor in floors" :key="floor" :value="floor">
                        {{ floor }}
                     </option>
                  </select>
                   <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-[#2F2C36]">
                     <Icon icon="ph:caret-down" class="h-4 w-4" />
                   </div>
               </div>
            </div>

            <!-- Description Input -->
            <div class="space-y-2">
               <label class="text-base font-medium text-[#2F2C36]">Difficultés rencontrées</label>
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
           class="flex items-center justify-center gap-2 border border-transparent rounded-lg bg-[#D0F471] px-4 py-3 text-sm text-[#2F2C36] font-medium min-w-[100px]"
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
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

/* Fallback for Popart Pixel if not loaded, though user might have it locally */
@font-face {
  font-family: 'Popart_Pixel';
  src: local('Popart_Pixel'), local('Popart Pixel'), url('/fonts/Popart_Pixel.woff2') format('woff2'); 
  /* Add actual path if available, else standard fallback covers it */
}

/* Hide scrollbar for clean UI */
::-webkit-scrollbar {
  width: 0px;
  background: transparent;
}
</style>
