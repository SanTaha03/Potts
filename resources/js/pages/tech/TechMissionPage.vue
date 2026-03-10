<script setup lang="ts">
import { onMounted, computed, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useMissionStore } from "@/stores/missionStore";
import { Icon } from "@iconify/vue";

const route = useRoute();
const router = useRouter();
const store = useMissionStore();

const activeTab = ref<"detail" | "client" | "notes">("detail");
const noteInput = ref("");
const showConfirmModal = ref(false);
const showCopyToast = ref(false);

const missionId = parseInt(route.params.id as string);

onMounted(() => {
    store.fetchMission(missionId);
});

const m = computed(() => store.currentMission);
const isDone = computed(() => m.value?.status === "done");
const allItemsChecked = computed(() => {
    if (!m.value?.items || m.value.items.length === 0) return true;
    return m.value.items.every((i) => i.status === "done");
});

// Actions
function goBack() {
    router.back();
}

function handleTab(tab: "detail" | "client" | "notes") {
    activeTab.value = tab;
}

async function toggleItem(itemId: number, done: boolean) {
    if (isDone.value) return;
    await store.toggleItem(itemId, done);
}

async function sendNote() {
    if (!noteInput.value.trim()) return;
    await store.addNote(missionId, noteInput.value);
    noteInput.value = "";
}

function triggerClose() {
    if (!allItemsChecked.value) {
        alert(
            "Veuillez valider toutes les plantes avant de clôturer la mission.",
        );
        return;
    }
    showConfirmModal.value = true;
}

async function confirmClose() {
    showConfirmModal.value = false;
    await store.closeMission(missionId);
    // Stay or back? User said: "revenir à /tech (liste) ou rester en lecture seule"
    // Let's go back to list as per "toast 'Mission clôturée' -> revenir à /tech"
    router.push("/tech");
}

function goToIncident() {
    router.push({ name: "tech-incident", params: { id: missionId } });
}

// Helpers Display
function getBadgeStyle(type: string) {
    if (type === "maintenance") return "bg-[#EFFBD0] text-[#475F07]";
    if (type === "replacement") return "bg-[#EDE5FF] text-[#2E0099]";
    return "bg-gray-100 text-gray-800";
}

function goToPlantDetail(plantId: number) {
    router.push({
        name: "tech-plant-detail",
        params: { id: plantId },
        query: { mission: missionId },
    });
}

async function copyAddress() {
    if (m.value?.address) {
        try {
            await navigator.clipboard.writeText(m.value.address);
            showCopyToast.value = true;
            setTimeout(() => (showCopyToast.value = false), 2000);
        } catch (err) {
            console.error("Failed to copy: ", err);
        }
    }
}
</script>

<template>
    <!-- 100 vh - 80px bottom nav -->
    <div class="relative min-h-screen overflow-y-auto mb-24" v-if="m">
        <!-- Top Bar Mockup (Back button handled here actually) -->
        <div class="px-6 pt-6 pb-2">
            <div class="flex items-start gap-4">
                <!-- Back Button -->
                <button
                    @click="
                        router.name === 'tech-mission'
                            ? goBack()
                            : router.push('/tech')
                    "
                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#F1F2F3] text-dark-green-2"
                >
                    <Icon icon="ph:arrow-left-bold" class="h-5 w-5" />
                </button>
                <div>
                    <h1 class="text-2xl font-bold leading-tight">
                        {{ m.title }}
                    </h1>
                    <div class="flex justify-between items-end gap-2">
                        <div>
                            <div class="mt-2 flex items-center gap-2">
                                <span
                                    class="px-2 py-1 text-xs font-bold rounded"
                                    :class="getBadgeStyle(m.type)"
                                >
                                    {{ m.org.name }}
                                </span>
                            </div>
                            <p class="text-sm text-gray-500 mt-2">
                                {{ m.address }}
                            </p>
                        </div>

                        <button
                            @click="copyAddress"
                            class="mt-2 shrink-0 w-8 h-8 rounded-lg bg-gray-50 flex items-center justify-center active:bg-gray-200 transition-colors"
                        >
                            <Icon
                                :icon="
                                    showCopyToast
                                        ? 'ph:check-bold'
                                        : 'ph:copy-simple'
                                "
                                class="w-4 h-4 transition-all"
                                :class="
                                    showCopyToast
                                        ? 'text-green-600'
                                        : 'text-gray-400'
                                "
                            />
                        </button>
                    </div>
                </div>
            </div>

            <button
                class="w-full mt-4 bg-gray-100 text-black py-3 rounded-lg font-medium flex items-center justify-center gap-2"
            >
                Itinéraire Google Maps
                <Icon icon="ph:car-fill" />
            </button>
        </div>

        <!-- Tabs switcher -->
        <div class="mt-6 px-4">
            <div class="bg-gray-200 p-1 rounded-xl flex">
                <button
                    v-for="tab in ['detail', 'client', 'notes']"
                    :key="tab"
                    class="flex-1 py-2 text-sm font-medium rounded-lg capitalize transition-all"
                    :class="
                        activeTab === tab
                            ? 'bg-white shadow text-black'
                            : 'text-gray-500'
                    "
                    @click="handleTab(tab as any)"
                >
                    {{
                        tab === "notes"
                            ? `Notes (${m.notes?.length || 0})`
                            : tab
                    }}
                </button>
            </div>
        </div>

        <!-- CONTENT: DETAIL -->
        <div v-if="activeTab === 'detail'" class="mt-6 px-6">
            <p class="text-sm text-gray-600 mb-6">
                Description de la mission... Vérification de tous les pots,
                arrosage si nécessaire.
            </p>

            <h3 class="font-bold text-lg mb-4">
                Toutes les plantes ({{ m.items?.length }})
            </h3>

            <div class="space-y-4">
                <template v-for="item in m.items" :key="item.id">
                    <!-- REPLACEMENT CARD STYLE -->
                    <div
                        v-if="item.action === 'replace'"
                        class="bg-[#F8F9FA] rounded-xl p-4 border border-gray-100"
                    >
                        <!-- Location Header -->
                        <div
                            class="flex items-center gap-2 mb-3 text-xs text-gray-500 font-medium uppercase tracking-wide"
                        >
                            <div
                                class="w-3 h-3 rounded-full border-2 border-gray-300"
                            ></div>
                            <span class="flex items-center gap-1">
                                {{ item.device?.location?.site }}
                                <Icon
                                    icon="ph:caret-right-bold"
                                    class="text-gray-300"
                                />
                                {{ item.device?.location?.floor }}
                                <Icon
                                    icon="ph:caret-right-bold"
                                    class="text-gray-300"
                                />
                                {{ item.device?.location?.zone }}
                            </span>
                        </div>

                        <!-- OLD PLANT (Top) -->
                        <div
                            class="bg-white p-3 rounded-xl shadow-sm flex items-center relative overflow-hidden"
                        >
                            <img
                                :src="'/images/monstera.png'"
                                class="w-12 h-12 object-cover rounded-lg bg-gray-100 grayscale opacity-70"
                            />
                            <div class="ml-3 flex-1 z-10">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-gray-700">{{
                                        item.meta?.old_device_name ||
                                        "Ancienne plante"
                                    }}</span>
                                    <span
                                        class="text-[10px] bg-gray-100 text-gray-500 font-bold px-1.5 py-0.5 rounded"
                                    >
                                        #{{ item.meta?.old_device_code }}</span
                                    >
                                </div>
                                <span class="text-xs text-gray-400 font-medium"
                                    >Ancienne</span
                                >
                            </div>

                            <!-- Visual eyes icon plant id  -->
                            <div class="relative z-20">
                                <button
                                    @click.stop="
                                        goToPlantDetail(
                                            item.meta?.old_device_id,
                                        )
                                    "
                                    class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-500 hover:bg-gray-200 transition-colors"
                                >
                                    <Icon icon="ph:eye-bold" />
                                </button>
                            </div>
                        </div>

                        <!-- DIVIDER (Par) -->
                        <div
                            class="flex flex-col items-center justify-center py-2 relative"
                        >
                            <div
                                class="h-6 w-0.5 bg-gray-200 absolute top-0 bottom-0 z-0"
                            ></div>
                            <div
                                class="bg-white rounded z-10 px-1 py-0.5 text-[10px] text-gray-400 font-bold uppercase tracking-wider flex flex-col items-center"
                            >
                                <span>Par</span>
                                <Icon icon="ph:arrow-down-bold" class="mt-px" />
                            </div>
                        </div>

                        <!-- NEW PLANT (Bottom) -->
                        <div
                            class="bg-white p-3 rounded-xl shadow-sm flex items-center relative overflow-hidden active:scale-[0.99] transition-transform"
                            @click="goToPlantDetail(item.device_id)"
                        >
                            <img
                                :src="'/images/monstera.png'"
                                class="w-12 h-12 object-cover rounded-lg bg-gray-100"
                            />
                            <div class="ml-3 flex-1 z-10">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-[#1E1E1E]">{{
                                        item.device?.name || "Nouvelle plante"
                                    }}</span>
                                    <span
                                        class="text-[10px] bg-[#EFFBD0] text-[#475F07] font-bold px-1.5 py-0.5 rounded"
                                    >
                                        #{{ item.device?.device_id }}
                                    </span>
                                </div>
                                <span class="text-xs text-gray-400 font-medium"
                                    >Nouvelle</span
                                >
                            </div>

                            <!-- Action Button -->
                            <div class="relative z-20">
                                <button
                                    v-if="item.status === 'done'"
                                    @click.stop="toggleItem(item.id, false)"
                                    :disabled="isDone"
                                    :class="{ 'opacity-50': isDone }"
                                    class="w-8 h-8 rounded-full flex items-center justify-center bg-primary-green text-dark-green-2 transform transition-all"
                                >
                                    <Icon icon="ph:check-bold" />
                                </button>

                                <button
                                    v-else
                                    :disabled="isDone"
                                    :class="{ 'opacity-50': isDone }"
                                    class="w-8 h-8 rounded-full bg-primary-green flex items-center justify-center text-dark-green-2 hover:translate-x-1 transition-all"
                                >
                                    <Icon icon="ph:arrow-right-bold" />
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- STANDARD CARD STYLE (Maintenance / Install) -->
                    <div
                        v-else
                        class="bg-[#F1F2F3] rounded-xl p-3 flex items-center gap-3 active:scale-[0.98] transition-all"
                        @click="goToPlantDetail(item.device_id)"
                    >
                        <!-- Image -->
                        <img
                            :src="'/images/monstera.png'"
                            class="w-14 h-14 object-cover rounded-xl bg-gray-200"
                        />

                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span
                                    class="font-bold text-base text-[#1E1E1E]"
                                    >{{ item.device?.name || "Inconnue" }}</span
                                >
                                <span
                                    class="bg-[#DFE1E3] text-gray-600 text-[10px] font-bold px-1.5 py-0.5 rounded"
                                    >#{{ item.device?.device_id }}</span
                                >
                            </div>
                            <!-- Access location from device json -->
                            <div
                                class="text-[11px] font-medium text-[#4C5D70] flex flex-wrap gap-1 items-center"
                            >
                                <span>{{ item.device?.location?.site }}</span>
                                <Icon
                                    icon="ph:caret-right-bold"
                                    class="text-gray-400 text-[10px]"
                                />
                                <span>{{ item.device?.location?.floor }}</span>
                                <Icon
                                    icon="ph:caret-right-bold"
                                    class="text-gray-400 text-[10px]"
                                />
                                <span class="text-[#1E1E1E]">{{
                                    item.device?.location?.zone
                                }}</span>
                            </div>

                            <div
                                v-if="item.action === 'install'"
                                class="text-xs text-blue-600 font-semibold mt-1"
                            >
                                Nouvelle Installation
                            </div>
                        </div>

                        <!-- Checkbox / Button Action -->
                        <div class="relative z-10">
                            <button
                                v-if="item.status === 'done'"
                                @click.stop="toggleItem(item.id, false)"
                                :disabled="isDone"
                                :class="{ 'opacity-50': isDone }"
                                class="w-10 h-10 rounded-xl bg-[#D0F471] border border-[#D0F471] flex items-center justify-center text-[#1E1E1E] transform transition-all active:scale-95"
                            >
                                <Icon icon="ph:check-bold" class="w-4 h-4" />
                            </button>

                            <button
                                v-else
                                :disabled="isDone"
                                :class="{ 'opacity-50': isDone }"
                                @click.stop="toggleItem(item.id, true)"
                                class="w-10 h-10 rounded-xl bg-white border-2 border-gray-200 flex items-center justify-center text-gray-300 hover:border-[#D0F471] hover:text-[#D0F471] transform transition-all active:scale-95"
                            >
                                <Icon icon="ph:check-bold" class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- CONTENT: CLIENT -->
        <div v-if="activeTab === 'client'" class="mt-6 px-6">
            <h3 class="font-bold text-lg mb-2">Information du bâtiment</h3>
            <p class="text-sm text-gray-600 mb-6">
                L'entrée se trouve côté Ouest. Code porte: 1234A. Ouvert de 9h à
                18h.
            </p>

            <h3 class="font-bold text-lg mb-4">Référents</h3>

            <div
                class="bg-[#F8F9FA] p-3 rounded-lg mb-2 flex justify-between items-center"
            >
                <div>
                    <div class="font-bold text-sm">Christophe Doe</div>
                    <div class="text-xs text-gray-500">Accueil • Bat A</div>
                </div>
                <div
                    class="bg-[#EDE5FF] text-[#2E0099] px-2 py-1 rounded text-xs font-bold"
                >
                    06 12 34 56 78
                </div>
            </div>
        </div>

        <!-- CONTENT: NOTES -->
        <div v-if="activeTab === 'notes'" class="mt-6 px-6">
            <div class="space-y-4 mb-20">
                <div
                    v-for="note in m.notes"
                    :key="note.id"
                    class="flex flex-col gap-1"
                >
                    <div class="flex justify-between text-xs text-gray-400">
                        <span class="font-bold text-gray-800">{{
                            note.user.name
                        }}</span>
                        <!-- Simple date format -->
                        <span>{{
                            new Date(note.created_at).toLocaleDateString()
                        }}</span>
                    </div>
                    <div
                        class="bg-gray-100 p-3 rounded-lg text-sm text-gray-700"
                    >
                        {{ note.message }}
                    </div>
                </div>

                <p
                    v-if="!m.notes?.length"
                    class="text-center text-gray-400 py-10 text-sm"
                >
                    Aucune note pour le moment.
                </p>
            </div>

            <!-- Input sticky (handled by absolute but better valid layout usually) -->
            <div class="fixed bottom-32 left-6 right-6 z-10">
                <div
                    class="bg-white p-2 rounded-xl shadow-lg border border-gray-100 flex gap-2"
                >
                    <input
                        v-model="noteInput"
                        placeholder="Mon message..."
                        class="flex-1 outline-none text-sm px-2"
                    />
                    <button
                        @click="sendNote"
                        class="bg-[#D0F471] px-4 py-2 rounded-lg font-bold text-xs"
                    >
                        Envoyer
                    </button>
                </div>
            </div>
        </div>

        <!-- Footer Actions (Incident / Close) -->
        <div
            v-if="!isDone"
            class="fixed bottom-0 left-0 right-0 p-4 bg-white border-t border-gray-100 flex gap-3 z-30"
        >
            <button
                @click="goToIncident"
                class="flex-1 bg-[#2C2C2C] text-white py-3 rounded-2xl font-bold text-sm flex items-center justify-center gap-2 transition-all"
            >
                Incident
                <Icon icon="ph:warning-fill" />
            </button>
            <button
                @click="triggerClose"
                :class="{ 'opacity-40 grayscale': !allItemsChecked }"
                class="flex-1 bg-[#D0F471] text-[#1E1E1E] py-3 rounded-2xl font-bold text-sm flex items-center justify-center gap-2 transition-all"
            >
                Clôturer
                <Icon icon="ph:check-circle-fill" />
            </button>
        </div>

        <div
            v-else
            class="fixed bottom-0 left-0 right-0 p-4 bg-gray-50 border-t border-gray-100 z-30 pb-6 text-center text-gray-500 font-bold"
        >
            Mission clôturée
        </div>

        <!-- Modal Confirmation -->
        <div
            v-if="showConfirmModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm p-6"
            style="margin: 0 !important"
        >
            <div
                class="bg-white px-6 py-8 rounded-[32px] w-full max-w-sm text-center shadow-2xl relative animate-in fade-in zoom-in duration-200"
            >
                <h3
                    class="text-lg font-bold text-[#1E1E1E] mb-8 mt-2 px-2 leading-tight"
                >
                    Etes vous sur de vouloir clôturer cette tâche ?
                </h3>

                <div class="flex gap-3">
                    <button
                        @click="showConfirmModal = false"
                        class="flex-1 py-3 rounded-full bg-[#F5F5F5] font-bold text-[#1E1E1E]"
                    >
                        Non
                    </button>
                    <button
                        @click="confirmClose"
                        class="flex-1 py-3 rounded-full bg-[#D0F471] font-bold text-[#1E1E1E]"
                    >
                        Oui
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div v-else class="flex items-center justify-center h-screen">
        Chargement...
    </div>
</template>
