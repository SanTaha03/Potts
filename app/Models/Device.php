<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Device extends Model
{
    use HasFactory;

    protected $fillable = ['org_id', 'plant_id', 'device_id', 'token', 'name', 'status', 'location', 'meta', 'last_seen_at', 'last_values'];

    protected $casts = [
        'location' => 'array',
        'meta' => 'array',
        'last_seen_at' => 'datetime',
        'last_values' => 'array',
    ];

    public function org()
    {
        return $this->belongsTo(Org::class);
    }

    public function plant()
    {
        return $this->belongsTo(Plant::class);
    }

    public function readings(): HasMany
    {
        return $this->hasMany(Reading::class);
    }
}
