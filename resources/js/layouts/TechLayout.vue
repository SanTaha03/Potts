<script setup lang="ts">
// Common navigation bar for Tech
import { useRoute, useRouter } from 'vue-router';
import BottomNav from '@/components/navigation/BottomNav.vue';

const router = useRouter();
const route = useRoute();
</script>

<template>
  <div 
    class="relative flex min-h-screen flex-col text-slate-900"
    :class="{ 'pb-[80px]': route.meta.showBottomNav }"
  >
    <!-- Background Pattern with opacity -->
    <div 
      class="absolute inset-0 z-[-1] " 
      style="background: url('/images/pattern.webp') no-repeat center center / cover; opacity: 0.3;"
    >
</div>
    <div class="flex-1 overflow-auto ">
        <RouterView />
    </div>

    <!-- Shared Bottom Navigation -->
    <BottomNav v-if="route.meta.showBottomNav" />
  </div>
</template>
