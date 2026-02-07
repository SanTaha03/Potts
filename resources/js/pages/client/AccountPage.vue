
<script setup lang="ts">
import { ref } from 'vue';
import { Icon } from '@iconify/vue';

// Mock user data
const user = ref({
  firstName: 'John',
  lastName: 'Doe',
  email: 'john.doe@company.com',
  role: 'Employé',
  avatar: 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=facearea&facepad=2&w=256&h=256&q=80',
  notifications: true,
  theme: 'Light'
});

const isEditing = ref(false);

const toggleEdit = () => {
  isEditing.value = !isEditing.value;
};

const saveProfile = () => {
  isEditing.value = false;
  // TODO: Add API call
};
</script>

<template>
  <div class=" bg-white font-popins p-6 pb-24">
    <!-- Header -->
    <header class="flex items-center justify-between mb-8">
      <h1 class="text-2xl font-bold text-[#2F2C36]">Mon compte</h1>
      
    </header>

    <!-- Profile Card -->
    <div class="flex flex-col items-center mb-8">
      <div class="relative mb-4">
        <div class="w-24 h-24 rounded-full overflow-hidden border-4 border-[#F7FDE7]">
          <img :src="user.avatar" alt="Profile" class="w-full h-full object-cover">
        </div>
        <button class="absolute bottom-0 right-0 p-2 bg-primary-green rounded-full text-[#2F2C36] shadow-md hover:scale-105 transition">
          <Icon icon="ph:camera-bold" class="w-4 h-4" />
        </button>
      </div>
      <h2 class="text-xl font-bold text-[#2F2C36]">{{ user.firstName }} {{ user.lastName }}</h2>
      <span class="px-3 py-1 mt-2 text-xs font-bold text-[#2E0099] bg-[#EDE5FF] rounded-lg">
        {{ user.role }}
      </span>
    </div>

    <!-- Personal Info Section -->
    <section class="mb-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-bold text-[#2F2C36]">Informations personnelles</h3>
        <button 
          @click="isEditing ? saveProfile() : toggleEdit()"
          class="text-sm font-bold text-[#2F2C36] underline decoration-[#D0F471] decoration-2 underline-offset-4"
        >
          {{ isEditing ? 'Enregistrer' : 'Modifier' }}
        </button>
      </div>

      <div class="space-y-4 p-5 bg-[#F7F7F8] rounded-[32px]">
        <div class="grid gap-1">
          <label class="text-xs font-bold text-gray-400">Prénom</label>
          <input 
            v-model="user.firstName"
            type="text" 
            :disabled="!isEditing"
            class="w-full bg-transparent border-b border-gray-200 py-2 text-sm font-medium text-[#2F2C36] focus:outline-none focus:border-[#D0F471] disabled:border-transparent"
          >
        </div>
        
        <div class="grid gap-1">
          <label class="text-xs font-bold text-gray-400">Nom</label>
          <input 
            v-model="user.lastName"
            type="text" 
            :disabled="!isEditing"
            class="w-full bg-transparent border-b border-gray-200 py-2 text-sm font-medium text-[#2F2C36] focus:outline-none focus:border-[#D0F471] disabled:border-transparent"
          >
        </div>

        <div class="grid gap-1">
          <label class="text-xs font-bold text-gray-400">Email professionnel</label>
          <input 
            v-model="user.email"
            type="email" 
            disabled
            class="w-full bg-transparent border-b border-transparent py-2 text-sm font-medium text-gray-400 opacity-70"
          >
        </div>
      </div>
    </section>

    <!-- Preferences Section -->
    <section>
      <h3 class="text-lg font-bold text-[#2F2C36] mb-4">Préférences</h3>
      <div class="space-y-3">
        <!-- Notification Toggle -->
        <div class="flex items-center justify-between p-4 bg-white border border-gray-100 rounded-2xl shadow-sm">
          <div class="flex items-center gap-3">
            <div class="p-2 bg-[#F2EDFF] rounded-xl text-[#9466FF]">
              <Icon icon="ph:bell-simple-ringing-bold" class="w-5 h-5" />
            </div>
            <span class="text-sm font-bold text-[#2F2C36]">Notifications</span>
          </div>
          <button 
            @click="user.notifications = !user.notifications"
            class="w-12 h-7 rounded-full transition-colors relative"
            :class="user.notifications ? 'bg-primary-green' : 'bg-gray-200'"
          >
            <div 
              class="absolute top-1 left-1 w-5 h-5 bg-white rounded-full shadow transition-transform"
              :class="user.notifications ? 'translate-x-5' : 'translate-x-0'"
            ></div>
          </button>
        </div>

        <!-- Theme Selector (Mock) -->
        <button class="w-full flex items-center justify-between p-4 bg-white border border-gray-100 rounded-2xl shadow-sm active:scale-[0.98] transition">
          <div class="flex items-center gap-3">
             <div class="p-2 bg-[#FECEE9] rounded-xl text-[#FD9BD2]">
              <Icon icon="ph:moon-stars-bold" class="w-5 h-5 text-white" />
            </div>
            <span class="text-sm font-bold text-[#2F2C36]">Apparence</span>
          </div>
          <div class="flex items-center gap-2 text-gray-400">
            <span class="text-xs font-medium">{{ user.theme }}</span>
            <Icon icon="ph:caret-right-bold" class="w-4 h-4" />
          </div>
        </button>

         <!-- Logout -->
        <button class="w-full mt-6 flex items-center justify-center gap-2 p-4 rounded-2xl border-2 border-[#FEE2E2] text-red-500 font-bold hover:bg-red-50 transition">
             <Icon icon="ph:sign-out-bold" class="w-5 h-5" />
             Déconnexion
        </button>
      </div>
    </section>
  </div>
</template>
