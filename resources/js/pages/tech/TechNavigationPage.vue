<script setup lang="ts">
import { ref } from "vue";
import { Icon } from "@iconify/vue";

const address = ref("");
const showOptions = ref(false);

const navigationApps = [
    {
        name: "Google Maps",
        icon: "logos:google-maps",
        getUrl: (q: string) =>
            `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(q)}`,
    },
    {
        name: "Waze",
        icon: "bxl:waze",
        getUrl: (q: string) => `https://waze.com/ul?q=${encodeURIComponent(q)}`,
    },
];

const handleSearch = () => {
    if (!address.value.trim()) return;
    showOptions.value = true;
};

const openApp = (app: any) => {
    window.open(app.getUrl(address.value), "_blank");
    showOptions.value = false;
};
</script>

<template>
    <div class="flex flex-col h-screen bg-[#FDFEFE] relative overflow-hidden">
        <!-- Background pattern (simple approximation) -->
        <div
            class="absolute inset-0 pointer-events-none opacity-[0.03]"
            style="
                background-image: url(&quot;data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0MCIgaGVpZ2h0PSI0MCIgdmlld0JveD0iMCAwIDQwIDQwIj48cGF0aCBkPSJNMjAgMjBMMCAwTTQwIDQwTDIwIDIwIiBzdHJva2U9IiMwMDAiIHN0cm9rZS13aWR0aD0iMSIvPjwvc3ZnPg==&quot;);
            "
        ></div>

        <div
            class="flex flex-col items-center justify-center flex-1 px-8 z-10 -mt-20"
        >
            <!-- Map Icon Placeholder -->
            <div class="w-24 h-24 mb-10 text-gray-400">
                <!-- Using an icon similar to the image -->
                <Icon
                    icon="ph:map-trifold-fill"
                    class="w-full h-full opacity-60"
                />
            </div>

            <h1 class="text-xl font-bold text-[#1E1E1E] mb-8 text-center">
                Où voulez-vous allez ?
            </h1>

            <div class="w-full space-y-4">
                <input
                    v-model="address"
                    type="text"
                    placeholder="11 rue de la paix, Nantes"
                    class="w-full bg-white border border-gray-200 rounded-xl px-4 py-4 text-sm outline-none focus:border-[#D0F471] transition-colors shadow-sm"
                    @keyup.enter="handleSearch"
                />

                <button
                    @click="handleSearch"
                    class="w-full bg-[#D0F471] text-[#1E1E1E] font-bold py-4 rounded-xl flex items-center justify-center gap-2 active:scale-[0.98] transition-all"
                >
                    Rechercher
                    <Icon icon="ph:car-fill" class="w-5 h-5" />
                </button>
            </div>
        </div>

        <!-- Navigation Options Modal -->
        <div
            v-if="showOptions"
            class="absolute inset-0 z-50 flex items-end justify-center sm:items-center bg-black/20 backdrop-blur-sm"
            @click.self="showOptions = false"
        >
            <div
                class="bg-white w-full max-w-sm mx-4 mb-4 sm:mb-0 rounded-2xl p-6 shadow-xl animate-in slide-in-from-bottom-10 fade-in duration-200"
            >
                <h3 class="text-lg font-bold text-gray-900 mb-4 text-center">
                    Choisir un navigateur
                </h3>
                <div class="space-y-3">
                    <button
                        v-for="app in navigationApps"
                        :key="app.name"
                        @click="openApp(app)"
                        class="w-full flex items-center gap-4 p-3 border border-gray-100 rounded-xl hover:bg-gray-50 active:bg-gray-100 transition-colors"
                    >
                        <Icon :icon="app.icon" :class="['w-8 h-8']" />

                        <span class="font-medium text-gray-700">{{
                            app.name
                        }}</span>
                        <Icon
                            icon="ph:caret-right"
                            class="w-4 h-4 text-gray-400 ml-auto"
                        />
                    </button>
                </div>
                <button
                    @click="showOptions = false"
                    class="w-full mt-4 py-3 text-gray-500 font-medium hover:text-gray-700"
                >
                    Annuler
                </button>
            </div>
        </div>
    </div>
</template>
