<?php

namespace App\Http\Controllers\Api\App\Tech;

use App\Http\Controllers\Controller;
use App\Models\Mission;
use Illuminate\Http\Request;

class MissionIncidentController extends Controller
{
    public function store(Request $request, $missionId)
    {
        $mission = Mission::findOrFail($missionId);
        
        $validated = $request->validate([
            'severity' => 'required|in:low,medium,high',
            'type' => 'required',
            'description' => 'required|string',
            'device_id' => 'nullable|exists:devices,id'
        ]);

        $incident = $mission->incidents()->create([
            'created_by' => $request->user()?->id ?? 1,
            'severity' => $validated['severity'],
            'type' => $validated['type'],
            'description' => $validated['description'],
            'device_id' => $validated['device_id'] ?? null,
        ]);

        return response()->json(['data' => $incident]);
    }
}
