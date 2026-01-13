<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NutricionEtapa extends Model
{
    protected $table = 'nutricion_etapa';
    public $timestamps = true;

    protected $fillable = [
        'etapa_fenologica_id',
        'variable',
    ];
}
