<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDeviceRequest;
use App\Http\Resources\DeviceResource;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class DeviceController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = (int) $request->integer('per_page', 10);
        $perPage = $perPage > 0 ? min($perPage, 1000) : 10;

        $paginator = Device::query()
            ->when(
                $request->string('search'),
                fn($query, $search) => $query->where(function ($inner) use ($search) {
                    $inner->where('device_id', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%");
                })
            )
            ->latest()
            ->paginate($perPage)
            ->appends($request->only(['search', 'per_page']));

        $paginator->getCollection()->loadCount('readings');

        return DeviceResource::collection($paginator);
    }

    public function show(Device $device): DeviceResource
    {
        $device->loadCount('readings');

        return DeviceResource::make($device);
    }

    public function store(StoreDeviceRequest $request): DeviceResource
    {
        $data = $request->validated();
        $data['org_id'] = $data['org_id'] ?? $request->user()?->org_id;
        $data['status'] = $data['status'] ?? 'active';

        $device = Device::create($data);
        $device->loadCount('readings');

        return DeviceResource::make($device);
    }
}
