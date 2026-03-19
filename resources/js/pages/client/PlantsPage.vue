<script setup lang="ts">
import { computed, onMounted, onUnmounted, reactive, ref } from "vue";
import { useRouter } from "vue-router";
import { Icon } from "@iconify/vue";
import WaterGauge from "@/components/charts/WaterGauge.vue";
import { useDeviceStore } from "@/stores/deviceStore";

interface Plant {
    id: number;
    name: string;
    species: string;
    building: string;
    floor: string;
    waterLevel: number;
    status: "ok" | "alert" | "offline"; // Added offline
    image: string;
}

const router = useRouter();
const deviceStore = useDeviceStore();

const viewMode = ref<"list" | "plan">("list");

const filters = reactive({
    building: "",
    floor: "",
    search: "",
    status: "Tout" as "Tout" | "En ligne" | "Hors ligne",
});

const selectedTag = ref("Tout");

const plantTags = [
    "Tout",
    "En ligne",
    "Hors ligne",
    "Monstera",
    "Basilic",
    "Strelitzia",
    "Ficus",
    "Pachira",
];

const selectedSpeciesTag = ref("Tout");

onMounted(() => {
    // Start polling list every 20s
    deviceStore.startPollingList(20000);
});

onUnmounted(() => {
    deviceStore.stopPollingList();
});

// Map API devices to Plant interface for the UI
const plants = computed<Plant[]>(() => {
    return deviceStore.items.map((d) => ({
        id: d.id,
        name: d.name || d.device_id, // fallback to ID
        species: d.meta?.type || "Inconnue",
        // Derive building/floor from location or defaults
        building: d.location?.site || "Showroom",
        floor: d.location?.floor || "Étage non renseigné",
        // Calculate water level 0-1 from 0-100 soil_pct
        waterLevel: (d.last_values?.soil_pct ?? 0) / 100,
        // Status logic
        status: d.is_online ? "ok" : "offline",
        image: "/images/monstera.png", // Placeholder for now
    }));
});

const buildings = computed(() => {
    const real = Array.from(
        new Set(plants.value.map((p) => p.building)),
    ).sort();
    return real;
});

const floors = computed(() => {
    // On peut vouloir trier par numéro d'étage si nécessaire, ici string sort simple
    const real = Array.from(new Set(plants.value.map((p) => p.floor))).sort();
    return real;
});
const showBuildingSheet = ref(false);
const showFloorSheet = ref(false);

// Data Cleaned

const filteredPlants = computed(() => {
    const query = filters.search.trim().toLowerCase();
    return plants.value.filter((plant) => {
        const matchBuilding = filters.building
            ? plant.building === filters.building
            : true;
        const matchFloor = filters.floor ? plant.floor === filters.floor : true;
        const matchQuery = query
            ? plant.name.toLowerCase().includes(query)
            : true;

        let matchTag = true;
        if (selectedSpeciesTag.value === "En ligne") {
            matchTag = plant.status === "ok";
        } else if (selectedSpeciesTag.value === "Hors ligne") {
            matchTag = plant.status === "offline";
        } else if (selectedSpeciesTag.value !== "Tout") {
            // Basic text match for species - assuming species names are in French/aligned
            // We check if the plant species contains the tag (e.g. "Monstera Deliciosa" contains "Monstera")
            matchTag = plant.species
                .toLowerCase()
                .includes(selectedSpeciesTag.value.toLowerCase());
        }

        return matchBuilding && matchFloor && matchQuery && matchTag;
    });
});

// Ouvre la carte en redirigeant vers la future page détail.
const openPlantDetail = (plant: Plant) => {
    router
        .push({ name: "plant-detail", params: { id: plant.id } })
        .catch(() => undefined);
};

const selectBuilding = (value: string) => {
    filters.building = value;
    showBuildingSheet.value = false;
};

const selectFloor = (value: string) => {
    filters.floor = value;
    showFloorSheet.value = false;
};

// Permettra d’ouvrir un panneau de filtres avancés (placeholder pour l’instant).
const triggerFilters = () => {
    console.info("Filters panel coming soon");
};
</script>

<template>
    <section class="relative space-y-4 pb-24">
        <div class="bg-white">
            <h1 class="text-xl font-bold text-dark-green-2 mb-4">
                Liste des plantes
            </h1>

            <!-- Dropdowns -->
            <div class="flex gap-4">
                <button
                    type="button"
                    class="flex-1 flex items-center justify-between bg-white border border-gray-200 rounded-xl px-4 py-3 text-sm text-[#1E1E1E]"
                    @click="showBuildingSheet = true"
                >
                    <span class="truncate">{{
                        filters.building || "Tout les bâtiments"
                    }}</span>
                    <Icon icon="ph:caret-down" class="text-gray-400" />
                </button>

                <button
                    type="button"
                    class="flex-1 flex items-center justify-between bg-white border border-gray-200 rounded-xl px-4 py-3 text-sm text-[#1E1E1E]"
                    @click="showFloorSheet = true"
                >
                    <span class="truncate">{{
                        filters.floor || "Tous les étages"
                    }}</span>
                    <Icon icon="ph:caret-down" class="text-gray-400" />
                </button>
            </div>
        </div>

        <!-- Search & Filter -->
        <div class="flex gap-3">
            <div class="relative flex-1">
                <input
                    v-model="filters.search"
                    placeholder="Rechercher.."
                    class="w-full bg-white border border-gray-200 rounded-xl py-3 pl-4 pr-10 text-sm focus:outline-none focus:border-gray-400"
                />
                <Icon
                    icon="ph:magnifying-glass"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 w-5 h-5"
                />
            </div>
        </div>

        <!-- Tags -->
        <div class="flex gap-2 overflow-x-auto pb-1 no-scrollbar -mx-4 px-4">
            <button
                v-for="tag in plantTags"
                :key="tag"
                class="whitespace-nowrap px-4 py-1.5 rounded-lg text-sm font-medium border transition-colors"
                :class="
                    selectedSpeciesTag === tag
                        ? 'bg-[#E0E0E0] border-[#E0E0E0] text-[#1E1E1E]'
                        : 'bg-white border-gray-200 text-gray-500'
                "
                @click="selectedSpeciesTag = tag"
            >
                {{ tag }}
            </button>
        </div>

        <!-- Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
            <div
                v-for="plant in filteredPlants"
                :key="plant.id"
                class="bg-white border border-gray-300 rounded-2xl p-3 flex flex-col items-center relative shadow-[0_2px_8px_rgba(0,0,0,0.04)] active:scale-[0.98] transition-transform"
                @click="openPlantDetail(plant)"
            >
                <!-- Image Area -->
                <div
                    class="relative w-full aspect-square mb-2 flex items-center justify-center"
                >
                    <img
                        :src="plant.image"
                        :alt="plant.name"
                        class="w-full h-full object-contain drop-shadow-sm"
                    />

                    <div
                        class="absolute top-1 right-1 w-6 h-6 bg-white rounded-full flex items-center justify-center shadow-sm text-blue-500"
                    >
                        <!-- Icon gauge/status mini -->
                        <Icon icon="ph:drop" class="w-4 h-4" />
                    </div>
                </div>

                <!-- Info Area -->
                <div class="w-full text-left">
                    <h3 class="font-bold text-[#1E1E1E] text-sm truncate">
                        {{ plant.name }}
                    </h3>

                    <div class="flex justify-between items-center mt-1">
                        <span
                            class="bg-[#EFEFEF] text-gray-600 text-[10px] font-bold px-2 py-0.5 rounded"
                        >
                            #{{ plant.id }}
                        </span>

                        <!-- Alert Indicator -->
                        <Icon
                            v-if="plant.status === 'alert'"
                            icon="ph:warning-fill"
                            class="text-red-500 w-4 h-4"
                        />
                        <Icon
                            v-else-if="plant.status === 'offline'"
                            icon="ph:wifi-slash"
                            class="text-gray-400 w-4 h-4"
                        />
                        <Icon
                            v-else
                            icon="ph:wifi-high"
                            class="text-green-500 w-4 h-4"
                        />
                    </div>
                </div>
            </div>
        </div>

        <div
            v-if="filteredPlants.length === 0"
            class="text-center py-10 text-gray-400"
        >
            Aucune plante trouvée.
        </div>

        <!-- Bottom sheet Building -->
        <div
            v-if="showBuildingSheet"
            class="fixed inset-0 z-50 flex items-end bg-black/40 backdrop-blur-sm"
            @click.self="showBuildingSheet = false"
        >
            <div
                class="w-full rounded-t-3xl bg-white p-4 max-h-[70vh] overflow-y-auto shadow-2xl animate-in slide-in-from-bottom duration-200"
            >
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-[#1E1E1E]">
                        Choisir un bâtiment
                    </h3>
                    <button
                        type="button"
                        class="p-2 bg-gray-100 rounded-full"
                        @click="showBuildingSheet = false"
                    >
                        <Icon icon="ph:x-bold" class="h-4 w-4" />
                    </button>
                </div>
                <ul class="space-y-2">
                    <li>
                        <button
                            type="button"
                            class="flex w-full items-center justify-between rounded-2xl border px-4 py-3 text-left transition"
                            :class="
                                filters.building === ''
                                    ? 'border-primary-green bg-secondary-green text-tertiary-green'
                                    : 'border-gray-200 bg-white text-[#2F2C36]'
                            "
                            @click="selectBuilding('')"
                        >
                            <span class="text-sm font-semibold"
                                >Tout les bâtiments</span
                            >
                            <Icon
                                v-if="filters.building === ''"
                                icon="ph:check-circle-fill"
                                class="h-5 w-5"
                            />
                        </button>
                    </li>
                    <li v-for="building in buildings" :key="building">
                        <button
                            type="button"
                            class="flex w-full items-center justify-between rounded-2xl border px-4 py-3 text-left transition"
                            :class="
                                filters.building === building
                                    ? 'border-primary-green bg-secondary-green text-tertiary-green'
                                    : 'border-gray-200 bg-white text-[#2F2C36]'
                            "
                            @click="selectBuilding(building)"
                        >
                            <span class="text-sm font-semibold">{{
                                building
                            }}</span>
                            <Icon
                                v-if="filters.building === building"
                                icon="ph:check-circle-fill"
                                class="h-5 w-5"
                            />
                        </button>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Bottom sheet Floor -->
        <div
            v-if="showFloorSheet"
            class="fixed inset-0 z-50 flex items-end bg-black/40 backdrop-blur-sm"
            @click.self="showFloorSheet = false"
        >
            <div
                class="w-full rounded-t-3xl bg-white p-4 max-h-[70vh] overflow-y-auto shadow-2xl animate-in slide-in-from-bottom duration-200"
            >
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-[#1E1E1E]">
                        Choisir un étage
                    </h3>
                    <button
                        type="button"
                        class="p-2 bg-gray-100 rounded-full"
                        @click="showFloorSheet = false"
                    >
                        <Icon icon="ph:x-bold" class="h-4 w-4" />
                    </button>
                </div>
                <ul class="space-y-2">
                    <li>
                        <button
                            type="button"
                            class="flex w-full items-center justify-between rounded-2xl border px-4 py-3 text-left transition"
                            :class="
                                filters.floor === ''
                                    ? 'border-primary-green bg-secondary-green text-tertiary-green'
                                    : 'border-gray-200 bg-white text-[#2F2C36]'
                            "
                            @click="selectFloor('')"
                        >
                            <span class="text-sm font-semibold"
                                >Tous les étages</span
                            >
                            <Icon
                                v-if="filters.floor === ''"
                                icon="ph:check-circle-fill"
                                class="h-5 w-5"
                            />
                        </button>
                    </li>
                    <li v-for="floor in floors" :key="floor">
                        <button
                            type="button"
                            class="flex w-full items-center justify-between rounded-2xl border px-4 py-3 text-left transition"
                            :class="
                                filters.floor === floor
                                    ? 'border-primary-green bg-secondary-green text-tertiary-green'
                                    : 'border-gray-200 bg-white text-[#2F2C36]'
                            "
                            @click="selectFloor(floor)"
                        >
                            <span class="text-sm font-semibold">{{
                                floor
                            }}</span>
                            <Icon
                                v-if="filters.floor === floor"
                                icon="ph:check-circle-fill"
                                class="h-5 w-5"
                            />
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </section>
</template>
