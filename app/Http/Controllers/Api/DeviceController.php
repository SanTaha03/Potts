<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\Request;

class DeviceController extends Controller {
  
  public function getAllDevices(Request $req) {
    // Retourne tous les devices (collection) au lieu d'un seul device
    return Device::orderByDesc('id')->paginate(10);  

  }

  public function show(Device $device, Request $req) {
    
    $device->loadCount('readings');
    return $device;
  }
}
