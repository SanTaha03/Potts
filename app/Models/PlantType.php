<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlantType extends Model
{
    use HasFactory;

    protected $fillable = [
        'slug',
        'display_name',
        'category',
        'description',
        'soil_opt_min',
        'soil_opt_max',
        'temp_opt_min',
        'temp_opt_max',
        'light_opt_min',
        'light_opt_max',
        'co2_k_per_m2_year',
        'leaf_area_m2_s',
        'leaf_area_m2_m',
        'leaf_area_m2_l',
    ];

    protected $casts = [
        'temp_opt_min' => 'float',
        'temp_opt_max' => 'float',
        'co2_k_per_m2_year' => 'float',
        'leaf_area_m2_s' => 'float',
        'leaf_area_m2_m' => 'float',
        'leaf_area_m2_l' => 'float',
    ];

    public function plants(): HasMany
    {
        return $this->hasMany(Plant::class);
    }
}
