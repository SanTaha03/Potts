<script setup lang="ts">
import { ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { Icon } from '@iconify/vue';

const route = useRoute();
const router = useRouter();

const photoPlaceholder = ref<File | null>(null);
const description = ref('');
const toastMessage = ref('');
const submitting = ref(false);

const plant = {
  id: Number(route.params.id),
  name: 'Calathea',
  subtitle: 'Ma plante de bureau',
  location: 'Bureau 13L',
  healthPoints: [28, 34, 60, 55, 72, 78, 80],
  image: 'https://images.unsplash.com/photo-1483799524323-66a1ad26c228?auto=format&fit=crop&w=680&q=80',
  waterLevel: 0.7,
};

const polyline = plant.healthPoints
  .map((value, index) => {
    const max = Math.max(...plant.healthPoints);
    const x = (index / (plant.healthPoints.length - 1)) * 100;
    const y = 100 - (value / max) * 70;
    return `${x},${y}`;
  })
  .join(' ');

// Simule l’upload d’image du problème.
const handlePhotoSelect = (event: Event) => {
  const target = event.target as HTMLInputElement;
  photoPlaceholder.value = target.files?.[0] ?? null;
};

// Soumet le rapport et affiche un toast de confirmation.
const submitReport = () => {
  if (submitting.value) return;
  submitting.value = true;
  setTimeout(() => {
    submitting.value = false;
    toastMessage.value = 'Rapport envoyé avec succès !';
    setTimeout(() => (toastMessage.value = ''), 2500);
    photoPlaceholder.value = null;
    description.value = '';
    router.push({ name: 'problems' }).catch(() => undefined);
  }, 1000);
};
</script>

<template>
  <section class="space-y-6 pb-20 md:pb-2">
    <div class="grid grid-cols-2 gap-6 rounded-3xl bg-[#f2eee9] p-5 shadow-[0_18px_45px_rgba(80,70,55,0.12)] lg:grid-cols-[minmax(0,1.2fr)_minmax(0,1fr)]">
      <div class="relative overflow-hidden rounded-3xl bg-white shadow-[0_12px_30px_rgba(80,70,55,0.15)]">
        <div
          class="h-72 w-full bg-cover bg-center"
          :style="{ backgroundImage: `url(${plant.image})` }"
        />
        <!-- Jauge d'eau -->
        <div class="absolute inset-y-6 left-4 flex w-5 gap-4 flex-col items-center justify-between rounded-full bg-[#eaf2fd] pt-3 pb-2 text-[#6da6f5] shadow">
          <Icon icon="ph:drop-fill" class="h-4 w-4" />
          <div class="h-full relative w-2 rounded-full bg-[#cbd7f4] ">
            <div
              class="absolute bottom-0 w-full rounded-full bg-[#7cb4ff]"
              :style="{ height: `${plant.waterLevel * 100}%` }"
            />
          </div>
        </div>
        <button
          type="button"
          class="absolute right-4 top-4 flex h-10 w-10 items-center justify-center rounded-full bg-white text-[#d28d7b] shadow"
        >
          <Icon icon="ph:heart-straight-bold" class="h-5 w-5" />
        </button>
      </div>

      <div class="space-y-4">
        <div>
          <h1 class="text-2xl font-semibold text-[#5d7a38]">{{ plant.name }}</h1>
          <p class="mt-1 text-xs font-medium text-[#8a7c71]">{{ plant.subtitle }}</p>
        </div>
        <div class="rounded-2xl bg-white px-4 py-3 text-xs font-semibold text-[#5f5148] shadow">
          <div class="flex items-center justify-between gap-3">
            <span class="flex items-center gap-2">
              <Icon icon="ph:map-pin-line-duotone" class="h-5 w-5 text-[#857564]" />
              {{ plant.location }}
            </span>
            <button type="button" class="text-xs text-[#9b7f6b]">
              <Icon icon="ph:pencil-simple-line" class="h-5 w-5" />
            </button>
          </div>
        </div>
        <div class="rounded-3xl bg-white p-5 shadow-[0_12px_28px_rgba(74,64,51,0.12)]">
          <header class="mb-4 flex items-center justify-between text-[#5f5148]">
            <span class="text-base font-semibold">Santé global</span>
            <span class="rounded-full bg-[#eef7d8] px-3 py-1 text-xs font-semibold text-[#6c7a3d]">Stable</span>
          </header>
          <div class="h-32 w-full">
            <svg viewBox="0 0 100 100" class="h-full w-full" preserveAspectRatio="none">
              <polyline :points="polyline" fill="none" stroke="#5e9df8" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" />
              <line v-for="i in 6" :key="i" x1="0" :x2="100" :y1="i * 12" :y2="i * 12" stroke="#f1ede9" stroke-width="0.5" />
            </svg>
          </div>
          <div class="mt-2 flex justify-between text-[11px] font-semibold uppercase tracking-wide text-[#a09286]">
            <span>Lun</span>
            <span>Mar</span>
            <span>Mer</span>
            <span>Jeu</span>
            <span>Ven</span>
            <span>Sam</span>
            <span>Dim</span>
          </div>
        </div>
      </div>
    </div>

    <form class="space-y-5 rounded-3xl bg-white p-6 shadow-[0_18px_45px_rgba(80,70,55,0.12)]" @submit.prevent="submitReport">
      <p class="text-xs text-[#9b8b82]">
        Les champs muni d’un <span class="font-semibold text-[#c0784f]">*</span> sont obligatoire
      </p>

      <div class="space-y-2">
        <label class="text-xs font-semibold text-[#5f5148]">Photo du problème*</label>
        <label
          class="flex min-h-[150px] cursor-pointer flex-col items-center justify-center rounded-3xl border border-dashed border-[#dab19f] bg-[#fdf7f3] text-xs font-semibold text-[#bf7152]"
        >
            <input type="file" accept="image/*" class="hidden" @change="handlePhotoSelect" />
            <Icon icon="gridicons:add-image" class="mb-2 h-8 w-8" />
          <span v-if="photoPlaceholder">{{ photoPlaceholder.name }}</span>
          <span v-else>Ajouter une photo</span>
        </label>
      </div>

      <div class="space-y-2">
        <label class="text-xs font-semibold text-[#5f5148]">Plus de précision sur le problème</label>
        <textarea
          v-model="description"
          rows="4"
          placeholder="Ma description..."
          class="w-full rounded-3xl border border-transparent bg-[#f7f0ea] px-4 py-3 text-xs text-[#5f5148] placeholder:text-[#b7a9a0] focus:border-[#c8d7a4] focus:outline-none"
        />
      </div>

      <div class="">
        <button
          type="submit"
          :disabled="submitting"
          class="flex items-center justify-center gap-2 mx-auto rounded-full bg-[#5c6631] px-6 py-3 text-xs font-semibold text-white shadow-lg transition hover:bg-[#4e5628] disabled:opacity-60"
        >
          <span>{{ submitting ? 'Envoi en cours...' : 'Envoyer le rapport' }}</span>
          <Icon icon="ph:caret-right" class="h-4 w-4" />
        </button>
      </div>
    </form>

    <transition name="fade">
      <div
        v-if="toastMessage"
        class="fixed bottom-24 left-1/2 z-50 -translate-x-1/2 rounded-full bg-[#344026] px-6 py-3 text-xs font-semibold text-white shadow-lg"
      >
        {{ toastMessage }}
      </div>
    </transition>
  </section>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
