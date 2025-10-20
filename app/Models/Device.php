<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    use HasFactory;
    protected $fillable = ['org_id', 'serial', 'alias', 'status', 'location', 'meta'];
    protected $casts = [
        'location' => 'array',
        'meta' => 'array'
    ];

    public function org(){
        return $this->belongsTo(Org::class);
    }
}
