<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MissionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'mission_id',
        'device_id',
        'action',
        'status',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function mission()
    {
        return $this->belongsTo(Mission::class);
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }
}
