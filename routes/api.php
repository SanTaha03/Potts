<?php

use App\Http\Controllers\Api\App\DeviceReadController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Api\DeviceController;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {
  Route::post('/login', function (Request $request) {
    $validated = $request->validate([
      'email' => ['required','email'],
      'password' => ['required'],
      'remember' => ['nullable','boolean'],
    ]);

    $credentials = [
      'email' => $validated['email'],
      'password' => $validated['password'],
    ];

    if (!Auth::attempt($credentials, $request->boolean('remember'))) {
      return response()->json(['message' => 'Identifiants incorrects'], 422);
    }

    $request->session()->regenerate();
    return ['user'=>$request->user()];
  });

  Route::post('/logout', function (Request $request) {
    Auth::guard('web')->logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return response()->noContent();
  });
});

Route::middleware('auth:sanctum')->group(function () {
  Route::get('/me', fn (Request $request) => ['user' => $request->user()]);
  Route::apiResource('devices', DeviceController::class)->only(['index', 'show', 'store']);
});

Route::get('/health', fn() => response()->json(['ok'=>true,'ts'=>now()]));

// Hardware API Routes
Route::prefix('hardware/v1')->group(function () {
    Route::post('/telemetry', [\App\Http\Controllers\Api\Hardware\TelemetryController::class, 'store']);
});

// App API Routes (Read-only / User centric)
Route::prefix('app/v1')->middleware('auth:sanctum')->group(function () {
    Route::get('/devices', [DeviceReadController::class, 'index']);
    Route::get('/devices/{deviceId}', [DeviceReadController::class, 'show']);
    Route::get('/devices/{deviceId}/history', [DeviceReadController::class, 'history']);

    // Tech / Provider Routes
    Route::prefix('tech')->group(function () {
        Route::get('/missions', [\App\Http\Controllers\Api\App\Tech\MissionController::class, 'index']);
        Route::get('/missions/{id}', [\App\Http\Controllers\Api\App\Tech\MissionController::class, 'show']);
        Route::patch('/missions/{id}', [\App\Http\Controllers\Api\App\Tech\MissionController::class, 'update']);
        
        Route::patch('/mission-items/{id}', [\App\Http\Controllers\Api\App\Tech\MissionItemController::class, 'update']);
        
        Route::get('/missions/{id}/notes', [\App\Http\Controllers\Api\App\Tech\MissionNoteController::class, 'index']);
        Route::post('/missions/{id}/notes', [\App\Http\Controllers\Api\App\Tech\MissionNoteController::class, 'store']);
        
        Route::post('/missions/{id}/incidents', [\App\Http\Controllers\Api\App\Tech\MissionIncidentController::class, 'store']);
    });
});

