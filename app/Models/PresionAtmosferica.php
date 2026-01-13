<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PresionAtmosferica extends Model
{
    use HasFactory;

    protected $table = 'presion_atmosferica';

    protected $fillable = [
        'parcela_id',
        'zona_manejo_id',
        'fecha_solicita',
        'hora_solicita',
        'lat',
        'lon',
        'fecha_hora_dato',
        'pressure',
        'sea_level',
        'grnd_level',
        'tipo_dato',
        'fuente',
        'datos_raw'
    ];

    protected $casts = [
        'fecha_solicita' => 'date',
        'fecha_hora_dato' => 'datetime',
        'pressure' => 'decimal:2',
        'sea_level' => 'decimal:2',
        'grnd_level' => 'decimal:2',
        'lat' => 'decimal:7',
        'lon' => 'decimal:7',
        'datos_raw' => 'array'
    ];

    /**
     * Relación con Parcelas
     */
    public function parcela()
    {
        return $this->belongsTo(Parcelas::class);
    }

    /**
     * Relación con ZonaManejos
     */
    public function zonaManejo()
    {
        return $this->belongsTo(ZonaManejos::class);
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
}
