<?php

namespace App\Http\Controllers\Api\App\Tech;

use App\Http\Controllers\Controller;
use App\Models\Mission;
use Illuminate\Http\Request;

class MissionNoteController extends Controller
{
    public function index($missionId)
    {
        $mission = Mission::findOrFail($missionId);

        return response()->json([
            'data' => $mission->notes()->with('user')->get(),
        ]);
    }

    public function store(Request $request, $missionId)
    {
        $mission = Mission::findOrFail($missionId);

        $validated = $request->validate([
            'message' => 'required|string',
        ]);

        $note = $mission->notes()->create([
            'user_id' => $request->user()?->id ?? 1, // Fallback to 1 if no auth yet
            'message' => $validated['message'],
        ]);

        return response()->json(['data' => $note->load('user')]);
    }
}
