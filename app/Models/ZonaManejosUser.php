<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ZonaManejosUser extends Model
{
    protected $table = 'zona_manejos_user';
    protected $fillable = [
        'user_id',
        'zona_manejo_id',
    ];
}
