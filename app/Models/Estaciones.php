<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Estaciones extends Model
{
    use SoftDeletes;

    protected $table = 'estaciones';

    public $timestamps = true;

    protected $fillable = [
        'id',
        'uuid',
        'tipo_estacion_id',
        'cliente_id',
        'fabricante_id',
        'almacen_id',
        'celular',
        'caracteristicas',
        'status',
    ];

    public function tipo_estacion()
    {
        return $this->belongsTo(TipoEstacion::class);
    }

    public function fabricante()
    {
        return $this->belongsTo(Fabricante::class);
    }

    public function almacen()
    {
        return $this->belongsTo(Almacen::class);
    }

    public function parcela()
    {
        return $this->belongsTo(Parcelas::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function zonaManejos()
    {
        return $this->belongsToMany(ZonaManejos::class, 'zona_manejos_estaciones', 'estacion_id', 'zona_manejo_id');
    }

    /**
     * Alias para zonaManejos (estaciones virtuales)
     */
    public function virtuales()
    {
        return $this->zonaManejos();
    }

    public function variables()
    {
        return $this->belongsToMany(VariablesMedicion::class, 'estacion_variable', 'estacion_id', 'variables_medicion_id');
    }

    public function estacionDatos()
    {
        return $this->hasMany(EstacionDato::class, 'estacion_id');
    }

    public const ESTATUS = [
        1 => 'En servicio',
        2 => 'Fuera de servicio',
        3 => 'En reparación',
    ];

    public static function getEstatusOptions(): array
    {
        return self::ESTATUS;
    }
}
