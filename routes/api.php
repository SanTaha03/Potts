<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DeviceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

Route::get('/csrf-token', fn() => response()->noContent()); //ping simple pour obtenir le cookie de session

Route::post('/login', function (Request $request) {
  $creds = $request->validate(['email'=>'required|email','password'=>'required']);
  if (!Auth::attempt($creds)) return response()->json(['message'=>'Invalid credentials'], 422);
  $request->session()->regenerate();
  return ['user'=>$request->user()];
});

Route::post('/logout', function (Request $request) {
  Auth::guard('web')->logout();
  $request->session()->invalidate();
  $request->session()->regenerateToken();
  return response()->noContent();
});

// Route publique de test (temporaire)
Route::get('/devices-test', [DeviceController::class, 'getAllDevices']);

Route::middleware('auth:sanctum')->group(function () {
  Route::get('/devices', [DeviceController::class, 'getAllDevices']);
  Route::get('/devices/{device}', [DeviceController::class, 'show']);
});

Route::get('/health', fn() => response()->json(['ok'=>true,'ts'=>now()]));
