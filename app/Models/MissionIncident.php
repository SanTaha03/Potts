<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MissionIncident extends Model
{
    use HasFactory;

    protected $fillable = [
        'mission_id',
        'device_id',
        'severity',
        'type',
        'description',
        'created_by'
    ];

    public function mission()
    {
        return $this->belongsTo(Mission::class);
    }

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
