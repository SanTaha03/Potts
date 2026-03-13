<script setup lang="ts">
import { computed, onMounted, ref } from "vue";
import { Icon } from "@iconify/vue";
import { getRseReport } from "@/services/rseService";
import { resolveHttpErrorMessage } from "@/services/http";
import type { RseBadge, RseKpi, RseReportResponse } from "@/types/rse";

const loading = ref(false);
const error = ref<string | null>(null);
const selectedYear = ref(new Date().getFullYear());
const report = ref<RseReportResponse | null>(null);

const kpiOrder = [
    "health_score_avg",
    "time_in_optimal_pct",
    "alerts_resolved_pct",
    "interventions_targeted",
    "co2_est_kg_year",
    "carbone_est_kg_year",
];

const yearOptions = computed(() => {
    const now = new Date().getFullYear();
    return [now - 2, now - 1, now];
});

const orderedKpis = computed<RseKpi[]>(() => {
    if (!report.value) {
        return [];
    }

    const byKey = new Map(report.value.kpis.map((kpi) => [kpi.key, kpi]));
    return kpiOrder
        .map((key) => byKey.get(key))
        .filter((kpi): kpi is RseKpi => Boolean(kpi));
});

const subtitle = computed(() => {
    if (!report.value) {
        return "Chargement du perimetre...";
    }

    const org = report.value.scope.org_name ?? "Organisation";
    const plants = report.value.coverage.devices_total;
    return `${org} · ${plants} plantes connectees`;
});
// Séries de données pour les graphiques
const healthMonthly = computed(
    () => report.value?.charts.health_score_monthly ?? [],
);
// Série d'alertes warning et critical par mois
const alertsMonthly = computed(() => report.value?.charts.alerts_monthly ?? []);
// Série du temps en zone optimale par mois
const optimalMonthly = computed(
    () => report.value?.charts.time_in_optimal_monthly ?? [],
);
// Répartition par espèce et top plantes
const bySpecies = computed(() => report.value?.by_species ?? []);
const topPlants = computed(() => report.value?.top_plants ?? []);

const chartWidth = 320;
const chartHeight = 120;
const chartPaddingX = 14;
const chartPaddingY = 12;
// Calcul des maxima pour les graphiques afin de les mettre à l'échelle
const maxAlertsMonthly = computed(() => {
    const max = Math.max(
        ...alertsMonthly.value.flatMap((point) => [
            point.warning,
            point.critical,
        ]),
        0,
    );
    return max > 0 ? max : 1;
});
// Indique s'il n'y a eu aucune alerte sur la période, pour afficher un message adapté
const noAlertsOnPeriod = computed(() => {
    return alertsMonthly.value.every(
        (point) => point.warning === 0 && point.critical === 0,
    );
});
// Pour les graphiques à barres, on peut aussi calculer un maximum spécifique pour chaque KPI afin de mieux répartir les barres, surtout si une espèce est très dominante
const maxSpeciesCo2 = computed(() => {
    const max = Math.max(
        ...bySpecies.value.map((item) => item.co2_est_kg_year),
        0,
    );
    return max > 0 ? max : 1;
});
// Pour le temps en zone optimale, comme c'est un pourcentage, on peut aussi choisir de mettre 100% comme maximum pour que les barres soient proportionnelles à l'objectif
const maxSpeciesOptimal = computed(() => {
    const max = Math.max(
        ...bySpecies.value.map((item) => item.time_in_optimal_pct),
        0,
    );
    return max > 0 ? max : 1;
});
// Fonctions utilitaires pour les graphiques et l'affichage
function monthLabel(month: string): string {
    return month.slice(5);
}

function toBarHeight(value: number, max = 100): string {
    const pct = max > 0 ? (value / max) * 100 : 0;
    const bounded = Math.max(4, Math.min(100, pct));
    return `${bounded}%`;
}

function toLinePoints(values: number[], max = 100): string {
    if (!values.length) {
        return "";
    }

    const usableWidth = chartWidth - chartPaddingX * 2;
    const usableHeight = chartHeight - chartPaddingY * 2;
    const stepX = values.length > 1 ? usableWidth / (values.length - 1) : 0;

    return values
        .map((value, index) => {
            const x = chartPaddingX + stepX * index;
            const ratio = max > 0 ? Math.max(0, Math.min(1, value / max)) : 0;
            const y = chartHeight - chartPaddingY - ratio * usableHeight;
            return `${x},${y}`;
        })
        .join(" ");
}

function toLinePath(values: number[], max = 100): string {
    const points = toLinePoints(values, max);
    return points ? `M ${points.replace(/ /g, " L ")}` : "";
}

function toSeries(values: Array<{ month: string; value: number }>): number[] {
    return values.map((point) => point.value);
}

function toAlertSeries(
    values: Array<{ month: string; warning: number; critical: number }>,
    key: "warning" | "critical",
): number[] {
    return values.map((point) => point[key]);
}

function badgeClass(badge: RseBadge): string {
    if (badge === "MESURE") {
        return "bg-emerald-100 text-emerald-800";
    }
    if (badge === "CALCULE") {
        return "bg-sky-100 text-sky-800";
    }
    return "bg-amber-100 text-amber-800";
}

function formatValue(kpi: RseKpi): string {
    const value = Number.isInteger(kpi.value)
        ? kpi.value.toString()
        : kpi.value.toFixed(2).replace(".", ",");
    const unit = kpi.unit ? ` ${kpi.unit}` : "";
    return `${value}${unit}`;
}

function kpiSubline(kpi: RseKpi): string {
    if (!report.value) {
        return kpi.detail;
    }

    if (kpi.key === "time_in_optimal_pct") {
        const b = report.value.breakdown.optimal_conditions;
        return `Sol ${b.soil_ok_pct}% · Temp ${b.temp_ok_pct}% · Lumiere ${b.light_ok_pct}%`;
    }

    if (kpi.key === "alerts_resolved_pct") {
        return `Délai moyen ${report.value.breakdown.alerts.avg_resolution_hours}h`;
    }

    if (kpi.key === "interventions_targeted") {
        return "Réduction des passages inutiles";
    }

    if (kpi.key === "co2_est_kg_year") {
        const p = report.value.plant_demo;
        const factor = (
            report.value.breakdown.optimal_conditions.overall_ok_pct / 100
        )
            .toFixed(2)
            .replace(".", ",");
        return `${p.plant_type} (taille ${p.plant_size}) · F = ${factor}`;
    }

    if (kpi.key === "carbone_est_kg_year") {
        return "Conversion CO2 -> C (12/44)";
    }

    return kpi.detail;
}

function locationLabel(
    location: { [key: string]: unknown } | null | undefined,
): string {
    if (!location) {
        return "Emplacement non renseigné";
    }

    const building =
        typeof location.building === "string" ? location.building : "";
    const floor = typeof location.floor === "string" ? location.floor : "";
    const zone = typeof location.zone === "string" ? location.zone : "";
    const parts = [building, floor, zone].filter(Boolean);

    return parts.length ? parts.join(" · ") : "Emplacement non renseigné";
}

async function fetchReport(): Promise<void> {
    loading.value = true;
    error.value = null;
    try {
        report.value = await getRseReport({ year: selectedYear.value });
    } catch (err) {
        error.value = resolveHttpErrorMessage(
            err,
            "Impossible de charger le rapport RSE.",
        );
    } finally {
        loading.value = false;
    }
}

function exportPdf(): void {
    globalThis.print();
}

onMounted(() => {
    fetchReport();
});
</script>

<template>
    <section class="space-y-6 pb-24">
        <header class="p-5 text-black border-b border-slate-200">
            <div
                class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"
            >
                <div>
                    <h1 class="font-popart text-3xl">
                        Rapport RSE - {{ selectedYear }}
                    </h1>
                    <p class="mt-1 text-base text-slate-500">{{ subtitle }}</p>
                </div>

                <div class="w-full flex justify-between">
                    <label
                        class="flex items-center gap-2 rounded-xl bg-white border border-gray-300 px-3 py-2 text-xs font-semibold text-slate-700"
                    >
                        <Icon icon="ph:calendar" class="h-4 w-4" />
                        <span>Annee</span>
                        <select
                            v-model="selectedYear"
                            class="bg-transparent outline-none"
                            @change="fetchReport"
                        >
                            <option
                                v-for="year in yearOptions"
                                :key="year"
                                :value="year"
                            >
                                {{ year }}
                            </option>
                        </select>
                    </label>
                    <button
                        type="button"
                        class="rounded-xl bg-primary-green px-3 py-2 text-xs font-semibold backdrop-blur transition hover:bg-primary-green/40"
                        @click="exportPdf"
                    >
                        <Icon icon="ph:file-pdf" class="mr-1 inline h-4 w-4" />
                        Exporter PDF
                    </button>
                </div>
            </div>
        </header>

        <article
            class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200"
        >
            <h2 class="font-popart text-lg text-slate-900">Couverture</h2>
            <div class="mt-3 grid gap-3 sm:grid-cols-3">
                <div class="rounded-2xl bg-slate-50 p-3">
                    <p
                        class="text-xs font-semibold uppercase tracking-wide text-slate-500"
                    >
                        Uptime des mesures *
                    </p>
                    <p class="mt-1 text-2xl font-bold text-slate-900">
                        {{ report?.coverage.uptime_pct ?? 0 }}%
                    </p>
                </div>
                <div class="rounded-2xl bg-slate-50 p-3">
                    <p
                        class="text-xs font-semibold uppercase tracking-wide text-slate-500"
                    >
                        Points de donnees
                    </p>
                    <p class="mt-1 text-2xl font-bold text-slate-900">
                        {{ report?.coverage.data_points ?? 0 }}
                    </p>
                </div>
                <div class="rounded-2xl bg-slate-50 p-3">
                    <p
                        class="text-xs font-semibold uppercase tracking-wide text-slate-500"
                    >
                        Équipements reportants
                    </p>
                    <p class="mt-1 text-2xl font-bold text-slate-900">
                        {{ report?.coverage.devices_reporting ?? 0 }}/{{
                            report?.coverage.devices_total ?? 0
                        }}
                    </p>
                </div>
                <p class="text-xs text-slate-500">
                    *Uptime des mesures : pourcentage du temps où les capteurs
                    ont fourni des données sur la période, indicateur de la
                    fiabilite des mesures et de la couverture du rapport.
                </p>
            </div>
        </article>

        <div
            v-if="loading"
            class="rounded-2xl bg-white p-6 text-center text-sm text-slate-500 shadow-sm ring-1 ring-slate-200"
        >
            Chargement du rapport RSE...
        </div>

        <div
            v-else-if="error"
            class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
        >
            {{ error }}
        </div>

        <div v-else class="grid gap-4 sm:grid-cols-2">
            <article
                v-for="kpi in orderedKpis"
                :key="kpi.key"
                class="rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200"
            >
                <div class="flex items-start justify-between gap-3">
                    <h3 class="font-popart text-lg text-slate-900">
                        {{ kpi.label }}
                    </h3>
                    <span
                        :class="badgeClass(kpi.badge)"
                        class="rounded-full px-2 py-1 text-[10px] font-bold uppercase tracking-wide"
                    >
                        {{ kpi.badge }}
                    </span>
                </div>
                <p class="mt-3 text-3xl font-bold text-slate-900">
                    {{ formatValue(kpi) }}
                </p>
                <p class="mt-2 text-xs leading-relaxed text-slate-600">
                    {{ kpiSubline(kpi) }}
                </p>
            </article>
        </div>

        <section
            v-if="!loading && !error && bySpecies.length"
            class="space-y-4 rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200"
        >
            <h2 class="font-popart text-lg text-slate-900">Multi-espèces</h2>

            <div class="grid gap-4 lg:grid-cols-3">
                <article class="rounded-2xl bg-slate-50 p-4 lg:col-span-1">
                    <h3 class="text-sm font-semibold text-slate-700">
                        Répartition des plantes
                    </h3>
                    <ul class="mt-3 space-y-2 text-xs text-slate-600">
                        <li
                            v-for="species in bySpecies"
                            :key="`split-${species.slug}`"
                            class="flex items-center justify-between rounded-lg bg-white px-3 py-2"
                        >
                            <span>{{ species.name }}</span>
                            <strong>{{ species.plants }}</strong>
                        </li>
                    </ul>
                </article>

                <article class="rounded-2xl bg-slate-50 p-4 lg:col-span-1">
                    <h3 class="text-sm font-semibold text-slate-700">
                        CO2 estimé par espèce
                    </h3>
                    <div class="mt-3 space-y-2">
                        <div
                            v-for="species in bySpecies"
                            :key="`co2-${species.slug}`"
                            class="grid grid-cols-[80px_1fr_44px] items-center gap-2"
                        >
                            <span class="text-[10px] text-slate-500">{{
                                species.name.split(" ")[0]
                            }}</span>
                            <div
                                class="h-2 rounded bg-emerald-500"
                                :style="{
                                    width: toBarHeight(
                                        species.co2_est_kg_year,
                                        maxSpeciesCo2,
                                    ),
                                }"
                            ></div>
                            <span
                                class="text-[10px] text-right text-slate-600"
                                >{{
                                    species.co2_est_kg_year
                                        .toFixed(1)
                                        .replace(".", ",")
                                }}</span
                            >
                        </div>
                    </div>
                </article>

                <article class="rounded-2xl bg-slate-50 p-4 lg:col-span-1">
                    <h3 class="text-sm font-semibold text-slate-700">
                        Zone optimale par espèce
                    </h3>
                    <div class="mt-3 space-y-2">
                        <div
                            v-for="species in bySpecies"
                            :key="`optimal-species-${species.slug}`"
                            class="grid grid-cols-[80px_1fr_44px] items-center gap-2"
                        >
                            <span class="text-[10px] text-slate-500">{{
                                species.name.split(" ")[0]
                            }}</span>
                            <div
                                class="h-2 rounded bg-sky-500"
                                :style="{
                                    width: toBarHeight(
                                        species.time_in_optimal_pct,
                                        maxSpeciesOptimal,
                                    ),
                                }"
                            ></div>
                            <span class="text-[10px] text-right text-slate-600"
                                >{{
                                    species.time_in_optimal_pct.toFixed(0)
                                }}%</span
                            >
                        </div>
                    </div>
                </article>
            </div>
        </section>

        <section
            v-if="!loading && !error && topPlants.length"
            class="space-y-3 rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200"
        >
            <h2 class="font-popart text-lg text-slate-900">
                Top 3 plantes contributrices
            </h2>
            <div class="space-y-2">
                <article
                    v-for="(plant, idx) in topPlants"
                    :key="`top-plant-${plant.device_id}`"
                    class="grid grid-cols-[32px_1fr_auto] items-center gap-3 rounded-xl bg-slate-50 p-3"
                >
                    <span
                        class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-800"
                        >{{ idx + 1 }}</span
                    >
                    <div>
                        <p class="text-sm font-semibold text-slate-800">
                            {{ plant.name }}
                        </p>
                        <p class="text-xs text-slate-500">
                            {{ plant.species }} ·
                            {{ locationLabel(plant.location) }}
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-bold text-slate-900">
                            {{
                                plant.co2_est_kg_year
                                    .toFixed(2)
                                    .replace(".", ",")
                            }}
                            kg/an
                        </p>
                        <p class="text-[11px] text-slate-500">
                            {{ plant.time_in_optimal_pct.toFixed(0) }}% optimal
                        </p>
                    </div>
                </article>
            </div>
        </section>

        <section
            v-if="!loading && !error"
            class="space-y-4 rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200"
        >
            <h2 class="font-popart text-lg text-slate-900">
                Tendances mensuelles
            </h2>

            <div class="grid gap-5 lg:grid-cols-3">
                <article class="rounded-2xl bg-slate-50 p-4">
                    <h3 class="text-sm font-semibold text-slate-700">
                        Santé moyenne par mois
                    </h3>
                    <div class="mt-4 rounded-xl bg-white p-2">
                        <svg
                            class="h-32 w-full"
                            :viewBox="`0 0 ${chartWidth} ${chartHeight}`"
                            preserveAspectRatio="none"
                            aria-label="Courbe santé moyenne"
                        >
                            <line
                                :x1="chartPaddingX"
                                :x2="chartWidth - chartPaddingX"
                                :y1="chartHeight - chartPaddingY"
                                :y2="chartHeight - chartPaddingY"
                                stroke="#CBD5E1"
                                stroke-width="1"
                            />
                            <path
                                :d="toLinePath(toSeries(healthMonthly), 100)"
                                fill="none"
                                stroke="#10B981"
                                stroke-width="2.5"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                        <div
                            class="mt-1 grid grid-cols-12 text-[10px] text-slate-500"
                        >
                            <span
                                v-for="point in healthMonthly"
                                :key="`health-axis-${point.month}`"
                                class="text-center"
                            >
                                {{ monthLabel(point.month) }}
                            </span>
                        </div>
                    </div>
                </article>

                <article class="rounded-2xl bg-slate-50 p-4">
                    <h3 class="text-sm font-semibold text-slate-700">
                        Alertes (warning/critical) par mois
                    </h3>
                    <p
                        v-if="noAlertsOnPeriod"
                        class="mt-3 rounded-lg bg-white px-3 py-2 text-xs text-slate-600"
                    >
                        Aucune alerte enregistrée sur la période sélectionnée.
                        Les KPI "Alertes résolues" et "Interventions ciblées"
                        restent donc à 0, ce qui est normal.
                    </p>
                    <div v-else class="mt-4 rounded-xl bg-white p-2">
                        <svg
                            class="h-32 w-full"
                            :viewBox="`0 0 ${chartWidth} ${chartHeight}`"
                            preserveAspectRatio="none"
                            aria-label="Courbes alertes"
                        >
                            <line
                                :x1="chartPaddingX"
                                :x2="chartWidth - chartPaddingX"
                                :y1="chartHeight - chartPaddingY"
                                :y2="chartHeight - chartPaddingY"
                                stroke="#CBD5E1"
                                stroke-width="1"
                            />
                            <path
                                :d="
                                    toLinePath(
                                        toAlertSeries(alertsMonthly, 'warning'),
                                        maxAlertsMonthly,
                                    )
                                "
                                fill="none"
                                stroke="#F59E0B"
                                stroke-width="2.5"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                            <path
                                :d="
                                    toLinePath(
                                        toAlertSeries(
                                            alertsMonthly,
                                            'critical',
                                        ),
                                        maxAlertsMonthly,
                                    )
                                "
                                fill="none"
                                stroke="#F43F5E"
                                stroke-width="2.5"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                        <div
                            class="mt-1 grid grid-cols-12 text-[10px] text-slate-500"
                        >
                            <span
                                v-for="point in alertsMonthly"
                                :key="`alerts-axis-${point.month}`"
                                class="text-center"
                            >
                                {{ monthLabel(point.month) }}
                            </span>
                        </div>
                        <div
                            class="mt-2 flex items-center gap-4 text-[10px] text-slate-600"
                        >
                            <span class="inline-flex items-center gap-1">
                                <span
                                    class="h-2 w-2 rounded-full bg-amber-500"
                                ></span>
                                warning
                            </span>
                            <span class="inline-flex items-center gap-1">
                                <span
                                    class="h-2 w-2 rounded-full bg-rose-500"
                                ></span>
                                critical
                            </span>
                        </div>
                    </div>
                </article>

                <article class="rounded-2xl bg-slate-50 p-4">
                    <h3 class="text-sm font-semibold text-slate-700">
                        Temps en zone optimale par mois
                    </h3>
                    <div class="mt-4 rounded-xl bg-white p-2">
                        <svg
                            class="h-32 w-full"
                            :viewBox="`0 0 ${chartWidth} ${chartHeight}`"
                            preserveAspectRatio="none"
                            aria-label="Courbe zone optimale"
                        >
                            <line
                                :x1="chartPaddingX"
                                :x2="chartWidth - chartPaddingX"
                                :y1="chartHeight - chartPaddingY"
                                :y2="chartHeight - chartPaddingY"
                                stroke="#CBD5E1"
                                stroke-width="1"
                            />
                            <path
                                :d="toLinePath(toSeries(optimalMonthly), 100)"
                                fill="none"
                                stroke="#0EA5E9"
                                stroke-width="2.5"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                        <div
                            class="mt-1 grid grid-cols-12 text-[10px] text-slate-500"
                        >
                            <span
                                v-for="point in optimalMonthly"
                                :key="`optimal-axis-${point.month}`"
                                class="text-center"
                            >
                                {{ monthLabel(point.month) }}
                            </span>
                        </div>
                    </div>
                </article>
            </div>
        </section>

        <footer
            v-if="report"
            class="space-y-3 rounded-3xl bg-dark-green p-5 text-slate-100 shadow-lg"
        >
            <h2 class="font-popart text-lg">Méthode et limites</h2>
            <p class="text-sm text-slate-200">
                Ce rapport combine des mesures capteurs, des calculs à partir
                des seuils Monstera et des estimations de capture carbone basées
                sur la surface foliaire et le temps en conditions optimales.
            </p>
            <ul class="space-y-1 text-xs text-slate-300">
                <li v-for="line in report.disclaimer" :key="line">
                    - {{ line }}
                </li>
            </ul>
        </footer>
    </section>
</template>
