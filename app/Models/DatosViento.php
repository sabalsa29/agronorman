<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class DatosViento extends Model
{
    use HasFactory;

    protected $table = 'datos_viento';

    protected $fillable = [
        'parcela_id',
        'zona_manejo_id',
        'fecha_solicita',
        'hora_solicita',
        'lat',
        'lon',
        'fecha_hora_dato',
        'wind_speed',
        'wind_gust',
        'wind_deg',
        'wind_direction',
        'wind_speed_2m',
        'wind_speed_10m',
        'wind_gust_10m',
        'tipo_dato',
        'fuente',
        'datos_raw'
    ];

    protected $casts = [
        'fecha_solicita' => 'date',
        'fecha_hora_dato' => 'datetime',
        'wind_speed' => 'decimal:2',
        'wind_gust' => 'decimal:2',
        'wind_speed_2m' => 'decimal:2',
        'wind_speed_10m' => 'decimal:2',
        'wind_gust_10m' => 'decimal:2',
        'datos_raw' => 'array'
    ];

    /**
     * Relación con Parcela
     */
    public function parcela()
    {
        return $this->belongsTo(Parcelas::class, 'parcela_id');
    }

    /**
     * Relación con ZonaManejo
     */
    public function zonaManejo()
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
     * Obtener datos de viento por parcela y rango de fechas
     */
    public static function obtenerDatosPorParcela($parcelaId, $fechaInicio, $fechaFin, $tipoDato = null)
    {
        $query = self::where('parcela_id', $parcelaId)
            ->whereBetween('fecha_hora_dato', [$fechaInicio, $fechaFin])
            ->orderBy('fecha_hora_dato');

        if ($tipoDato) {
            $query->where('tipo_dato', $tipoDato);
        }

        return $query->get();
    }

    /**
     * Obtener datos de viento por zona de manejo y rango de fechas
     */
    public static function obtenerDatosPorZonaManejo($zonaManejoId, $fechaInicio, $fechaFin, $tipoDato = null)
    {
        $query = self::where('zona_manejo_id', $zonaManejoId)
            ->whereBetween('fecha_hora_dato', [$fechaInicio, $fechaFin])
            ->orderBy('fecha_hora_dato');

        if ($tipoDato) {
            $query->where('tipo_dato', $tipoDato);
        }

        return $query->get();
    }

    /**
     * Obtener resumen de viento por día
     */
    public static function obtenerResumenDiario($parcelaId, $fechaInicio, $fechaFin)
    {
        return self::where('parcela_id', $parcelaId)
            ->whereBetween('fecha_hora_dato', [$fechaInicio, $fechaFin])
            ->selectRaw('
                DATE(fecha_hora_dato) as fecha,
                AVG(wind_speed) as velocidad_promedio,
                MAX(wind_speed) as velocidad_maxima,
                MIN(wind_speed) as velocidad_minima,
                AVG(wind_gust) as rafagas_promedio,
                MAX(wind_gust) as rafagas_maxima,
                COUNT(*) as total_registros
            ')
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get();
    }

    /**
     * Obtener dirección de viento predominante por día
     */
    public static function obtenerDireccionPredominante($parcelaId, $fechaInicio, $fechaFin)
    {
        return self::where('parcela_id', $parcelaId)
            ->whereBetween('fecha_hora_dato', [$fechaInicio, $fechaFin])
            ->whereNotNull('wind_direction')
            ->selectRaw('
                DATE(fecha_hora_dato) as fecha,
                wind_direction,
                COUNT(*) as frecuencia
            ')
            ->groupBy('fecha', 'wind_direction')
            ->orderBy('fecha')
            ->orderByDesc('frecuencia')
            ->get();
    }

    /**
     * Accessor para formatear velocidad del viento
     */
    public function getVelocidadFormateadaAttribute()
    {
        return $this->wind_speed ? number_format($this->wind_speed, 1) . ' m/s' : 'N/A';
    }

    /**
     * Accessor para formatear ráfagas
     */
    public function getRafagasFormateadasAttribute()
    {
        return $this->wind_gust ? number_format($this->wind_gust, 1) . ' m/s' : 'N/A';
    }

    /**
     * Accessor para formatear dirección
     */
    public function getDireccionFormateadaAttribute()
    {
        if ($this->wind_direction && $this->wind_deg) {
            return $this->wind_direction . ' (' . $this->wind_deg . '°)';
        }
        return $this->wind_direction ?? 'N/A';
    }
}
