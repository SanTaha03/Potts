<script setup lang="ts">
import { ref, computed } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { Icon } from '@iconify/vue';

const route = useRoute();
const router = useRouter();

const photoPlaceholder = ref<File | null>(null);
const description = ref('');
const toastMessage = ref('');
const submitting = ref(false);

// Mock data based on the route ID
const plant = computed(() => ({
  id: Number(route.params.id),
  name: 'Calathea',
  subtitle: 'Ma plante de bureau',
  location: 'Bureau 13L',
  tag: '#12345',
  healthPoints: [28, 34, 60, 55, 72, 78, 80],
  image: '/images/monstera.png',
  waterLevel: 0.7,

}));

// SVG path for the health graph
const polyline = computed(() => {
  const points = plant.value.healthPoints;
  const max = Math.max(...points);
  return points
    .map((value, index) => {
      const x = (index / (points.length - 1)) * 100;
      const y = 100 - (value / max) * 70; // Keep some padding at top
      return `${x},${y}`;
    })
    .join(' ');
});

const handlePhotoSelect = (event: Event) => {
  const target = event.target as HTMLInputElement;
  photoPlaceholder.value = target.files?.[0] ?? null;
};

const submitReport = () => {
  if (submitting.value) return;
  submitting.value = true;
  
  // Simulate API call
  setTimeout(() => {
    submitting.value = false;
    toastMessage.value = 'Rapport envoyé avec succès !';
    setTimeout(() => {
        toastMessage.value = '';
        router.back();
    }, 1500);
    photoPlaceholder.value = null;
    description.value = '';
  }, 1000);
};
</script>

<template>
  <div class=" w-full bg-white font-popins">
    <!-- Header -->
    <header class="sticky top-0 z-30 bg-white/80 backdrop-blur-md px-5 py-4">
      <div class="flex items-center gap-4">
        <button 
          @click="router.back()"
          class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#F1F2F3] text-[#2F2C36] transition hover:bg-gray-200"
        >
          <Icon icon="ph:arrow-left-bold" class="h-5 w-5" />
        </button>
        <h1 class="text-xl font-bold text-[#2F2C36]">Signaler un problème</h1>
      </div>
    </header>

    <main class="px-5 space-y-6">
      <!-- Plant Summary Card (Mini Hero) -->
      <div class="relative overflow-hidden rounded-[32px] bg-[#F7F7F8] p-4">
        <div class="flex gap-4">
            <!-- Image -->
            <div class="h-24 w-24 shrink-0 overflow-hidden rounded-2xl bg-gray-200">
                <img 
                    :src="plant.image" 
                    :alt="plant.name" 
                    class="h-full w-full object-cover"
                />
            </div>
            
            <!-- Info -->
            <div class="flex flex-col justify-center gap-1">
                <h2 class="text-lg font-bold text-[#2F2C36]">{{ plant.name }}</h2>
                <div class="flex items-center gap-2">
                    <span class="rounded-[6px] bg-[#EDE5FF] px-[6px] py-[2px] text-xs font-bold text-[#2E0099]">
                        {{ plant.location }}
                    </span>
                    <span class="text-xs text-[#747F8B]">{{ plant.tag }}</span>
                </div>
            </div>
        </div>
      </div>

      

      <!-- Form -->
      <form @submit.prevent="submitReport" class="space-y-6">
        
        <!-- Photo Upload -->
        <div class="space-y-2">
            <label class="ml-1 text-sm font-bold text-[#2F2C36]">Photo du problème <span class="text-red-500">*</span></label>
            <label
                class="flex min-h-[160px] cursor-pointer flex-col items-center justify-center rounded-[24px] border-2 border-dashed border-[#E0E0E0] bg-[#FAFAFA] text-sm font-medium text-[#747F8B] transition hover:border-[#D0F471] hover:bg-[#F7F7F8]"
            >
                <input type="file" accept="image/*" class="hidden" @change="handlePhotoSelect" />
                
                <div v-if="photoPlaceholder" class="flex flex-col items-center gap-2">
                    <Icon icon="ph:check-circle-fill" class="h-8 w-8 text-[#D0F471]" />
                    <span class="max-w-[200px] truncate">{{ photoPlaceholder.name }}</span>
                    <span class="text-xs text-[#2072DF] underline">Changer</span>
                </div>
                
                <div v-else class="flex flex-col items-center gap-3">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-[#F1F2F3]">
                        <Icon icon="ph:camera-plus-bold" class="h-6 w-6 text-[#2F2C36]" />
                    </div>
                    <span>Ajouter une photo</span>
                </div>
            </label>
        </div>

        <!-- Description -->
        <div class="space-y-2">
            <label class="ml-1 text-sm font-bold text-[#2F2C36]">Description</label>
            <textarea
                v-model="description"
                rows="4"
                placeholder="Décrivez les symptômes observés..."
                class="w-full rounded-[24px] border-none bg-[#F7F7F8] p-5 text-sm font-medium text-[#2F2C36] placeholder:text-[#9DA3AE] focus:outline-none focus:ring-2 focus:ring-[#D0F471]"
            ></textarea>
        </div>

        <!-- Submit Button -->
        <button
            type="submit"
            :disabled="submitting"
            class="flex w-full items-center justify-center gap-2 rounded-2xl bg-primary-green py-4 text-sm font-bold text-dark-green  transition hover:bg-primary-green disabled:opacity-50 disabled:cursor-not-allowed"
        >
            <span v-if="submitting">Envoi en cours...</span>
            <span v-else class="flex items-center gap-2">
                Envoyer le rapport
                <div class="flex h-5 w-5 items-center justify-center rounded-full bg-dark-green">
                     <Icon icon="ph:arrow-right-bold" class="h-3 w-3 text-white" />
                </div>
            </span>
        </button>

      </form>
    </main>

    <!-- Toast Notification -->
    <transition name="fade">
      <div
        v-if="toastMessage"
        class="fixed bottom-10 left-1/2 z-50 flex -translate-x-1/2 items-center gap-3 rounded-full bg-[#2F2C36] px-6 py-3 text-sm font-bold text-white shadow-xl"
      >
        <Icon icon="ph:check-circle-fill" class="h-5 w-5 text-primary-green" />
        {{ toastMessage }}
      </div>
    </transition>
  </div>
</template>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease, transform 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
  transform: translate(-50%, 10px);
}
</style>
