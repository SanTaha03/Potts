<?php

namespace App\Console\Commands;

use App\Models\Device;
use App\Models\Org;
use App\Models\Reading;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateYearReadingsCommand extends Command
{
    protected $signature = 'potts:generate-year-readings
        {--org=shbf : Org identifier (slug, name fragment, or numeric id)}
        {--year= : Year to generate (defaults to current year)}
        {--reset : Delete existing readings of that year for org devices before generation}';

    protected $description = 'Generate one year of realistic telemetry (2 measurements/day) for org linked plants/devices.';

    private const SENSOR_SOIL = 'soil_pct';

    private const SENSOR_TEMP = 'temp_c';

    private const SENSOR_LIGHT = 'light_pct';

    public function handle(): int
    {
        $orgInput = (string) $this->option('org');
        $year = (int) ($this->option('year') ?: now()->year);

        if ($year < 2020 || $year > 2100) {
            $this->error('Invalid year. Use a value between 2020 and 2100.');

            return self::FAILURE;
        }

        $org = $this->resolveOrg($orgInput);
        if (! $org) {
            $this->error("Org not found for selector: {$orgInput}");

            return self::FAILURE;
        }

        $devices = Device::query()
            ->with(['plant.plantType'])
            ->where('org_id', $org->id)
            ->whereNotNull('plant_id')
            ->orderBy('device_id')
            ->get();

        $periodStart = Carbon::create($year, 1, 1, 0, 0, 0, 'UTC');
        $periodEnd = Carbon::create($year, 12, 31, 23, 59, 59, 'UTC');

        if ((bool) $this->option('reset') && $devices->isNotEmpty()) {
            Reading::query()
                ->whereIn('device_id', $devices->pluck('id')->all())
                ->whereBetween('measured_at', [$periodStart, $periodEnd])
                ->delete();

            $this->info('Existing readings for this year were removed (--reset).');
        }

        $totalInserted = 0;
        $totalSkippedTimestamps = 0;
        $processedDevices = 0;

        if ($devices->isEmpty()) {
            $this->warn('No linked devices found for this org. Seed plants/devices first.');
        } else {
            foreach ($devices->values() as $index => $device) {
                $plantType = $device->plant?->plantType;
                if (! $plantType) {
                    $this->warn("Skipping {$device->device_id}: missing plant_type linkage.");

                    continue;
                }

                $stats = $this->generateForDevice($device, $plantType->slug, $year, $index);
                $totalInserted += $stats['inserted'];
                $totalSkippedTimestamps += $stats['skipped_timestamps'];
                $processedDevices++;
            }
        }

        $expectedTimestamps = (int) (Carbon::create($year, 1, 1)->isLeapYear() ? 366 : 365) * 2;

        $this->newLine();
        $this->info("Org: {$org->name}");
        $this->line("Devices processed: {$processedDevices}");
        $this->line("Expected timestamps/device (without outages): {$expectedTimestamps}");
        $this->line("Skipped timestamps (outages): {$totalSkippedTimestamps}");
        $this->line("Inserted EAV rows: {$totalInserted}");

        return self::SUCCESS;
    }

    private function generateForDevice(Device $device, string $plantTypeSlug, int $year, int $deviceIndex): array
    {
        $seed = abs(crc32($device->device_id . '-' . $year));
        mt_srand($seed);

        $outages = $this->outageWindows($year, $deviceIndex);
        $watering = $this->wateringProfile($plantTypeSlug);
        $soilState = $this->clamp(
            $watering['target'] + $this->randFloat(-6, 3),
            20,
            92
        );

        $nextWateringDay = Carbon::create($year, 1, 1, 0, 0, 0, 'UTC')->copy()->addDays(mt_rand(0, $watering['interval'] - 1));

        $forgottenWateringDays = [];
        for ($month = 1; $month <= 12; $month++) {
            $forgottenWateringDays[$month] = mt_rand(2, 26);
        }

        $grayDaysRemaining = 0;
        $rows = [];
        $inserted = 0;
        $skippedTimestamps = 0;

        $lastValues = null;
        $lastTimestamp = null;

        $day = Carbon::create($year, 1, 1, 0, 0, 0, 'UTC');
        $endDay = Carbon::create($year, 12, 31, 0, 0, 0, 'UTC');

        while ($day->lte($endDay)) {
            if ($this->isOutageDay($day, $outages)) {
                $skippedTimestamps += 2;
                $day->addDay();
                continue;
            }

            $month = (int) $day->format('n');
            $isGrayDay = $this->resolveGrayDay($grayDaysRemaining);

            $soilState = $this->clamp(
                $soilState - $watering['drop_morning'] - $this->randFloat(0.1, 0.6),
                5,
                95
            );

            $soilState = $this->applyWateringCycle($day, $month, $soilState, $watering, $forgottenWateringDays, $nextWateringDay);

            $morningAt = $day->copy()->setTime(8, 0, 0);
            $morning = $this->buildSnapshot($month, $plantTypeSlug, $soilState, true, $isGrayDay);
            $rows = array_merge($rows, $this->toEavRows($device->id, $morningAt, $morning));

            $soilState = $this->clamp(
                $soilState - $watering['drop_evening'] - $this->randFloat(0.1, 0.5),
                5,
                95
            );

            $eveningAt = $day->copy()->setTime(18, 0, 0);
            $evening = $this->buildSnapshot($month, $plantTypeSlug, $soilState, false, $isGrayDay);
            $rows = array_merge($rows, $this->toEavRows($device->id, $eveningAt, $evening));
            $lastValues = $evening;
            $lastTimestamp = $eveningAt;

            if (count($rows) >= 1200) {
                Reading::query()->insert($rows);
                $inserted += count($rows);
                $rows = [];
            }

            $day->addDay();
        }

        if (! empty($rows)) {
            Reading::query()->insert($rows);
            $inserted += count($rows);
        }

        if ($lastTimestamp && $lastValues) {
            $device->update([
                'last_seen_at' => $lastTimestamp,
                'last_values' => [
                    self::SENSOR_SOIL => $lastValues[self::SENSOR_SOIL],
                    self::SENSOR_TEMP => $lastValues[self::SENSOR_TEMP],
                    self::SENSOR_LIGHT => $lastValues[self::SENSOR_LIGHT],
                    'sent_at' => $lastTimestamp->toIso8601String(),
                ],
                'status' => 'active',
            ]);
        }

        return [
            'inserted' => $inserted,
            'skipped_timestamps' => $skippedTimestamps,
        ];
    }

    private function buildSnapshot(int $month, string $plantTypeSlug, float $soilState, bool $isMorning, bool $isGrayDay): array
    {
        $temp = $this->seasonalTemperature($month, $plantTypeSlug, $isMorning);
        $light = $this->seasonalLight($month, $isMorning, $isGrayDay);

        return [
            self::SENSOR_SOIL => round($this->clamp($soilState + $this->randFloat(-1.4, 1.2), 4, 98), 2),
            self::SENSOR_TEMP => round($this->clamp($temp + $this->randFloat(-0.5, 0.5), 8, 40), 2),
            self::SENSOR_LIGHT => round($this->clamp($light + $this->randFloat(-4, 4), 2, 100), 2),
        ];
    }

    /**
     * @param  array{soil_pct: float, temp_c: float, light_pct: float}  $snapshot
     * @return array<int, array<string, mixed>>
     */
    private function toEavRows(int $deviceDbId, Carbon $at, array $snapshot): array
    {
        $createdAt = now();

        return [
            [
                'device_id' => $deviceDbId,
                'sensor_type' => self::SENSOR_SOIL,
                'value' => $snapshot[self::SENSOR_SOIL],
                'measured_at' => $at,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ],
            [
                'device_id' => $deviceDbId,
                'sensor_type' => self::SENSOR_TEMP,
                'value' => $snapshot[self::SENSOR_TEMP],
                'measured_at' => $at,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ],
            [
                'device_id' => $deviceDbId,
                'sensor_type' => self::SENSOR_LIGHT,
                'value' => $snapshot[self::SENSOR_LIGHT],
                'measured_at' => $at,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ],
        ];
    }

    private function seasonalTemperature(int $month, string $plantTypeSlug, bool $isMorning): float
    {
        $baseByMonth = [
            1 => 19.0,
            2 => 19.8,
            3 => 21.2,
            4 => 22.8,
            5 => 24.2,
            6 => 25.8,
            7 => 27.1,
            8 => 27.0,
            9 => 25.2,
            10 => 23.4,
            11 => 21.5,
            12 => 19.7,
        ];

        $typeBias = match ($plantTypeSlug) {
            'ocimum-basilicum' => 1.0,
            'strelitzia-reginae' => 0.6,
            'ficus-lyrata' => 0.3,
            default => 0.0,
        };

        $timeBias = $isMorning ? -1.2 : 0.8;

        return $baseByMonth[$month] + $typeBias + $timeBias;
    }

    private function seasonalLight(int $month, bool $isMorning, bool $isGrayDay): float
    {
        $morningByMonth = [
            1 => 43,
            2 => 48,
            3 => 56,
            4 => 64,
            5 => 72,
            6 => 80,
            7 => 82,
            8 => 78,
            9 => 67,
            10 => 58,
            11 => 48,
            12 => 42,
        ];

        $eveningByMonth = [
            1 => 20,
            2 => 24,
            3 => 32,
            4 => 40,
            5 => 48,
            6 => 56,
            7 => 60,
            8 => 55,
            9 => 45,
            10 => 36,
            11 => 27,
            12 => 22,
        ];

        $value = $isMorning ? $morningByMonth[$month] : $eveningByMonth[$month];

        if ($isGrayDay) {
            $value *= 0.62;
        }

        return $value;
    }

    private function resolveGrayDay(int &$grayDaysRemaining): bool
    {
        if ($grayDaysRemaining <= 0 && $this->randFloat(0, 1) < 0.08) {
            $grayDaysRemaining = mt_rand(1, 2);
        }

        $isGrayDay = $grayDaysRemaining > 0;
        if ($isGrayDay) {
            $grayDaysRemaining--;
        }

        return $isGrayDay;
    }

    /**
     * @param  array{interval: int, target: float, drop_morning: float, drop_evening: float}  $watering
     * @param  array<int, int>  $forgottenWateringDays
     */
    private function applyWateringCycle(Carbon $day, int $month, float $soilState, array $watering, array $forgottenWateringDays, Carbon &$nextWateringDay): float
    {
        if (! $day->gte($nextWateringDay)) {
            return $soilState;
        }

        $dayOfMonth = (int) $day->format('j');
        if ($dayOfMonth === $forgottenWateringDays[$month]) {
            $nextWateringDay = $day->copy()->addDay();

            return $soilState;
        }

        $nextWateringDay = $day->copy()->addDays($watering['interval']);

        return $this->clamp(
            $watering['target'] + $this->randFloat(-4, 4),
            10,
            96
        );
    }

    /**
     * @return array{interval: int, target: float, drop_morning: float, drop_evening: float}
     */
    private function wateringProfile(string $plantTypeSlug): array
    {
        return match ($plantTypeSlug) {
            'ocimum-basilicum' => ['interval' => 2, 'target' => 79.0, 'drop_morning' => 3.2, 'drop_evening' => 2.2],
            'strelitzia-reginae' => ['interval' => 5, 'target' => 70.0, 'drop_morning' => 2.0, 'drop_evening' => 1.4],
            'pachira-aquatica' => ['interval' => 6, 'target' => 66.0, 'drop_morning' => 1.7, 'drop_evening' => 1.2],
            'ficus-lyrata' => ['interval' => 5, 'target' => 64.0, 'drop_morning' => 1.9, 'drop_evening' => 1.4],
            default => ['interval' => 4, 'target' => 72.0, 'drop_morning' => 2.3, 'drop_evening' => 1.7],
        };
    }

    /**
     * @return array<int, array{start: Carbon, end: Carbon}>
     */
    private function outageWindows(int $year, int $deviceIndex): array
    {
        if ($deviceIndex === 0) {
            return [
                [
                    'start' => Carbon::create($year, 3, 10, 0, 0, 0, 'UTC'),
                    'end' => Carbon::create($year, 3, 12, 23, 59, 59, 'UTC'),
                ],
                [
                    'start' => Carbon::create($year, 10, 4, 0, 0, 0, 'UTC'),
                    'end' => Carbon::create($year, 10, 5, 23, 59, 59, 'UTC'),
                ],
            ];
        }

        if ($deviceIndex === 1) {
            return [
                [
                    'start' => Carbon::create($year, 7, 18, 0, 0, 0, 'UTC'),
                    'end' => Carbon::create($year, 7, 20, 23, 59, 59, 'UTC'),
                ],
            ];
        }

        return [];
    }

    /**
     * @param  array<int, array{start: Carbon, end: Carbon}>  $windows
     */
    private function isOutageDay(Carbon $day, array $windows): bool
    {
        foreach ($windows as $window) {
            if ($day->between($window['start'], $window['end'])) {
                return true;
            }
        }

        return false;
    }

    private function resolveOrg(string $selector): ?Org
    {
        if (is_numeric($selector)) {
            return Org::query()->find((int) $selector);
        }

        $normalized = trim(strtolower($selector));

        return Org::query()
            ->whereRaw('LOWER(slug) = ?', [$normalized])
            ->orWhereRaw('LOWER(name) like ?', ['%' . $normalized . '%'])
            ->first();
    }

    private function randFloat(float $min, float $max): float
    {
        return $min + (mt_rand() / mt_getrandmax()) * ($max - $min);
    }

    private function clamp(float $value, float $min, float $max): float
    {
        return max($min, min($max, $value));
    }
}
