<?php

namespace App\Services;

class PlantRulesService
{
    /**
     * Calcule le statut de santé et les alertes pour un profil de plante donné.
     * Pour la démo, on hardcode le profil "Monstera".
     *
     * @param array $values ['soil_pct', 'temp_c', 'light_pct']
     * @param string $profile 'monstera' (MVP)
     * @return array ['status' => 'ok|warning|alert', 'alerts' => string[], 'health_score' => int]
     */
    public static function assessHealth(array $values, string $profile = 'monstera'): array
    {
        // Seuils MVP Démo (Monstera)
        $thresholds = [
            'soil_pct' => [
                'critical_low' => 25, 'warning_low' => 35,
                'warning_high' => 75, 'critical_high' => 85
            ],
            'temp_c' => [
                'critical_low' => 14, 'warning_low' => 18,
                'warning_high' => 28, 'critical_high' => 32
            ],
            'light_pct' => [
                'critical_low' => 10, 'warning_low' => 25,
                'warning_high' => 85, 'critical_high' => 95
            ]
        ];

        $alerts = [];
        $status = 'ok';
        $score = 100;

        // 1. Analyse Humidité
        $soil = $values['soil_pct'] ?? null;
        if ($soil !== null) {
            if ($soil < $thresholds['soil_pct']['critical_low']) {
                $alerts[] = "Sol trop sec : arrosage nécessaire";
                $status = 'alert';
                $score -= 40;
            } elseif ($soil < $thresholds['soil_pct']['warning_low']) {
                $alerts[] = "Sol un peu sec";
                if ($status !== 'alert') $status = 'warning';
                $score -= 15;
            } elseif ($soil > $thresholds['soil_pct']['critical_high']) {
                $alerts[] = "Sol trop humide : risque de pourriture";
                $status = 'alert';
                $score -= 40;
            } elseif ($soil > $thresholds['soil_pct']['warning_high']) {
                $alerts[] = "Sol un peu humide";
                if ($status !== 'alert') $status = 'warning';
                $score -= 15;
            }
        }

        // 2. Analyse Température (si non-alert déjà critique)
        $temp = $values['temp_c'] ?? null;
        if ($temp !== null) {
            if ($temp < $thresholds['temp_c']['critical_low']) {
                $alerts[] = "Température trop basse ({$temp}°C)";
                $status = 'alert';
                $score -= 30;
            } elseif ($temp < $thresholds['temp_c']['warning_low']) {
                $alerts[] = "Il fait un peu froid ({$temp}°C)";
                if ($status !== 'alert') $status = 'warning';
                $score -= 10;
            } elseif ($temp > $thresholds['temp_c']['critical_high']) {
                $alerts[] = "Température trop élevée ({$temp}°C)";
                $status = 'alert';
                $score -= 30;
            } elseif ($temp > $thresholds['temp_c']['warning_high']) {
                $alerts[] = "Il fait un peu chaud ({$temp}°C)";
                if ($status !== 'alert') $status = 'warning';
                $score -= 10;
            }
        }

        // 3. Analyse Lumière
        $light = $values['light_pct'] ?? null;
        if ($light !== null) {
            if ($light < $thresholds['light_pct']['critical_low']) {
                $alerts[] = "Luminosité insuffisante";
                if ($status !== 'alert') $status = 'warning'; // Lumière souvent moins critique pour la mort immédiate
                $score -= 20;
            } elseif ($light < $thresholds['light_pct']['warning_low']) {
                 // Warning silencieux ou message informatif
                 // $alerts[] = "Manque un peu de lumière";
                 $score -= 5;
                 if ($status === 'ok') $status = 'warning';
            } elseif ($light > $thresholds['light_pct']['critical_high']) {
                $alerts[] = "Luminosité excessive (brûlure)";
                if ($status !== 'alert') $status = 'warning';
                $score -= 20;
            }
        }

        return [
            'status' => $status,
            'alerts' => $alerts,
            'health_score' => max(0, $score)
        ];
    }
}
