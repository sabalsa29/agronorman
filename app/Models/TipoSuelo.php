<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TipoSuelo extends Model
{
    use SoftDeletes;

    protected $table = 'tipo_suelos';
    public $timestamps = true;

    protected $fillable = [
        'tipo_suelo',
        'bajo',
        'optimo_min',
        'optimo_max',
        'alto',
    ];

    public static function semaforo(int $tipoSueloId, $humedad): string
    {
        $tipo = self::find($tipoSueloId);
        if (! $tipo) {
            return '#ffffff'; // color neutro si no existe el tipo
        }

        // Normaliza $humedad a float
        if (is_array($humedad)) {
            $humedad = (float) end($humedad);
        } else {
            $humedad = (float) $humedad;
        }

        // Umbrales simplificados
        $bajo       = $tipo->bajo;
        $optimoMin  = $tipo->optimo_min;
        $optimoMax  = $tipo->optimo_max;
        $alto       = $tipo->alto;

        // 1) Fuera de rango extremo (rojo)
        if ($humedad <= $bajo || $humedad >= $alto) {
            return '#f26368';
        }

        // 2) Zona baja de advertencia (amarillo)
        if ($humedad > $bajo && $humedad < $optimoMin) {
            return '#f5dfa9';
        }

        // 3) Zona óptima (verde)
        if ($humedad >= $optimoMin && $humedad <= $optimoMax) {
            return '#0cab73';
        }

        // 4) Zona alta de advertencia (amarillo)
        if ($humedad > $optimoMax && $humedad < $alto) {
            return '#f5dfa9';
        }

        // Fallback: óptimo
        return '#0cab73';
    }
}
