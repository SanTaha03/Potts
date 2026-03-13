<script setup lang="ts">
import { computed } from "vue";
import { Icon } from "@iconify/vue";
import { format } from "date-fns";
import { fr } from "date-fns/locale";
import type { Mission } from "@/stores/missionStore";

// Props
const props = defineProps<{
    missions: Mission[];
    date: string;
}>();

const emit = defineEmits(["close"]);

// Date formatted
const formattedDate = computed(() => {
    return format(new Date(props.date), "EEEE d MMMM", { locale: fr });
});

// Checklist Logic
const checklist = computed(() => {
    const list = {
        base: [
            "Vaporisateur",
            "Chiffons",
            "Arrosoir d’intérieur",
            "Testeur d’humidité",
            "Testeur de luminosité",
            "Testeur de température",
            "Station de bouturage", // As per image
            "Gants", // Added based on prompt
            "Sécateur", // Added based on prompt
        ],
        maintenance: [] as string[],
        replacement: [] as string[],
        installation: [] as string[],
        specifics: [] as string[],
    };

    // Counters
    let nbMaintenance = 0;
    let nbReplacement = 0;
    let nbInstallation = 0;

    // Plant counters
    let plantsToReplace = 0;
    let plantsToInstall = 0;

    props.missions.forEach((m) => {
        if (m.type === "maintenance") nbMaintenance++;
        if (m.type === "replacement") nbReplacement++;
        if (m.type === "installation") nbInstallation++;

        // Count specific items if available
        if (m.items) {
            m.items.forEach((item) => {
                if (item.action === "replace") plantsToReplace++;
                if (item.action === "install") plantsToInstall++;
            });
        }
    });

    // Populate Dynamic Lists
    if (nbMaintenance > 0) {
        // list.maintenance.push('Engrais liquide');
        // list.maintenance.push('Tuteurs');
    }

    if (nbReplacement > 0) {
        list.replacement.push("Cisaille");
        list.replacement.push("Sécateur");
        list.replacement.push("Gants");
        list.replacement.push("Sac de transport");
    }

    if (nbInstallation > 0) {
        list.installation.push("Terreau universel");
        list.installation.push("Billes d'argile");
        list.installation.push("Pots de rechange");
    }

    // Specific Items (Plants)
    if (plantsToReplace > 0) {
        /*
            Si des plantes spécifiques à remplacer sont mentionnées dans les missions, les lister ici avec leur nom et ID si possible. 
            Sinon, indiquer "Plantes à remplacer : X" où X est le nombre total de plantes à remplacer.
            Idem pour les installations.
        */

        props.missions.forEach((m) => {
            if (m.items) {
                m.items.forEach((item) => {
                    if (item.action === "replace") {
                        // Try to find name in meta or device
                        // Assuming new device name is in meta or standard device
                        const name =
                            item.meta?.new_device_name || "Plante à remplacer";
                        const id =
                            item.meta?.new_device_id || item.device_id || "???";
                        list.specifics.push(`${name} #${id}`);
                    }
                });
            }
        });
    }

    return list;
});

// Helper for "Monsterrat #12345" parsing if needed,
// but we constructed strings in specific list above.
function parseItem(str: string) {
    const parts = str.split("#");
    if (parts.length === 2) {
        return { name: parts[0].trim(), tag: "#" + parts[1].trim() };
    }
    return { name: str, tag: "" };
}
</script>

<template>
    <div class="h-full flex flex-col bg-white">
        <!-- Header -->
        <div
            class="pb-2 px-2 border-b border-gray-100 flex items-center justify-between sticky top-0 bg-white z-10"
        >
            <div>
                <h2 class="text-xl font-bold text-[#1E1E1E]">
                    Matériel nécessaire
                </h2>
                <p class="text-sm text-gray-500 capitalize">
                    {{ formattedDate }}
                </p>
            </div>
            <button
                @click="$emit('close')"
                class="p-2 bg-gray-100 rounded-full hover:bg-gray-200 transition"
            >
                <Icon icon="ph:x-bold" class="text-gray-600" />
            </button>
        </div>

        <!-- Scrollable Content -->
        <div class="flex-1 overflow-y-auto space-y-6 pt-2 pb-20">
            <!-- BASE / ENTRETIEN (Merged as per image "Entretient") -->
            <div class="bg-[#F8F9FA] rounded-2xl p-5">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <Icon icon="ph:broom-bold" class="text-lg" />
                        <h3 class="font-bold text-[#1E1E1E]">Entretien</h3>
                    </div>

                    <div class="relative flex items-center">
                        <input
                            type="checkbox"
                            class="peer h-5 w-5 cursor-pointer appearance-none rounded-md border border-gray-300 bg-white transition-all checked:border-[#D0F471] checked:bg-[#D0F471]"
                        />
                        <Icon
                            icon="ph:check-bold"
                            class="absolute pointer-events-none opacity-0 peer-checked:opacity-100 text-[#1E1E1E] w-3.5 h-3.5 left-0.5"
                        />
                    </div>
                </div>
                <ul class="space-y-2 ml-1">
                    <li
                        v-for="item in checklist.base"
                        :key="item"
                        class="flex items-center gap-2 text-sm text-[#4C5D70]"
                    >
                        <span class="w-1 h-1 bg-gray-400 rounded-full"></span>
                        {{ item }}
                    </li>
                </ul>
            </div>

            <!-- TAILLE ET COUPE (If Replacement/Maintenance) -->
            <div
                v-if="checklist.replacement.length > 0"
                class="bg-[#F8F9FA] rounded-2xl p-5"
            >
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <Icon icon="ph:scissors-bold" class="text-lg" />
                        <h3 class="font-bold text-[#1E1E1E]">
                            Taille et coupe
                        </h3>
                    </div>
                    <div class="relative flex items-center">
                        <input
                            type="checkbox"
                            class="peer h-5 w-5 cursor-pointer appearance-none rounded-md border border-gray-300 bg-white transition-all checked:border-[#D0F471] checked:bg-[#D0F471]"
                        />
                        <Icon
                            icon="ph:check-bold"
                            class="absolute pointer-events-none opacity-0 peer-checked:opacity-100 text-[#1E1E1E] w-3.5 h-3.5 left-0.5"
                        />
                    </div>
                </div>
                <ul class="space-y-2 ml-1">
                    <li
                        v-for="item in checklist.replacement"
                        :key="item"
                        class="flex items-center gap-2 text-sm text-[#4C5D70]"
                    >
                        <span class="w-1 h-1 bg-gray-400 rounded-full"></span>
                        {{ item }}
                    </li>
                </ul>
            </div>

            <!-- SPECIFIC PLANTS (Pott's) -->
            <div
                v-if="checklist.specifics.length > 0"
                class="bg-[#F8F9FA] rounded-2xl p-5"
            >
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-2">
                        <Icon icon="ph:plant-bold" class="text-lg" />
                        <h3 class="font-bold text-[#1E1E1E]">Pott's</h3>
                    </div>
                    <div class="relative flex items-center">
                        <input
                            type="checkbox"
                            class="peer h-5 w-5 cursor-pointer appearance-none rounded-md border border-gray-300 bg-white transition-all checked:border-[#D0F471] checked:bg-[#D0F471]"
                        />
                        <Icon
                            icon="ph:check-bold"
                            class="absolute pointer-events-none opacity-0 peer-checked:opacity-100 text-[#1E1E1E] w-3.5 h-3.5 left-0.5"
                        />
                    </div>
                </div>
                <ul class="space-y-3">
                    <li
                        v-for="itemStr in checklist.specifics"
                        :key="itemStr"
                        class="flex items-center gap-2 text-sm text-[#1E1E1E]"
                    >
                        <span
                            class="w-1 h-1 bg-gray-400 rounded-full shrink-0"
                        ></span>
                        <span>{{ parseItem(itemStr).name }}</span>
                        <span
                            v-if="parseItem(itemStr).tag"
                            class="bg-[#DFE1E3] px-1.5 py-0.5 rounded textxs font-bold text-gray-600"
                        >
                            {{ parseItem(itemStr).tag }}
                        </span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>
