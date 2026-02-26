<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import { Icon } from '@iconify/vue';
import BottomSheet from '@/components/BottomSheet.vue';

interface Plant {
  id: number;
  name: string;
  building: string;
  floor: string;
  image: string;
  // Champs pour la recherche
  serialNumber?: string;
}

const router = useRouter();
const videoRef = ref<HTMLVideoElement | null>(null);
const stream = ref<MediaStream | null>(null);
const error = ref<string | null>(null);
let isMountedState = false;

const showManualSearch = ref(false);
const searchQuery = ref('');

// Données statiques pour la recherche
const plants = ref<Plant[]>([
  { id: 1, name: 'Monstera', building: 'Bâtiment A', floor: 'Étage 3', image: '/images/monstera.png', serialNumber: 'PL-001' },
  { id: 2, name: 'Sansevieria', building: 'Bâtiment A', floor: 'Étage 3', image: '/images/sansevieria.png', serialNumber: 'PL-002' },
  { id: 3, name: 'Ficus Lataara', building: 'Bâtiment A', floor: 'Étage 3', image: '/images/ficus-latara.png', serialNumber: 'PL-003' },
  { id: 4, name: 'Calathea', building: 'Bâtiment A', floor: 'Étage 3', image: '/images/calathea-ornata.webp', serialNumber: 'PL-004' },
  { id: 5, name: 'Pachira Aquatica', building: 'Bâtiment A', floor: 'Étage 3', image: '/images/pachira-aquatica.png', serialNumber: 'PL-005' },
]);

const filteredPlants = computed(() => {
  const q = searchQuery.value.trim().toLowerCase();
  if (!q) return plants.value;
  return plants.value.filter(p => 
    p.name.toLowerCase().includes(q) || 
    (p.serialNumber && p.serialNumber.toLowerCase().includes(q))
  );
});

const startCamera = async () => {
  try {
    error.value = null;
    const mediaStream = await navigator.mediaDevices.getUserMedia({
      video: { facingMode: 'environment' }
    });

    if (!isMountedState) {
      mediaStream.getTracks().forEach(track => track.stop());
      return;
    }

    stream.value = mediaStream;
    if (videoRef.value) {
      videoRef.value.srcObject = stream.value;
    }
  } catch (err) {
    console.error('Erreur caméra:', err);
    error.value = "Impossible d'accéder à la caméra. Vérifiez vos permissions.";
  }
};

const stopCamera = () => {
  if (stream.value) {
    stream.value.getTracks().forEach(track => track.stop());
    stream.value = null;
  }
  if (videoRef.value) {
    videoRef.value.srcObject = null;
  }
};

const goToReport = (plantId: number) => {
  router.push({ name: 'tech-report-problem', params: { id: plantId } });
};

onMounted(() => {
  isMountedState = true;
  startCamera();
});

onUnmounted(() => {
  isMountedState = false;
  stopCamera();
});
</script>

<template>
  <div class="fixed inset-0 bg-black z-0">
    <!-- Camera View -->
    <video
      ref="videoRef"
      autoplay
      playsinline
      class="absolute inset-0 h-full w-full object-cover"
    ></video>
    
    <!-- Fallback / Error -->
    <div v-if="error" class="absolute inset-0 flex items-center justify-center bg-gray-900 text-white p-6 text-center">
      <p>{{ error }}</p>
    </div>

    <!-- Interface Overlay -->
    <div class="absolute inset-0 flex flex-col items-center justify-center p-6 z-10 pointer-events-none">
      
      <!-- Instructions -->
      <div class="absolute top-24 text-center space-y-2 pointer-events-auto"> 
        <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-black/50 backdrop-blur-md border border-white/20 text-white text-sm font-medium">
          <Icon icon="ph:scan-duotone" class="w-5 h-5 text-primary-green" />
          <span>Scanner le QR Code</span>
        </div>
      </div>

      <!-- Scanner Frame -->
      <div class="relative w-64 h-64 sm:w-80 sm:h-80 border-2 border-white rounded-[2rem] shadow-[0_0_0_100vmax_rgba(0,0,0,0.5)]">
        <!-- Coins décoratifs (optionnel) -->
        <div class="absolute -top-[2px] -left-[2px] w-8 h-8 border-t-4 border-l-4 border-primary-green rounded-tl-[1.8rem]"></div>
        <div class="absolute -top-[2px] -right-[2px] w-8 h-8 border-t-4 border-r-4 border-primary-green rounded-tr-[1.8rem]"></div>
        <div class="absolute -bottom-[2px] -left-[2px] w-8 h-8 border-b-4 border-l-4 border-primary-green rounded-bl-[1.8rem]"></div>
        <div class="absolute -bottom-[2px] -right-[2px] w-8 h-8 border-b-4 border-r-4 border-primary-green rounded-br-[1.8rem]"></div>
        
        <!-- Simulate scanning animation -->
        <div class="absolute inset-x-4 top-0 h-0.5 bg-primary-green/80 shadow-[0_0_15px_rgba(72,187,120,0.8)] animate-scan"></div>
      </div>

      <p class="mt-8 text-white/80 text-sm font-medium text-center max-w-[200px]">
        Placez le code QR de la plante dans le cadre
      </p>

    </div>

    <!-- Floating Action Button (Manual) -->
    <div class="absolute bottom-28 left-0 right-0 flex justify-center z-20 pointer-events-auto">
      <button
        @click="showManualSearch = true"
        class="flex items-center gap-2 bg-white text-[#2F2C36] px-6 py-3 rounded-full shadow-lg font-semibold transform active:scale-95 transition-all"
      >
        <Icon icon="ph:magnifying-glass-bold" class="w-5 h-5 text-gray-500" />
        Saisir manuellement
      </button>
    </div>

    <!-- Manual Search Bottom Sheet -->
    <BottomSheet
      v-model="showManualSearch"
      title="Rechercher une plante"
      show-close
    >
      <div class="space-y-4 min-h-[50vh] p-1">
        <!-- Search Input -->
        <div class="relative">
          <Icon icon="ph:magnifying-glass" class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Nom de la plante ou N° de série"
            class="w-full rounded-2xl bg-gray-100 py-4 pl-12 pr-4 text-[#2F2C36] placeholder:text-gray-500 focus:outline-none focus:ring-2 focus:ring-primary-green"
          />
        </div>

        <!-- Suggestions / Results -->
        <div class="space-y-2 mt-4">
          <p v-if="filteredPlants.length === 0 && searchQuery" class="text-sm text-gray-400 text-center py-4">
            Aucun résultat pour "{{ searchQuery }}"
          </p>

           <p v-if="filteredPlants.length === 0 && !searchQuery" class="text-sm text-gray-400 text-center py-4">
             Commencez à taper...
          </p>

          <button
            v-for="plant in filteredPlants"
            :key="plant.id"
            @click="goToReport(plant.id)"
            class="w-full flex items-center gap-4 p-3 rounded-2xl bg-white border border-gray-100 shadow-sm hover:border-primary-green transition-colors text-left"
          >
            <div 
              class="w-12 h-12 rounded-xl bg-gray-100 bg-cover bg-center shrink-0"
              :style="{ backgroundImage: `url(${plant.image})` }"
            ></div>
            <div>
              <h4 class="font-semibold text-[#2F2C36]">{{ plant.name }}</h4>
              <p class="text-xs text-gray-500">
                <span v-if="plant.serialNumber" class="font-mono bg-gray-100 px-1 rounded text-gray-600 mr-1">{{ plant.serialNumber }}</span>
                {{ plant.building }} - {{ plant.floor }}
              </p>
            </div>
            <Icon icon="ph:caret-right" class="ml-auto w-4 h-4 text-gray-400" />
          </button>
        </div>
      </div>
    </BottomSheet>
  </div>
</template>

<style scoped>
@keyframes scan {
  0% { top: 10%; opacity: 0; }
  10% { opacity: 1; }
  90% { opacity: 1; }
  100% { top: 90%; opacity: 0; }
}

.animate-scan {
  animation: scan 2s ease-in-out infinite;
}
</style>
