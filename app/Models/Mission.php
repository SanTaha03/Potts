<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mission extends Model
{
    use HasFactory;

    protected $fillable = [
        'org_id',
        'assigned_to_user_id',
        'type',
        'title',
        'address',
        'status',
        'scheduled_for',
        'closed_at',
    ];

    protected $casts = [
        'scheduled_for' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function org()
    {
        return $this->belongsTo(Org::class);
    }

    public function items()
    {
        return $this->hasMany(MissionItem::class);
    }

    public function notes()
    {
        return $this->hasMany(MissionNote::class)->latest();
    }

    public function incidents()
    {
        return $this->hasMany(MissionIncident::class);
    }
}
