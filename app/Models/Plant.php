<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Plant extends Model
{
    use HasFactory;

    protected $fillable = [
        'org_id',
        'plant_type_id',
        'name',
        'size',
        'location',
        'installed_at',
        'status',
    ];

    protected $casts = [
        'location' => 'array',
        'installed_at' => 'date',
    ];

    public function org(): BelongsTo
    {
        return $this->belongsTo(Org::class);
    }

    public function plantType(): BelongsTo
    {
        return $this->belongsTo(PlantType::class);
    }

    public function device(): HasOne
    {
        return $this->hasOne(Device::class);
    }
}
