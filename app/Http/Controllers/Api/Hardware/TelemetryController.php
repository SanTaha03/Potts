<?php

namespace App\Http\Controllers\Api\Hardware;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TelemetryController extends Controller
{
    /**
     * Ingestion point for Device Telemetry.
     */
    public function store(Request $request): JsonResponse
    {
        // 1) Validation du body (contrat MVP)
        $validated = $request->validate([
            'device_id' => ['required', 'string', 'max:64'],
            'soil_pct' => ['required', 'integer', 'min:0', 'max:100'],
            'temp_c' => ['required', 'numeric', 'min:-40', 'max:125'],
            'light_pct' => ['required', 'integer', 'min:0', 'max:100'],
            'battery' => ['nullable', 'numeric'],
            'sent_at' => ['nullable', 'date'],
        ]);

        // 2) Auth machine via header
        $token = $request->header('X-DEVICE-TOKEN');
        if (! $token) {
            return response()->json(['message' => 'Missing X-DEVICE-TOKEN'], 401);
        }

        // 3) Vérifier device + token
        $device = Device::where('device_id', $validated['device_id'])->first();

        if (! $device) {
            return response()->json(['message' => 'Unknown device'], 404);
        }

        if (! hash_equals((string) $device->token, (string) $token)) {
            return response()->json(['message' => 'Invalid token'], 403);
        }

        // 4) Transaction atomique: Update Device + Insert Readings
        // On utilise sent_at du message, ou now() si absent
        $measuredAt = $validated['sent_at'] ?? now();

        // Calcul Santé & Alertes (Monstera Profile)
        $analysis = \App\Services\PlantRulesService::assessHealth([
            'soil_pct' => (int) $validated['soil_pct'],
            'temp_c' => (float) $validated['temp_c'],
            'light_pct' => (int) $validated['light_pct'],
        ]);

        \Illuminate\Support\Facades\DB::transaction(function () use ($device, $validated, $measuredAt, $analysis) {

            // A. Update Device "Live State"
            // -----------------------------
            $lastValues = [
                'soil_pct' => (int) $validated['soil_pct'],
                'temp_c' => (float) $validated['temp_c'],
                'light_pct' => (int) $validated['light_pct'],
                'sent_at' => $measuredAt,
            ];

            // Préparation Meta data
            $meta = $device->meta ?? [];

            if (isset($validated['battery'])) {
                $meta['battery_level'] = $validated['battery'];
                $lastValues['battery'] = (float) $validated['battery'];
            }

            // Injection des résultats d'analyse
            $meta['alerts'] = $analysis['alerts'];
            $meta['profile'] = 'monstera';
            $meta['health_score'] = $analysis['health_score'];

            $updateData = [
                'last_seen_at' => now(),
                'last_values' => $lastValues,
                'status' => $analysis['status'], // Mise à jour dynamique du statut
                'meta' => $meta,
            ];

            $device->update($updateData);

            // B. Historique EAV (Readings)
            // -----------------------------
            // On prépare les entrées pour les 3 capteurs principaux
            $readings = [
                [
                    'sensor_type' => 'soil_pct',
                    'value' => $validated['soil_pct'],
                    'measured_at' => $measuredAt,
                ],
                [
                    'sensor_type' => 'temp_c',
                    'value' => $validated['temp_c'],
                    'measured_at' => $measuredAt,
                ],
                [
                    'sensor_type' => 'light_pct',
                    'value' => $validated['light_pct'],
                    'measured_at' => $measuredAt,
                ],
            ];

            // Si on voulait historiser la batterie comme une mesure:
            // if (isset($validated['battery'])) {
            //    $readings[] = ['sensor_type' => 'battery', 'value' => $validated['battery'], 'measured_at' => $measuredAt];
            // }

            // createMany gère automatiquement le device_id via la relation
            $device->readings()->createMany($readings);
        });

        return response()->json([
            'ok' => true,
            'device_id' => $device->device_id,
            'status' => 'OK',
            'server_time' => now()->toIso8601String(),
        ]);
    }
}
