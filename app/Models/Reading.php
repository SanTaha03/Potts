<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reading extends Model
{
    use HasFactory;

    protected $fillable = ['name','email','password','role','org_id','preferences'];
    protected $casts = [
        'preferences' => 'array',
    ];
}
