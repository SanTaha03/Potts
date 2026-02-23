<?php

namespace App\Http\Controllers\Api\App\Tech;

use App\Http\Controllers\Controller;
use App\Models\Mission;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MissionController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->input('date', Carbon::today()->toDateString());

        $missions = Mission::with(['org', 'items'])
            ->whereDate('scheduled_for', $date)
            // ->where('assigned_to_user_id', $request->user()->id) // Uncomment for real auth
            ->orderBy('scheduled_for')
            ->get();

        return response()->json([
            'data' => $missions,
        ]);
    }

    public function show($id)
    {
        $mission = Mission::with([
            'org',
            'items.device',
            'notes.user',
            'incidents',
        ])->findOrFail($id);

        return response()->json([
            'data' => $mission,
        ]);
    }

    public function update(Request $request, $id)
    {
        $mission = Mission::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:planned,in_progress,done,cancelled',
            'closed_at' => 'nullable|date',
        ]);

        $mission->update($validated);

        if ($validated['status'] === 'done' && ! $mission->closed_at) {
            $mission->update(['closed_at' => now()]);
        }

        return response()->json(['data' => $mission]);
    }
}
