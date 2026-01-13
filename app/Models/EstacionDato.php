<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;

class EstacionDato extends Model
{
    use HasFactory;

    protected $table = 'estacion_dato';

    protected $fillable = [
        'id',
        'id_origen',
        'radiacion_solar',
        'viento',
        'precipitacion_acumulada',
        'humedad_relativa',
        'potencial_de_hidrogeno',
        'conductividad_electrica',
        'temperatura',
        'temperatura_lvl1',
        'humedad_15',
        'direccion_viento',
        'velocidad_viento',
        'co2',
        'ph',
        'phos',
        'nit',
        'pot',
        'estacion_id',
        'temperatura_suelo',
        'alertas',
        'capacidad_productiva',
        'bateria',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'temperatura' => 'float',
        'humedad' => 'float',
        'precipitacion' => 'float',
        'radiacion' => 'float',
        'velocidad_viento' => 'float',
        'direccion_viento' => 'float',
        'presion' => 'float'
    ];

    /**
     * Get the estacion that owns the dato.
     */
    public function estacion()
    {
        return $this->belongsTo(Estacion::class);
    }
}
