<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResumenTemperaturas extends Model
{
    protected $table = 'resumen_temperaturas';
    public $timestamps = true;

    protected $fillable = [
        'id',
        'zona_manejo_id',
        'fecha',
        'max_nocturna',
        'min_nocturna',
        'amp_nocturna',
        'max_diurna',
        'min_diurna',
        'amp_diurna',
        'max',
        'min',
        'amp',
        'uc',
        'uf'
    ];
}
