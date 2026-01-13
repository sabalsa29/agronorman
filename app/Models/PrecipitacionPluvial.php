<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class PrecipitacionPluvial extends Model
{
    protected $table = 'precipitacion_pluvial';

    protected $fillable = [
        'parcela_id',
        'zona_manejo_id',
        'fecha_solicita',
        'hora_solicita',
        'lat',
        'lon',
        'fecha_hora_dato',
        'precipitacion_mm',
        'precipitacion_probabilidad',
        'tipo_dato',
        'fuente',
        'datos_raw'
    ];

    protected $casts = [
        'fecha_solicita' => 'date',
        'fecha_hora_dato' => 'datetime',
        'precipitacion_mm' => 'decimal:2',
        'precipitacion_probabilidad' => 'decimal:2',
        'datos_raw' => 'array'
    ];

    /**
     * Relación con Parcela
     */
    public function parcela(): BelongsTo
    {
        return $this->belongsTo(Parcelas::class, 'parcela_id');
    }

    /**
     * Relación con ZonaManejo
     */
    public function zonaManejo(): BelongsTo
    {
        return $this->belongsTo(ZonaManejos::class, 'zona_manejo_id');
    }

    /**
     * Scope para datos históricos
     */
    public function scopeHistoricos($query)
    {
        return $query->where('tipo_dato', 'historico');
    }

    /**
     * Scope para datos de pronóstico
     */
    public function scopePronostico($query)
    {
        return $query->where('tipo_dato', 'pronostico');
    }

    /**
     * Scope para datos de OpenWeather
     */
    public function scopeOpenWeather($query)
    {
        return $query->where('fuente', 'openweather');
    }

    /**
     * Scope para datos de estación
     */
    public function scopeEstacion($query)
    {
        return $query->where('fuente', 'estacion');
    }

    /**
     * Obtener datos de precipitación para un rango de fechas
     */
    public static function getDatosPorRango($parcelaId, $fechaInicio, $fechaFin, $tipoDato = 'historico')
    {
        return self::where('parcela_id', $parcelaId)
            ->where('tipo_dato', $tipoDato)
            ->whereBetween('fecha_hora_dato', [$fechaInicio, $fechaFin])
            ->orderBy('fecha_hora_dato')
            ->get();
    }

    /**
     * Obtener precipitación acumulada por día
     */
    public static function getPrecipitacionAcumuladaDiaria($parcelaId, $fechaInicio, $fechaFin)
    {
        return self::where('parcela_id', $parcelaId)
            ->where('tipo_dato', 'historico')
            ->whereBetween('fecha_hora_dato', [$fechaInicio, $fechaFin])
            ->selectRaw('DATE(fecha_hora_dato) as fecha, SUM(precipitacion_mm) as precipitacion_total')
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get();
    }
}
