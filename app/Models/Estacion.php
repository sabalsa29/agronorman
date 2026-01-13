<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Estacion extends Model
{
    use HasFactory;

    protected $table = 'estaciones';

    protected $fillable = [
        'id',
        'nombre',
        'tipo_estacion_id',
        'status'
    ];

    /**
     * Get the datos for the estacion.
     */
    public function datos()
    {
        return $this->hasMany(EstacionDato::class);
    }

    /**
     * Get the tipo_estacion that owns the estacion.
     */
    public function tipoEstacion()
    {
        return $this->belongsTo(TipoEstacion::class);
    }

    /**
     * Get the zona_manejos that belong to the estacion.
     */
    public function zonaManejos()
    {
        return $this->belongsToMany(ZonaManejos::class, 'zona_manejos_estaciones', 'estacion_id', 'zona_manejo_id');
    }
}
