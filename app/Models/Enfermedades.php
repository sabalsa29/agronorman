<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Enfermedades extends Model
{
    use SoftDeletes;

    protected $table = 'enfermedades';

    public $timestamps = true;

    protected $fillable = [
        'nombre',
        'slug',
        'status'
    ];

    public function tipoCultivos()
    {
        return $this->belongsToMany(
            TipoCultivos::class,              // Modelo relacionado final
            'tipo_cultivos_enfermedades',    // Tabla pivote
            'enfermedad_id',                 // FK en la pivote hacia este modelo
            'tipo_cultivo_id'                // FK en la pivote hacia el otro modelo
        )->withPivot([
            'riesgo_humedad',
            'riesgo_humedad_max',
            'riesgo_temperatura',
            'riesgo_temperatura_max',
            'riesgo_medio',
            'riesgo_mediciones',
        ]);
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName()
    {
        return 'id';
    }
}
