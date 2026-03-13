<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\Mission;
use App\Models\MissionIncident;
use App\Models\Org;
use App\Models\Reading;
use App\Services\PlantRulesService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RseReportController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'org_id' => ['nullable', 'integer', 'exists:orgs,id'],
            'device_id' => ['nullable', 'string', 'max:64'],
            'year' => ['nullable', 'integer', 'min:2020', 'max:2100'],
        ]);

        $context = $this->resolveContext($request, $validated);

        $devices = $this->loadDevices($context['org_id'], $context['device_id']);
        $deviceIds = $devices->pluck('id')->all();
        $readings = $this->loadReadings($deviceIds, $context['period_start'], $context['effective_end']);

        $expectedTimestamps = $this->expectedTimestamps($context['period_start'], $context['effective_end']);
        $deviceAnalytics = $this->computePerDeviceAnalytics($devices, $readings, $context['months'], $expectedTimestamps);
        $deviceMetrics = collect($deviceAnalytics['devices']);

        $missionStats = $this->computeMissionStats(
            orgId: $context['org_id'],
            deviceIds: $deviceIds,
            periodStart: $context['period_start'],
            periodEnd: $context['period_end'],
            months: $context['months']
        );

        $topPlants = $this->topPlants($deviceMetrics);
        $payload = $this->buildPayload([
            'context' => $context,
            'devices' => $devices,
            'readings' => $readings,
            'device_analytics' => $deviceAnalytics,
            'device_metrics' => $deviceMetrics,
            'mission_stats' => $missionStats,
            'top_plants' => $topPlants,
            'by_species' => $this->buildBySpecies($deviceMetrics, $missionStats['incidents']),
        ]);

        return response()->json($payload);
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveContext(Request $request, array $validated): array
    {
        $year = (int) ($validated['year'] ?? now()->year);
        $periodStart = Carbon::create($year, 1, 1, 0, 0, 0, 'UTC');
        $periodEnd = Carbon::create($year, 12, 31, 23, 59, 59, 'UTC');
        $hasExplicitYear = array_key_exists('year', $validated) && $validated['year'] !== null;
        $effectiveEnd = $hasExplicitYear
            ? $periodEnd->copy()
            : (now('UTC')->lt($periodEnd) ? now('UTC') : $periodEnd->copy());
        $orgId = $validated['org_id'] ?? $request->user()?->org_id;

        return [
            'org_id' => $orgId,
            'device_id' => $validated['device_id'] ?? null,
            'year' => $year,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'effective_end' => $effectiveEnd,
            'months' => $this->monthKeys($year),
        ];
    }

    private function loadDevices(?int $orgId, ?string $deviceId): Collection
    {
        $query = Device::query()->with(['plant.plantType']);

        if ($orgId) {
            $query->where('org_id', $orgId);
        }

        if ($deviceId) {
            $query->where('device_id', $deviceId);
        }

        return $query->get();
    }

    private function loadReadings(array $deviceIds, Carbon $start, Carbon $end): Collection
    {
        if (empty($deviceIds)) {
            return collect();
        }

        return Reading::query()
            ->whereIn('device_id', $deviceIds)
            ->whereIn('sensor_type', ['soil_pct', 'temp_c', 'light_pct'])
            ->whereBetween('measured_at', [$start, $end])
            ->orderBy('measured_at')
            ->get(['device_id', 'sensor_type', 'value', 'measured_at']);
    }

    /**
     * @param  array<int, string>  $months
     * @return array<string, mixed>
     */
    private function computeMissionStats(?int $orgId, array $deviceIds, Carbon $periodStart, Carbon $periodEnd, array $months): array
    {
        $missionsQuery = Mission::query()->whereBetween('scheduled_for', [$periodStart, $periodEnd]);
        if ($orgId) {
            $missionsQuery->where('org_id', $orgId);
        }
        $missions = $missionsQuery->get(['id', 'type', 'status', 'closed_at', 'scheduled_for']);

        $incidentsQuery = MissionIncident::query()
            ->with(['mission:id,org_id,status,closed_at'])
            ->whereBetween('created_at', [$periodStart, $periodEnd]);
        if ($orgId) {
            $incidentsQuery->whereHas('mission', fn($q) => $q->where('org_id', $orgId));
        }
        if (! empty($deviceIds)) {
            $incidentsQuery->where(function ($q) use ($deviceIds) {
                $q->whereIn('device_id', $deviceIds)
                    ->orWhereNull('device_id');
            });
        }
        $incidents = $incidentsQuery->get(['id', 'mission_id', 'device_id', 'severity', 'created_at']);

        $resolvedAlerts = 0;
        $resolutionHoursSum = 0.0;
        $resolutionHoursCount = 0;
        foreach ($incidents as $incident) {
            $mission = $incident->mission;
            if (! $mission || ! $mission->closed_at || ! in_array($mission->status, ['done', 'closed'], true)) {
                continue;
            }

            $resolvedAlerts++;
            $hours = Carbon::parse($incident->created_at)->floatDiffInHours(Carbon::parse($mission->closed_at));
            if ($hours >= 0) {
                $resolutionHoursSum += $hours;
                $resolutionHoursCount++;
            }
        }

        $alertsTotal = $incidents->count();
        $alertsCritical = $incidents->filter(fn($i) => in_array($i->severity, ['high', 'critical'], true))->count();
        $alertsWarning = $alertsTotal - $alertsCritical;
        $alertsResolvedPct = $this->safePercent($resolvedAlerts, $alertsTotal);
        $avgResolutionHours = $resolutionHoursCount > 0 ? ($resolutionHoursSum / $resolutionHoursCount) : 0.0;

        $alertsMonthlyMap = array_fill_keys($months, ['critical' => 0, 'warning' => 0]);
        foreach ($incidents as $incident) {
            $month = Carbon::parse($incident->created_at)->format('Y-m');
            if (! isset($alertsMonthlyMap[$month])) {
                continue;
            }

            if (in_array($incident->severity, ['high', 'critical'], true)) {
                $alertsMonthlyMap[$month]['critical']++;
                continue;
            }

            $alertsMonthlyMap[$month]['warning']++;
        }

        return [
            'missions' => $missions,
            'incidents' => $incidents,
            'alerts_total' => $alertsTotal,
            'alerts_critical' => $alertsCritical,
            'alerts_warning' => $alertsWarning,
            'alerts_resolved_pct' => $alertsResolvedPct,
            'avg_resolution_hours' => $avgResolutionHours,
            'interventions_targeted' => $incidents->pluck('mission_id')->unique()->count(),
            'alerts_monthly_map' => $alertsMonthlyMap,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildPayload(array $data): array
    {
        $context = $data['context'];
        $devices = $data['devices'];
        $readings = $data['readings'];
        $deviceAnalytics = $data['device_analytics'];
        $deviceMetrics = $data['device_metrics'];
        $missionStats = $data['mission_stats'];
        $topPlants = $data['top_plants'];
        $bySpecies = $data['by_species'];

        $healthScoreAvg = $deviceMetrics->avg('health_score_avg') ?? 0.0;
        $overallOkPct = $deviceMetrics->avg('time_in_optimal_pct') ?? 0.0;
        $uptimePct = $deviceMetrics->avg('uptime_pct') ?? 0.0;
        $co2Total = $deviceMetrics->sum('co2_est_kg_year');
        $carbonTotal = $co2Total * (12 / 44);

        $soilOkPct = $this->safePercent($deviceAnalytics['global']['soil_ok'], $deviceAnalytics['global']['sample_count']);
        $tempOkPct = $this->safePercent($deviceAnalytics['global']['temp_ok'], $deviceAnalytics['global']['sample_count']);
        $lightOkPct = $this->safePercent($deviceAnalytics['global']['light_ok'], $deviceAnalytics['global']['sample_count']);

        $healthScoreMonthly = [];
        $timeInOptimalMonthly = [];
        $alertsMonthly = [];

        foreach ($context['months'] as $month) {
            $healthScoreMonthly[] = [
                'month' => $month,
                'value' => $deviceAnalytics['monthly']['health'][$month]['count'] > 0
                    ? round($deviceAnalytics['monthly']['health'][$month]['sum'] / $deviceAnalytics['monthly']['health'][$month]['count'], 1)
                    : 0,
            ];
            $timeInOptimalMonthly[] = [
                'month' => $month,
                'value' => $deviceAnalytics['monthly']['optimal'][$month]['count'] > 0
                    ? round($deviceAnalytics['monthly']['optimal'][$month]['sum'] / $deviceAnalytics['monthly']['optimal'][$month]['count'], 1)
                    : 0,
            ];
            $alertsMonthly[] = [
                'month' => $month,
                'critical' => $missionStats['alerts_monthly_map'][$month]['critical'],
                'warning' => $missionStats['alerts_monthly_map'][$month]['warning'],
            ];
        }

        $orgName = $context['org_id']
            ? Org::query()->where('id', $context['org_id'])->value('name')
            : 'Demo';

        return [
            'scope' => [
                'org_id' => $context['org_id'],
                'org_name' => $orgName,
                'year' => $context['year'],
                'generated_at' => now()->toIso8601String(),
            ],
            'coverage' => [
                'devices_total' => $devices->count(),
                'devices_reporting' => $readings->pluck('device_id')->unique()->count(),
                'uptime_pct' => round($uptimePct, 1),
                'data_points' => $readings->count(),
            ],
            'plant_demo' => $this->buildPlantDemo($topPlants),
            'kpis' => [
                ['key' => 'health_score_avg', 'label' => 'Sante moyenne', 'value' => round($healthScoreAvg, 1), 'unit' => '/100', 'badge' => 'MESURE', 'detail' => 'Moyenne des scores par plante, puis aggregation organisation'],
                ['key' => 'time_in_optimal_pct', 'label' => 'Temps en zone optimale', 'value' => round($overallOkPct, 1), 'unit' => '%', 'badge' => 'CALCULE', 'detail' => 'Calcule par plante selon les seuils de son espèce (PlantType)'],
                ['key' => 'alerts_resolved_pct', 'label' => 'Alertes resolues', 'value' => round($missionStats['alerts_resolved_pct']), 'unit' => '%', 'badge' => 'MESURE', 'detail' => 'Base sur l\'historique alertes et missions'],
                ['key' => 'interventions_targeted', 'label' => 'Interventions ciblees', 'value' => $missionStats['interventions_targeted'], 'unit' => '', 'badge' => 'MESURE', 'detail' => 'Nombre d\'interventions generees par alertes'],
                ['key' => 'co2_est_kg_year', 'label' => 'CO2 assimile (estime)', 'value' => round($co2Total, 2), 'unit' => 'kg/an', 'badge' => 'ESTIME', 'detail' => 'Somme des contributions device: k(type) x surface(size) x facteur_conditions'],
                ['key' => 'carbone_est_kg_year', 'label' => 'Carbone equivalent (estime)', 'value' => round($carbonTotal, 2), 'unit' => 'kgC/an', 'badge' => 'ESTIME', 'detail' => 'kgC = kgCO2 x 12/44'],
            ],
            'breakdown' => [
                'optimal_conditions' => [
                    'soil_ok_pct' => round($soilOkPct),
                    'temp_ok_pct' => round($tempOkPct),
                    'light_ok_pct' => round($lightOkPct),
                    'overall_ok_pct' => round($overallOkPct),
                ],
                'alerts' => [
                    'total' => $missionStats['alerts_total'],
                    'critical' => $missionStats['alerts_critical'],
                    'warning' => $missionStats['alerts_warning'],
                    'avg_resolution_hours' => round($missionStats['avg_resolution_hours'], 1),
                ],
                'missions' => [
                    'total' => $missionStats['missions']->count(),
                    'maintenance' => $missionStats['missions']->where('type', 'maintenance')->count(),
                    'replacement' => $missionStats['missions']->where('type', 'replacement')->count(),
                    'installation' => $missionStats['missions']->where('type', 'installation')->count(),
                ],
            ],
            'charts' => [
                'health_score_monthly' => $healthScoreMonthly,
                'alerts_monthly' => $alertsMonthly,
                'time_in_optimal_monthly' => $timeInOptimalMonthly,
            ],
            'by_species' => $bySpecies,
            'top_plants' => $topPlants,
            'disclaimer' => [
                "Les indicateurs marques 'ESTIME' sont des estimations. Ils dependent des hypotheses (surface foliaire, ventilation, exposition).",
                "Les indicateurs 'MESURE' proviennent directement des capteurs et du suivi operationnel.",
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function computePerDeviceAnalytics(Collection $devices, Collection $readings, array $months, int $expectedTimestamps): array
    {
        $monthly = [
            'health' => array_fill_keys($months, ['sum' => 0.0, 'count' => 0]),
            'optimal' => array_fill_keys($months, ['sum' => 0.0, 'count' => 0]),
        ];

        $global = [
            'sample_count' => 0,
            'soil_ok' => 0,
            'temp_ok' => 0,
            'light_ok' => 0,
        ];

        $readingsByDevice = $readings->groupBy('device_id');
        $metrics = [];

        foreach ($devices as $device) {
            $metric = $this->analyzeDeviceMetrics(
                device: $device,
                deviceReadings: $readingsByDevice->get($device->id, collect()),
                expectedTimestamps: $expectedTimestamps,
                monthly: $monthly,
                global: $global
            );

            if ($metric) {
                $metrics[] = $metric;
            }
        }

        return [
            'devices' => $metrics,
            'monthly' => $monthly,
            'global' => $global,
        ];
    }

    /**
     * @param  array<string, mixed>  $monthly
     * @param  array<string, mixed>  $global
     * @return array<string, mixed>|null
     */
    private function analyzeDeviceMetrics(Device $device, Collection $deviceReadings, int $expectedTimestamps, array &$monthly, array &$global): ?array
    {
        $plant = $device->plant;
        $plantType = $plant?->plantType;
        if (! $plant || ! $plantType) {
            return null;
        }

        $samples = $this->buildTriplets($deviceReadings);
        if (empty($samples)) {
            return null;
        }

        $optimal = [
            'soil_opt_min' => (float) $plantType->soil_opt_min,
            'soil_opt_max' => (float) $plantType->soil_opt_max,
            'temp_opt_min' => (float) $plantType->temp_opt_min,
            'temp_opt_max' => (float) $plantType->temp_opt_max,
            'light_opt_min' => (float) $plantType->light_opt_min,
            'light_opt_max' => (float) $plantType->light_opt_max,
        ];

        $summary = $this->summarizeSamples($samples, $optimal, $monthly);
        $global['sample_count'] += $summary['sample_count'];
        $global['soil_ok'] += $summary['soil_ok'];
        $global['temp_ok'] += $summary['temp_ok'];
        $global['light_ok'] += $summary['light_ok'];

        $optimalPct = $this->safePercent($summary['overall_ok'], $summary['sample_count']);
        $leafArea = $this->leafAreaBySize($plantType, (string) $plant->size);

        return [
            'device_db_id' => $device->id,
            'device_id' => $device->device_id,
            'plant_name' => $plant->name,
            'location' => $plant->location ?? $device->location,
            'species_slug' => $plantType->slug,
            'species_name' => $plantType->display_name,
            'size' => strtoupper((string) $plant->size),
            'sample_count' => $summary['sample_count'],
            'uptime_pct' => $this->safePercent($summary['sample_count'], max(1, $expectedTimestamps)),
            'health_score_avg' => $summary['health_sum'] / $summary['sample_count'],
            'time_in_optimal_pct' => $optimalPct,
            'co2_est_kg_year' => (float) $plantType->co2_k_per_m2_year * $leafArea * ($optimalPct / 100),
            'leaf_area_m2' => $leafArea,
            'co2_k_per_m2_year' => (float) $plantType->co2_k_per_m2_year,
        ];
    }

    /**
     * @param  array<int, array<string, float|Carbon>>  $samples
     * @param  array<string, float>  $optimal
     * @param  array<string, mixed>  $monthly
     * @return array<string, int|float>
     */
    private function summarizeSamples(array $samples, array $optimal, array &$monthly): array
    {
        $summary = [
            'sample_count' => 0,
            'health_sum' => 0.0,
            'overall_ok' => 0,
            'soil_ok' => 0,
            'temp_ok' => 0,
            'light_ok' => 0,
        ];

        foreach ($samples as $sample) {
            $health = (float) PlantRulesService::assessHealthForPlantType($sample, $optimal)['health_score'];
            $soilOk = $this->isInRange((float) $sample['soil_pct'], $optimal['soil_opt_min'], $optimal['soil_opt_max']);
            $tempOk = $this->isInRange((float) $sample['temp_c'], $optimal['temp_opt_min'], $optimal['temp_opt_max']);
            $lightOk = $this->isInRange((float) $sample['light_pct'], $optimal['light_opt_min'], $optimal['light_opt_max']);
            $overallOk = $soilOk && $tempOk && $lightOk;

            $summary['sample_count']++;
            $summary['health_sum'] += $health;
            $summary['overall_ok'] += $overallOk ? 1 : 0;
            $summary['soil_ok'] += $soilOk ? 1 : 0;
            $summary['temp_ok'] += $tempOk ? 1 : 0;
            $summary['light_ok'] += $lightOk ? 1 : 0;

            $month = Carbon::parse($sample['measured_at'])->format('Y-m');
            $this->addMonthlySample($monthly, $month, $health, $overallOk);
        }

        return $summary;
    }

    private function isInRange(float $value, float $min, float $max): bool
    {
        return $value >= $min && $value <= $max;
    }

    private function addMonthlySample(array &$monthly, string $month, float $health, bool $overallOk): void
    {
        if (! isset($monthly['health'][$month])) {
            return;
        }

        $monthly['health'][$month]['sum'] += $health;
        $monthly['health'][$month]['count']++;
        $monthly['optimal'][$month]['sum'] += $overallOk ? 100 : 0;
        $monthly['optimal'][$month]['count']++;
    }

    /**
     * @return array<int, array<string, float|Carbon>>
     */
    private function buildTriplets(Collection $deviceReadings): array
    {
        $triplets = [];

        foreach ($deviceReadings as $reading) {
            $key = $reading->measured_at->format('Y-m-d H:i:s');
            if (! isset($triplets[$key])) {
                $triplets[$key] = [
                    'measured_at' => $reading->measured_at,
                    'soil_pct' => null,
                    'temp_c' => null,
                    'light_pct' => null,
                ];
            }
            $triplets[$key][$reading->sensor_type] = (float) $reading->value;
        }

        return collect($triplets)
            ->filter(fn($sample) => $sample['soil_pct'] !== null && $sample['temp_c'] !== null && $sample['light_pct'] !== null)
            ->values()
            ->all();
    }

    private function leafAreaBySize($plantType, string $size): float
    {
        return match (strtoupper($size)) {
            'S' => (float) $plantType->leaf_area_m2_s,
            'L' => (float) $plantType->leaf_area_m2_l,
            default => (float) $plantType->leaf_area_m2_m,
        };
    }

    private function safePercent(float|int $part, float|int $total): float
    {
        if ((float) $total <= 0) {
            return 0.0;
        }

        return ((float) $part / (float) $total) * 100;
    }

    private function expectedTimestamps(Carbon $start, Carbon $end): int
    {
        $days = $start->copy()->startOfDay()->diffInDays($end->copy()->startOfDay()) + 1;

        return max(1, $days * 2);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildBySpecies(Collection $deviceMetrics, Collection $incidents): array
    {
        $speciesByDeviceId = $deviceMetrics
            ->pluck('species_slug', 'device_db_id')
            ->toArray();

        $alertsBySpecies = [];
        foreach ($incidents as $incident) {
            $slug = $speciesByDeviceId[$incident->device_id] ?? null;
            if (! $slug) {
                continue;
            }
            $alertsBySpecies[$slug] = ($alertsBySpecies[$slug] ?? 0) + 1;
        }

        return $deviceMetrics
            ->groupBy('species_slug')
            ->map(function (Collection $group, string $slug) use ($alertsBySpecies) {
                return [
                    'slug' => $slug,
                    'name' => $group->first()['species_name'],
                    'plants' => $group->count(),
                    'time_in_optimal_pct' => round($group->avg('time_in_optimal_pct') ?? 0, 1),
                    'health_score_avg' => round($group->avg('health_score_avg') ?? 0, 1),
                    'co2_est_kg_year' => round($group->sum('co2_est_kg_year'), 2),
                    'alerts_total' => (int) ($alertsBySpecies[$slug] ?? 0),
                ];
            })
            ->sortByDesc('co2_est_kg_year')
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function topPlants(Collection $deviceMetrics): array
    {
        return $deviceMetrics
            ->sortByDesc('co2_est_kg_year')
            ->take(3)
            ->map(function (array $device) {
                return [
                    'name' => $device['plant_name'],
                    'device_id' => $device['device_id'],
                    'species' => $device['species_name'],
                    'location' => $device['location'],
                    'co2_est_kg_year' => round($device['co2_est_kg_year'], 2),
                    'time_in_optimal_pct' => round($device['time_in_optimal_pct'], 1),
                    'health_score_avg' => round($device['health_score_avg'], 1),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $topPlants
     * @return array<string, mixed>
     */
    private function buildPlantDemo(array $topPlants): array
    {
        $top = $topPlants[0] ?? null;

        if (! $top) {
            return [
                'device_id' => null,
                'plant_type' => 'N/A',
                'plant_size' => 'N/A',
                'leaf_area_m2' => 0,
                'method_note' => 'Demo indisponible: aucune plante exploitable sur la periode.',
            ];
        }

        return [
            'device_id' => $top['device_id'],
            'plant_type' => $top['species'],
            'plant_size' => 'MIX',
            'leaf_area_m2' => 0,
            'method_note' => 'Plante vitrine choisie automatiquement selon la contribution CO2 la plus elevee.',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function monthKeys(int $year): array
    {
        $months = [];
        for ($month = 1; $month <= 12; $month++) {
            $months[] = sprintf('%04d-%02d', $year, $month);
        }

        return $months;
    }
}
