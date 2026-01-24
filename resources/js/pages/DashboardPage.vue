<script setup lang="ts">
import { computed } from "vue";
import { useRouter } from "vue-router";
import { Icon } from "@iconify/vue";
import HalfDoughnutChart from "@/components/charts/HalfDoughnutChart.vue";
import WaterGauge from "@/components/charts/WaterGauge.vue";

const router = useRouter();

const summary = {
    healthyPlants: 34,
    healthLabel: "Bonnes santé",
    healthPercent: 82,
    avgTemperature: 19,
    avgHumidity: 63,
};

const healthChartData = [
  { label: "Bonne santé", value: 28, color: "var(--color-primary-green)" },
  { label: "État moyen", value: 6, color: "var(--color-primary-pink)" },
];

const visits = [
    {
        id:"next2",
        title: "Visite planifiée",
        subtitle: "Dans 14 jours",
        date: "Jeu. 8 Mai",
        icon: "carbon:arrow-up-right",
        actionIcon: "ph:calendar-check",
        type: "primary",
    },
    {
        id: "next",
        title: "Prochaine visite",
        subtitle: "Dans 6 jours",
        date: "Mer. 30 Avril",
        icon: "carbon:arrow-up-right",
        actionIcon: "ph:calendar-check",
        type: "primary",
    },
    {
        id: "last",
        title: "Dernière visite",
        subtitle: "Il y a 20 jours",
        date: "Lun. 10 Avril",
        icon: "ph:clock-counter-clockwise",
        actionIcon: "ph:file-pdf",
        type: "secondary",
    },
];

const recentPlants = [
    {
        id: 1,
        name: "Calathea",
        health: 89,
        water: 0.7,
        icon: "ph:flower-lotus-bold",
    },
    {
        id: 2,
        name: "Strelitzia",
        health: 87,
        water: 0.45,
        icon: "ph:flower-tulip-bold",
    },
    {
        id: 3,
        name: "Monstera",
        health: 96,
        water: 0.82,
        icon: "ph:leaf-bold",
    },
];

const healthGaugeStyle = computed(() => ({
    background: `conic-gradient(#e8796f 0deg ${Math.max(
        summary.healthPercent - 40,
        0
    )}deg, #cce5a0 ${Math.max(summary.healthPercent - 40, 0)}deg ${
        summary.healthPercent * 1.8
    }deg, #d9d9d9 ${summary.healthPercent * 1.8}deg 180deg)`,
}));

// Redirige vers la page des plantes depuis le raccourci "Voir tout".
const goToPlants = () => {
    router.push({ name: "plants" }).catch(() => undefined);
};
</script>

<template>
    <section class="grid gap-6 ">
        <!-- Half Doughnut Chart Section -->
         <div class="">
           <h2 class="text-2xl font-popart font-semibold text-[#2F2C36]">
               État de santé des plantes
           </h2>
           <div class="relative h-28  w-full ">
             <div class="absolute right-0 -mr-20">
               <HalfDoughnutChart :data="healthChartData" />
             </div>
             <!-- tag bonne santé -->
             <div class="absolute bottom-8 right-0 flex justify-center items-center w-fit bg-primary-green px-2 py-1 rounded-lg">
              <span class="font-popins text-xs">Bonne santé</span>
             </div>
            <!-- tag état moyen -->
            <div class="absolute -bottom-4 right-16 flex justify-center items-center w-fit bg-secondary-pink px-2 py-1 rounded-lg ml-4">
              <span class="font-popins text-xs">État moyen</span>
            </div>
           </div>
         </div>

        <div class="flex h-full items-end relative text-black">
            <!-- Card température -->
            <div
                class="flex-1 max-w-xs w-[40vw] rounded-[38px] bg-[#DBCCFF] border-4 border-transparent p-4 text-center -rotate-[3.73deg]"
            >
                <div class="flex items-center justify-center gap-1">
                    <Icon
                        icon="ph:thermometer-simple-duotone"
                        class="h-6 w-6"
                    />
                    <p class="text-xs font-popins font-medium">
                        Température moyenne
                    </p>
                </div>
                <p class="text-4xl font-popart mt-3 font-light">
                    {{ summary.avgTemperature }}º
                </p>
            </div>

            <!-- Card Humidité (chevauche + au-dessus) -->
            <div
                class="flex-1 max-w-xs w-[40vw] rounded-[38px] bg-[#FD9BD2] border-8 border-white p-4 text-center -rotate-[-2.17deg] -mb-6 -ml-6 z-10"
            >
                <div class="flex items-center justify-center gap-1">
                    <Icon icon="ph:drop-duotone" class="h-6 w-6" />
                    <p class="text-xs font-popins font-medium">
                        Humidité moyenne
                    </p>
                </div>
                <p class="text-4xl font-popart mt-3 font-light">
                    {{ summary.avgHumidity }}%
                </p>
            </div>
        </div>

        <!-- Card Visite du prestataire -->
        <div class="space-y-4 rounded-3xl bg-primary-green p-5 mt-2 h-fit">
            <header class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span
                        class="flex h-6 w-6 items-center justify-center rounded-full text-black"
                    >
                        <Icon icon="ic:sharp-emoji-people" class="h-5 w-5" />
                    </span>
                    <h2 class="text-lg font-popart">Visite du prestataire</h2>
                </div>
            </header>
            <ul class="space-y-3">
                <li
                    v-for="visit in visits"
                    :key="visit.id"
                    class="flex items-center justify-between bg-secondary-green rounded-3xl px-4 py-3 text-sm text-[#4e4439]"
                >
                    <div class="flex items-center gap-3">
                        <span
                            class="flex h-6 w-6 items-center justify-center rounded-xl"
                            :class="
                                visit.type === 'primary'
                                    ? 'bg-secondary-green text-black'
                                    : 'bg-secondary-green text-black'
                            "
                        >
                            <Icon :icon="visit.icon" class="h-4 w-4" />
                        </span>
                        <div>
                            <p class="font-semibold text-black">
                                {{ visit.title }}
                            </p>
                            <p class="text-xs text-[#8d7e72]">
                                {{ visit.subtitle }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <p class="text-sm font-semibold text-black">
                            {{ visit.date }}
                        </p>
                        <button
                            type="button"
                            class="flex h-9 w-9 items-center justify-center rounded-xl bg-white text-[#7a6c62]"
                        >
                            <Icon :icon="visit.actionIcon" class="h-5 w-5" />
                        </button>
                    </div>
                </li>
            </ul>
        </div>
        <!-- Card plantes récemment consultées -->
        <div class="space-y-4 rounded-3xl bg-tertiary-purple p-5 h-fit">
            <header class="flex items-center justify-between">
                <div class="flex items-center gap-3 text-black">
                    <span
                        class="flex h-6 w-6 items-center justify-center rounded-full"
                    >
                        <Icon icon="ph:leaf-duotone" class="h-5 w-5" />
                    </span>
                    <h2 class="text-lg font-popart">Plantes consultées</h2>
                </div>
                <button
                    type="button"
                    class="flex items-center gap-1 font-popins rounded-lg bg-secondary-purple px-4 py-2 text-xs text-black"
                    @click="goToPlants"
                >
                    Voir tout
                </button>
            </header>

            <ul class="space-y-3">
                <li
                    v-for="plant in recentPlants"
                    :key="plant.id"
                    class="flex items-center justify-between rounded-2xl bg-secondary-purple px-4 py-3"
                >
                    <div class="flex items-center gap-3">
                        <span
                            class="flex h-6 w-6 items-center justify-center rounded-xl text-black"
                        >
                            <Icon :icon="plant.icon" class="h-5 w-5" />
                        </span>
                        <div>
                            <p class="font-semibold text-black">
                                {{ plant.name }}
                            </p>
                            <p class="text-xs text-[#8d7e72]">
                                Santé : {{ plant.health }}%
                            </p>
                        </div>
                    </div>
                    <!-- Jauge d'eau circulaire -->
                    <div class="flex items-center gap-3">
                        <WaterGauge :value="plant.water" :size="32" :stroke-width="8" />
                    </div>
                </li>
            </ul>
        </div>
    </section>
</template>
