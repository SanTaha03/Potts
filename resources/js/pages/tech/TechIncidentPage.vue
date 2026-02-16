<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useMissionStore } from '@/stores/missionStore';
import { Icon } from '@iconify/vue';

const route = useRoute();
const router = useRouter();
const store = useMissionStore();

const missionId = parseInt(route.params.id as string);

// Local state for form
// Duration options: generer toutes les 5 min jusqu'a 4h ?
// Ex: 00h05 .. 04h00
const durationOptions = computed(() => {
    const opts = [];
    for(let m = 5; m <= 480; m += 5) { // Jusqu'a 8h
        const h = Math.floor(m / 60);
        const mins = m % 60;
        const label = h > 0 
            ? `${h.toString().padStart(2, '0')} h ${mins.toString().padStart(2, '0')}`
            : `${mins} min`;
        opts.push({ label, value: m });
    }
    return opts;
});

const form = ref({
    duration_min: 30, // Default 30 min
    location: '',
    description: ''
});

const isLoading = ref(false);

onMounted(async () => {
    if(!store.currentMission || store.currentMission.id !== missionId) {
        await store.fetchMission(missionId);
    }
});

const mission = computed(() => store.currentMission);

// Actions
function goBack() {
    router.back();
}

async function submit() {
    if (!form.value.description || !form.value.location) return;
    
    isLoading.value = true;
    try {
        await store.reportIncident(missionId, {
            duration_min: form.value.duration_min,
            location: form.value.location,
            description: form.value.description,
            severity: 'high' // Default as per requirements? Or hidden? User said "severity" in payload but not in UI fields list. I'll put fixed or hidden.
        });
        // Toast success handled typically by a notification store or library, but here standard alert or implicit
        // User asked: "toast succès → retour mission"
        // I'll just redirect for now, maybe with a query param or alert? 
        // Assuming no Toast library is visible in context, I will just redirect.
        router.push(`/tech/missions/${missionId}`);
    } catch (e) {
        alert('Erreur lors de l\'envoi de l\'incident.');
    } finally {
        isLoading.value = false;
    }
}
</script>

<template>
<div class="min-h-screen bg-gray-50 flex flex-col">
    <!-- Header -->
    <header class="bg-white p-4 flex items-center gap-4 border-b border-gray-100">
        <button @click="goBack" class="p-2 rounded-full hover:bg-gray-100">
            <Icon icon="ph:arrow-left-bold" class="w-5 h-5" />
        </button>
        <h1 class="text-xl font-bold">Fiche incident</h1>
    </header>

    <div class="p-6 flex-1 space-y-6" v-if="mission">
        
        <!-- Context Card -->
        <div class="bg-white p-4 rounded-xl border border-gray-100">
            <div class="flex justify-between items-start mb-1">
                <h3 class="font-bold text-lg">Entretien régulier</h3>
                <span class="bg-gray-100 text-gray-700 text-xs font-bold px-2 py-1 rounded">
                    {{ mission.org?.name }}
                </span>
            </div>
            <p class="text-sm text-gray-500">{{ mission.address }}</p>
        </div>

        <!-- Form -->
        <div class="space-y-4">
            
            <!-- Temps d'intervention -->
            <div class="flex flex-col gap-1">
                <label class="font-bold text-sm text-dark-green-2">Temps d’intervention</label>
                <div class="relative">
                     <select 
                        v-model="form.duration_min"
                        class="w-full appearance-none bg-white border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary-green focus:ring-1 focus:ring-primary-green"
                     >
                        <option v-for="opt in durationOptions" :key="opt.value" :value="opt.value">
                            {{ opt.label }}
                        </option>
                     </select>
                     <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-gray-400">
                         <Icon icon="ph:caret-down-bold" />
                     </div>
                </div>
            </div>

            <!-- Localisation -->
            <div class="flex flex-col gap-1">
                <label class="font-bold text-sm text-dark-green-2">Localisation de l’incident</label>
                <input 
                    v-model="form.location"
                    type="text"
                    placeholder="Étage numéro 5"
                    class="w-full bg-white border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary-green focus:ring-1 focus:ring-primary-green"
                />
            </div>

            <!-- Description -->
            <div class="flex flex-col gap-1">
                <label class="font-bold text-sm text-dark-green-2">Difficultés rencontrées</label>
                <textarea 
                    v-model="form.description"
                    rows="8"
                    placeholder="Une fuite d’eau a eu lieu a l’étage 5 et la plante #12547 a été impacté, le pot n’est plus en fonctionnement."
                    class="w-full bg-white border border-gray-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-primary-green focus:ring-1 focus:ring-primary-green resize-none"
                ></textarea>
            </div>

        </div>
    </div>

    <!-- Actions Footer -->
    <div class="bg-white p-4 border-t border-gray-100 pb-8 flex gap-3">
        <button 
            @click="goBack"
            class="flex-1 py-3 rounded-2xl border-2 border-dark-green-2 font-bold text-dark-green-2"
        >
            Annuler
        </button>
        <button 
            @click="submit"
            :disabled="isLoading"
            class="flex-1 py-3 rounded-2xl bg-primary-green font-bold text-dark-green-2 flex items-center justify-center gap-2"
        >
            <span v-if="!isLoading">Envoyer</span>
            
            <span v-else>Envoi...</span>
        </button>
    </div>
</div>
</template>
