<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CultivoEnfermedad extends Model
{
    protected $table = 'cultivo_enfermedad';
    protected $fillable = [
        'cultivo_id',
        'enfermedad_id',
        'riesgo_humedad',
        'riesgo_humedad_max',
        'riesgo_temperatura',
        'riesgo_temperatura_max',
        'created_at',
        'updated_at',
        'riesgo_medio',
        'riesgo_mediciones',
    ];

    public function cultivo()
    {
        return $this->belongsTo(Cultivo::class);
    }
}
