<script setup lang="ts">
import { Icon } from '@iconify/vue';
import { useAuthStore } from '@/stores/authStore';
import { useRouter } from 'vue-router';

const authStore = useAuthStore();
const router = useRouter();

async function handleLogout() {
    await authStore.logout();
    router.push({ name: 'login' });
}

function goToPlanning() {
    router.push({ name: 'tech-planning' });
}
</script>

<template>
<div class="min-h-screen bg-[#FDFEFE] flex flex-col items-center relative overflow-hidden pt-12 p-6">
    
     <!-- Background pattern (simple approximation) -->
    <div class="absolute inset-0 pointer-events-none opacity-[0.03]" 
         style="background-image: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0MCIgaGVpZ2h0PSI0MCIgdmlld0JveD0iMCAwIDQwIDQwIj48cGF0aCBkPSJNMjAgMjBMMCAwTTQwIDQwTDIwIDIwIiBzdHJva2U9IiMwMDAiIHN0cm9rZS13aWR0aD0iMSIvPjwvc3ZnPg==');">
    </div>

    <div class=" z-10 w-full max-w-sm flex flex-col items-center">
        
        <!-- Avatar Section -->
        <div class="relative mb-4">
             <div class="w-32 h-32 rounded-full border-4 border-white shadow-xl overflow-hidden bg-gray-200">
                  <img src="https://i.pravatar.cc/300?img=5" alt="Avatar" class="w-full h-full object-cover">
             </div>
             <!-- Green swoosh decoration behind or front -->
             <div class="absolute -bottom-2 -right-2 w-16 h-16 bg-[#D0F471] rounded-full -z-10 blur-xl opacity-80"></div>
        </div>

        <h1 class="text-3xl font-bold text-[#1E1E1E] text-center mb-1">
            {{ authStore.currentUser?.name || 'Lora DOE' }}
        </h1>
        <p class="text-gray-500 text-sm mb-4">Prestataire Pott's</p>

        <!-- ID Badge -->
        <div class="bg-[#F3E8FF] text-[#6B21A8] px-4 py-1.5 rounded-full font-bold text-sm tracking-wide mb-10">
            ID : {{ authStore.currentUser?.id ? '987 654 ' + authStore.currentUser.id : '987 654 321' }}
        </div>

        <!-- Planning Card -->
        <div class="w-full bg-white border border-gray-100 rounded-[32px] p-6 shadow-sm mb-8 relative overflow-hidden group active:scale-[0.98] transition-all" @click="goToPlanning">
            <div class="flex justify-between items-start mb-4 relative z-10">
                <span class="font-medium text-lg text-[#1E1E1E]">Voir mon planning</span>
                <button class="bg-[#F5F5F5] w-10 h-10 rounded-xl flex items-center justify-center">
                     <Icon icon="ph:arrow-right-bold" class="text-gray-600" />
                </button>
            </div>
            
            <!-- Calendar Illustration Placeholder -->
            <div class="flex justify-center mt-2 relative z-10">
               <Icon icon="ph:calendar-blank-duotone" class="w-32 h-32 text-gray-100" />
               <!-- Fake calendar lines -->
               <div class="absolute inset-0 flex items-center justify-center opacity-10">
                   <!-- Keep it simple, just the icon -->
               </div>
            </div>
        </div>

        <!-- Logout Button -->
        <button 
            @click="handleLogout"
            class="w-full bg-white border border-gray-200 text-[#1E1E1E] py-4 rounded-xl flex items-center justify-center gap-2 font-medium shadow-sm active:scale-[0.98] transition-all"
        >
            Déconnexion
            <Icon icon="ph:sign-out-bold" />
        </button>

    </div>
</div>
</template>
