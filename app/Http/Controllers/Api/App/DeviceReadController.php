<?php

namespace App\Http\Controllers\Api\App;

use App\Http\Controllers\Controller;
use App\Http\Resources\DeviceResource;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DeviceReadController extends Controller
{
    /**
     * List all devices with their live status.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $devices = Device::query()
            ->latest('last_seen_at')
            ->paginate($request->integer('per_page', 20));

        // On peut enrichir la resource ici si besoin,
        // mais DeviceResource gère déjà last_values/last_seen_at.
        return DeviceResource::collection($devices);
    }

    /**
     * Get details for a specific device.
     */
    public function show(string $deviceId): DeviceResource
    {
        // On permet la recherche par ID interne ou device_id (serial)
        $device = Device::where('id', $deviceId)
            ->orWhere('device_id', $deviceId)
            ->firstOrFail();

        return new DeviceResource($device);
    }

    /**
     * Get history for a specific sensor on a device.
     */
    public function history(Request $request, string $deviceId)
    {
        $request->validate([
            'sensor' => 'required|string|in:soil_pct,temp_c,light_pct,battery',
            'period' => 'sometimes|string|in:24h,7d,30d',
        ]);

        $device = Device::where('id', $deviceId)
            ->orWhere('device_id', $deviceId)
            ->firstOrFail();

        $sensorType = $request->input('sensor');
        $period = $request->input('period', '24h');

        $query = $device->readings()
            ->where('sensor_type', $sensorType)
            ->orderBy('measured_at', 'asc'); // Chronologique pour les graphiques

        // Filtrage par période
        $cutoff = match ($period) {
            '24h' => now()->subDay(),
            '7d' => now()->subDays(7),
            '30d' => now()->subDays(30),
            default => now()->subDay(),
        };

        $readings = $query->where('measured_at', '>=', $cutoff)
            ->select('measured_at', 'value')
            ->get()
            ->map(fn ($r) => [
                'time' => $r->measured_at->toIso8601String(),
                'val' => (float) $r->value,
            ]);

        return response()->json([
            'device_id' => $device->device_id,
            'sensor' => $sensorType,
            'period' => $period,
            'data' => $readings,
        ]);
    }
}
