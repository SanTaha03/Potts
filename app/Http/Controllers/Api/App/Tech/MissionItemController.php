<?php

namespace App\Http\Controllers\Api\App\Tech;

use App\Http\Controllers\Controller;
use App\Models\MissionItem;
use Illuminate\Http\Request;

class MissionItemController extends Controller
{
    public function update(Request $request, $id)
    {
        $item = MissionItem::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:todo,done,skipped',
        ]);

        $item->update($validated);

        return response()->json(['data' => $item]);
    }
}
