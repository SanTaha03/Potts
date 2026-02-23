<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { Icon } from "@iconify/vue";
import AnimatedNumber from "@/components/AnimatedNumber.vue";
import { useDeviceStore } from "@/stores/deviceStore";
import { useAuthStore } from "@/stores/authStore";
import { formatDistanceToNow } from "date-fns";
import { fr } from "date-fns/locale";

const route = useRoute();
const router = useRouter();
const deviceStore = useDeviceStore();
const authStore = useAuthStore();

const showAnomalySheet = ref(false);

const isTech = computed(() => authStore.currentUser?.role === "tech");

// History polling timer
let historyInterval: number | null = null;

// Fetch real data on mount with polling
onMounted(async () => {
    const id = route.params.id as string;

    // Start polling device (live status)
    deviceStore.startPollingDevice(id, 10000); // 10s

    // Initial history load
    await loadHistory(id);

    // Poll history every 30s
    historyInterval = window.setInterval(() => {
        loadHistory(id);
    }, 30000);
});

onUnmounted(() => {
    deviceStore.stopPollingDevice();
    if (historyInterval) {
        clearInterval(historyInterval);
        historyInterval = null;
    }
});

async function loadHistory(id: string) {
    try {
        const history = await deviceStore.fetchHistory(id, "soil_pct", "7d");
        if (history.data.length > 0) {
            weeklyHealth.value = history.data
                .slice(-7)
                .map((d) => Math.round(d.val));
        }
    } catch (e) {
        console.error("Failed to load history", e);
    }
}

const plant = computed(() => {
    const d = deviceStore.currentDevice;
    if (!d)
        return {
            id: 0,
            name: "Loading...",
            subtitle: "",
            location: "",
            tag: "",
            exposure: { label: "-", value: 0, status: "" },
            humidity: { value: 0, status: "" },
            temperature: { value: 0, status: "" },
            image: "",
            description: "",
            waterLevel: 0,
        };

    return {
        id: d.id,
        name: d.name || d.device_id,
        subtitle: d.device_id,
        location: d.location
            ? `${d.location.site ?? ""} ${d.location.floor ? "Etage " + d.location.floor : ""}`
            : "Non localisé",
        tag: `#${d.device_id}`,
        exposure: {
            label: `${d.last_values?.light_pct ?? 0}%`,
            value: d.last_values?.light_pct ?? 0,
            status:
                (d.last_values?.light_pct ?? 0) > 50 ? "Adéquate" : "Faible",
        },

        humidity: {
            value: d.last_values?.soil_pct ?? 0,
            status:
                (d.last_values?.soil_pct ?? 0) > 30 ? "Suffisante" : "Faible",
        },
        temperature: {
            value: parseFloat((d.last_values?.temp_c ?? 0).toFixed(1)),
            status: (d.last_values?.temp_c ?? 0) > 18 ? "Adéquate" : "Froide",
        },
        image: "/images/monstera.png",
        description: ` La Monstera Deliciosa...`,
        waterLevel: (d.last_values?.soil_pct ?? 0) / 100,
        isOffline: !d.is_online,
        lastUpdated: d.last_values?.sent_at
            ? formatDistanceToNow(new Date(d.last_values.sent_at), {
                  addSuffix: true,
                  locale: fr,
              })
            : "Jamais",
    };
});

// Reactivity for Chart
const weeklyHealth = ref([25, 24, 32, 10, 51, 80, 23]); // Default mock

const alerts = computed(() => {
    return deviceStore.currentDevice?.meta?.alerts || [];
});

const goBack = () => {
    const missionId = route.query.mission;
    if (missionId) {
        router.push(`/tech/missions/${missionId}`);
    } else {
        router.back();
    }
};
</script>

<template>
    <div class="relative min-h-screen w-full bg-white font-popins pb-20">
        <!-- Header -->
        <header class="relative pb-2 px-6 pt-6">
            <div class="flex items-start gap-4">
                <!-- Back Button -->
                <button
                    @click="goBack"
                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-[#F1F2F3] text-dark-green-2"
                >
                    <Icon icon="ph:arrow-left-bold" class="h-5 w-5" />
                </button>

                <!-- Title & Info -->
                <div class="flex flex-col">
                    <div class="flex items-center gap-3">
                        <h1
                            class="text-[32px] font-popart leading-none text-dark-green-2"
                        >
                            {{ plant.name }}
                        </h1>
                        <span
                            class="rounded-md bg-[#EDE5FF] px-1.5 py-1 text-sm font-bold text-[#2E0099]"
                        >
                            {{ plant.location }}
                        </span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span
                            class="flex items-center justify-center rounded-lg bg-white py-2 text-sm text-dark-green-2"
                        >
                            {{ plant.tag }}
                        </span>
                        <span
                            v-if="plant.lastUpdated"
                            class="flex items-center gap-1 text-xs text-gray-400"
                        >
                            <Icon icon="ph:clock" class="text-xs" />
                            {{ plant.lastUpdated }}
                        </span>
                    </div>
                </div>
            </div>
        </header>

        <main class="space-y-4 px-6 md:px-0">
            <!-- Added padding for mobile -->
            <!-- Hero Section: Image + Floating Stats -->
            <div
                class="relative h-[380px] w-full overflow-hidden md:overflow-visible"
            >
                <!-- Overflow handling -->
                <!-- Plant Image Container (Left/Center) -->
                <div
                    class="absolute -left-1/3 top-3 bottom-0 right-[100px] rounded-[32px]"
                >
                    <img
                        :src="plant.image"
                        alt="Monstera"
                        class="h-full w-full object-cover sm:object-contain"
                    />

                    <!-- Decorative Water Gauge on Image Edge -->
                    <div
                        class="absolute right-8 bottom-6 flex flex-col items-center"
                    >
                        <div
                            class="relative h-32 w-2 rounded-full bg-gray-100 backdrop-blur-sm"
                        >
                            <div
                                class="absolute bottom-0 w-full rounded-full bg-[#2072DF] transition-all duration-2000 ease-out"
                                :style="{
                                    height: `${plant.waterLevel * 100}%`,
                                }"
                            ></div>
                        </div>
                        <div
                            class="mt-2 flex h-8 w-8 items-center justify-center rounded-full bg-gray-100 backdrop-blur"
                        >
                            <Icon
                                icon="ph:drop-fill"
                                class="h-4 w-4 text-[#2072DF]"
                            />
                        </div>
                    </div>
                </div>

                <!-- Floating Stats Widgets (Right Column) -->
                <div
                    class="absolute right-0 top-0 flex w-[120px] flex-col gap-3 z-10 mr-4 md:mr-0"
                >
                    <!-- Temp Card -->
                    <div
                        class="rotate-[2deg] rounded-3xl bg-[#F7F7F8] p-3 border border-white/50"
                    >
                        <div class="flex items-center justify-between mb-2">
                            <div
                                class="flex h-6 w-6 items-center justify-center rounded-full bg-white shadow-[0_0_0_2px_#B1ED12]"
                            >
                                <Icon
                                    icon="ph:thermometer-simple-bold"
                                    class="text-dark-green-2 text-xs"
                                />
                            </div>
                            <span class="text-2xl font-bold text-dark-green-2">
                                <AnimatedNumber
                                    :value="plant.temperature.value"
                                    :precision="1"
                                    suffix="°"
                                />
                            </span>
                        </div>
                        <div>
                            <p class="text-[10px] text-[#454C54]">
                                Température
                            </p>
                            <span
                                class="inline-block rounded-md bg-[#EFFBD0] px-1.5 py-[2px] text-[10px] font-bold text-[#475F07]"
                            >
                                {{ plant.temperature.status }}
                            </span>
                        </div>
                    </div>

                    <!-- Humidity Card -->
                    <div
                        class="-rotate-[2deg] rounded-3xl bg-[#F7F7F8] p-3 border border-white/50"
                    >
                        <div class="flex items-center justify-between mb-2">
                            <div
                                class="flex h-6 w-6 items-center justify-center rounded-full bg-white shadow-[0_0_0_2px_#FC69BB]"
                            >
                                <div
                                    class="h-4 w-4 bg-white rounded-full flex items-center justify-center text-[10px]"
                                >
                                    <Icon
                                        icon="ph:drop-bold"
                                        class="text-dark-green-2"
                                    />
                                </div>
                            </div>
                            <span class="text-2xl font-bold text-dark-green-2">
                                <AnimatedNumber
                                    :value="plant.humidity.value"
                                    suffix="%"
                                />
                            </span>
                        </div>
                        <div>
                            <p class="text-[10px] text-[#454C54]">Humidité</p>
                            <span
                                class="inline-block rounded-md bg-[#FFE6F4] px-1.5 py-[2px] text-[10px] font-bold text-[#640239]"
                            >
                                {{ plant.humidity.status }}
                            </span>
                        </div>
                    </div>

                    <!-- Exposure Card -->
                    <div
                        class="rotate-[2deg] rounded-3xl bg-[#F7F7F8] p-3 border border-white/50"
                    >
                        <div class="flex items-center justify-between mb-2">
                            <div
                                class="flex h-6 w-6 items-center justify-center rounded-full bg-white shadow-[0_0_0_2px_#B1ED12]"
                            >
                                <Icon
                                    icon="ph:sun-dim-bold"
                                    class="text-dark-green-2 text-xs"
                                />
                            </div>
                        </div>
                        <p
                            class="text-xl font-bold text-dark-green-2 mb-1 leading-tight"
                        >
                            <AnimatedNumber
                                :value="plant.exposure.value"
                                suffix="%"
                            />
                        </p>
                        <div>
                            <p class="text-[10px] text-[#454C54]">Exposition</p>
                            <span
                                class="inline-block rounded-md bg-[#EFFBD0] px-1.5 py-[2px] text-[10px] font-bold text-[#475F07]"
                            >
                                {{ plant.exposure.status }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Alerts Section -->
            <div
                v-if="!plant.isOffline && alerts.length > 0"
                class="mt-4 mb-4 p-3 bg-red-50 border border-red-200 rounded-2xl"
            >
                <div class="flex items-center gap-2 mb-2">
                    <Icon
                        icon="ph:warning-circle-bold"
                        class="text-red-600 text-lg"
                    />
                    <h3 class="font-bold text-red-800 text-sm">Attention</h3>
                </div>
                <ul
                    class="list-disc list-inside text-sm text-red-700 space-y-1"
                >
                    <li v-for="(alert, idx) in alerts" :key="idx">
                        {{ alert }}
                    </li>
                </ul>
            </div>
            <!-- Detail Block -->
            <div class="bg-gray-50 rounded-2xl p-4 text-sm text-gray-600">
                <h3 class="font-bold text-gray-800 mb-2">Description</h3>
                <p>{{ plant.description }}</p>
            </div>
        </main>
        <!-- Floating Bottom Action Bar-->
        <div
            class="fixed bottom-4 right-0 transform -translate-x-1/2 z-40 mx-5"
        >
            <button
                @click="showAnomalySheet = true"
                class="flex items-center justify-center gap-2 rounded-xl bg-[#D0F471] px-4 py-3 text-base text-dark-green-2 hover:bg-[#c2e666] transition-colors"
            >
                Signaler une anomalie
                <div
                    class="flex h-4 w-4 items-center justify-center rounded-full "
                >
                    <Icon icon="ph:warning-fill" class="h-4 w-4 text-dark-green-2" />
                </div>
            </button>
        </div>
    </div>
</template>
