<script setup lang="ts">
import { Icon } from '@iconify/vue';
</script>

<template>
<div class="flex flex-col h-screen bg-[#FDFEFE] relative overflow-hidden">
    <!-- Background pattern (simple approximation) -->
    <div class="absolute inset-0 pointer-events-none opacity-[0.03]" 
         style="background-image: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0MCIgaGVpZ2h0PSI0MCIgdmlld0JveD0iMCAwIDQwIDQwIj48cGF0aCBkPSJNMjAgMjBMMCAwTTQwIDQwTDIwIDIwIiBzdHJva2U9IiMwMDAiIHN0cm9rZS13aWR0aD0iMSIvPjwvc3ZnPg==');">
    </div>

    <div class="flex flex-col items-center justify-center flex-1 px-8 z-10 -mt-20">
        <!-- Map Icon Placeholder -->
         <div class="w-24 h-24 mb-10 text-gray-400">
             <!-- Using an icon similar to the image -->
             <Icon icon="ph:map-trifold-fill" class="w-full h-full opacity-60" />
         </div>

        <h1 class="text-xl font-bold text-[#1E1E1E] mb-8 text-center">
            Où voulez-vous allez ?
        </h1>

        <div class="w-full space-y-4">
            <input 
                type="text" 
                placeholder="11 rue de la paix, Nantes" 
                class="w-full bg-white border border-gray-200 rounded-xl px-4 py-4 text-sm outline-none focus:border-[#D0F471] transition-colors shadow-sm"
            />

            <button class="w-full bg-[#D0F471] text-[#1E1E1E] font-bold py-4 rounded-xl flex items-center justify-center gap-2 active:scale-[0.98] transition-all">
                Rechercher
                <Icon icon="ph:car-fill" class="w-5 h-5"/>
            </button>
        </div>
    </div>
</div>
</template>
