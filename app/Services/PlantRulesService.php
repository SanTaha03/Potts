<?php

namespace App\Services;

class PlantRulesService
{
    /**
     * Calcule le statut de santé et les alertes pour un profil de plante donné.
     * Pour la démo, on hardcode le profil "Monstera".
     *
     * @param  array  $values  ['soil_pct', 'temp_c', 'light_pct']
     * @param  string  $profile  'monstera' (MVP)
     * @return array ['status' => 'ok|warning|alert', 'alerts' => string[], 'health_score' => int]
     */
    public static function assessHealth(array $values): array
    {
        $thresholds = self::defaultThresholds();

        return self::assessHealthWithThresholds($values, $thresholds);
    }

    /**
     * Calcule la sante d'une plante a partir de seuils optimaux dynamiques.
     *
     * @param  array  $values  ['soil_pct', 'temp_c', 'light_pct']
     * @param  array  $optimal  [
     *                          'soil_opt_min' => float,
     *                          'soil_opt_max' => float,
     *                          'temp_opt_min' => float,
     *                          'temp_opt_max' => float,
     *                          'light_opt_min' => float,
     *                          'light_opt_max' => float,
     *                          ]
     * @return array ['status' => 'ok|warning|alert', 'alerts' => string[], 'health_score' => int]
     */
    public static function assessHealthForPlantType(array $values, array $optimal): array
    {
        $thresholds = self::expandThresholdsFromOptimal($optimal);

        return self::assessHealthWithThresholds($values, $thresholds);
    }

    /**
     * @param  array  $values
     * @param  array  $thresholds
     * @return array ['status' => 'ok|warning|alert', 'alerts' => string[], 'health_score' => int]
     */
    public static function assessHealthWithThresholds(array $values, array $thresholds): array
    {
        $alerts = [];
        $score = 100;
        $levelRank = 0;

        $soilResult = self::evaluateRange(
            value: $values['soil_pct'] ?? null,
            thresholds: $thresholds['soil_pct'],
            messages: [
                'critical_low' => 'Sol trop sec : arrosage necessaire',
                'warning_low' => 'Sol un peu sec',
                'warning_high' => 'Sol un peu humide',
                'critical_high' => 'Sol trop humide : risque de pourriture',
            ],
            penalties: [
                'critical' => 40,
                'warning' => 15,
            ],
            criticalIsAlert: true
        );

        $tempValue = $values['temp_c'] ?? null;
        $tempResult = self::evaluateRange(
            value: $tempValue,
            thresholds: $thresholds['temp_c'],
            messages: [
                'critical_low' => "Temperature trop basse ({$tempValue}C)",
                'warning_low' => "Il fait un peu froid ({$tempValue}C)",
                'warning_high' => "Il fait un peu chaud ({$tempValue}C)",
                'critical_high' => "Temperature trop elevee ({$tempValue}C)",
            ],
            penalties: [
                'critical' => 30,
                'warning' => 10,
            ],
            criticalIsAlert: true
        );

        $lightResult = self::evaluateRange(
            value: $values['light_pct'] ?? null,
            thresholds: $thresholds['light_pct'],
            messages: [
                'critical_low' => 'Luminosite insuffisante',
                'warning_low' => null,
                'warning_high' => null,
                'critical_high' => 'Luminosite excessive (brulure)',
            ],
            penalties: [
                'critical' => 20,
                'warning' => 5,
            ],
            criticalIsAlert: false
        );

        foreach ([$soilResult, $tempResult, $lightResult] as $result) {
            if ($result['message']) {
                $alerts[] = $result['message'];
            }
            $score -= $result['penalty'];
            $levelRank = max($levelRank, $result['level_rank']);
        }

        $status = self::statusFromRank($levelRank);

        return [
            'status' => $status,
            'alerts' => $alerts,
            'health_score' => max(0, $score),
        ];
    }

    /**
     * @param  array<string, float|int>  $thresholds
     * @param  array<string, string|null>  $messages
     * @param  array<string, int>  $penalties
     * @return array{message: string|null, penalty: int, level_rank: int}
     */
    private static function evaluateRange(?float $value, array $thresholds, array $messages, array $penalties, bool $criticalIsAlert): array
    {
        $result = ['message' => null, 'penalty' => 0, 'level_rank' => 0];

        if ($value === null) {
            return $result;
        }

        if ($value < $thresholds['critical_low']) {
            $result = [
                'message' => $messages['critical_low'],
                'penalty' => $penalties['critical'],
                'level_rank' => $criticalIsAlert ? 2 : 1,
            ];
        } elseif ($value < $thresholds['warning_low']) {
            $result = [
                'message' => $messages['warning_low'],
                'penalty' => $penalties['warning'],
                'level_rank' => 1,
            ];
        } elseif ($value > $thresholds['critical_high']) {
            $result = [
                'message' => $messages['critical_high'],
                'penalty' => $penalties['critical'],
                'level_rank' => $criticalIsAlert ? 2 : 1,
            ];
        } elseif ($value > $thresholds['warning_high']) {
            $result = [
                'message' => $messages['warning_high'],
                'penalty' => $penalties['warning'],
                'level_rank' => 1,
            ];
        }

        return $result;
    }

    private static function statusFromRank(int $rank): string
    {
        if ($rank >= 2) {
            return 'alert';
        }

        if ($rank === 1) {
            return 'warning';
        }

        return 'ok';
    }

    private static function defaultThresholds(): array
    {
        return [
            'soil_pct' => [
                'critical_low' => 25,
                'warning_low' => 35,
                'warning_high' => 75,
                'critical_high' => 85,
            ],
            'temp_c' => [
                'critical_low' => 14,
                'warning_low' => 18,
                'warning_high' => 28,
                'critical_high' => 32,
            ],
            'light_pct' => [
                'critical_low' => 10,
                'warning_low' => 25,
                'warning_high' => 85,
                'critical_high' => 95,
            ],
        ];
    }

    private static function expandThresholdsFromOptimal(array $optimal): array
    {
        $soilMin = (float) ($optimal['soil_opt_min'] ?? 35);
        $soilMax = (float) ($optimal['soil_opt_max'] ?? 75);
        $tempMin = (float) ($optimal['temp_opt_min'] ?? 18);
        $tempMax = (float) ($optimal['temp_opt_max'] ?? 28);
        $lightMin = (float) ($optimal['light_opt_min'] ?? 25);
        $lightMax = (float) ($optimal['light_opt_max'] ?? 85);

        return [
            'soil_pct' => [
                'critical_low' => max(0, $soilMin - 10),
                'warning_low' => $soilMin,
                'warning_high' => $soilMax,
                'critical_high' => min(100, $soilMax + 10),
            ],
            'temp_c' => [
                'critical_low' => $tempMin - 4,
                'warning_low' => $tempMin,
                'warning_high' => $tempMax,
                'critical_high' => $tempMax + 4,
            ],
            'light_pct' => [
                'critical_low' => max(0, $lightMin - 15),
                'warning_low' => $lightMin,
                'warning_high' => $lightMax,
                'critical_high' => min(100, $lightMax + 15),
            ],
        ];
    }
}
