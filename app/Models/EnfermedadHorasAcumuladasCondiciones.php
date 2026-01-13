<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EnfermedadHorasAcumuladasCondiciones extends Model
{
    use HasFactory;

    protected $table = 'enfermedad_horas_acumuladas_condiciones';

    protected $fillable = [
        'fecha',
        'minutos',
        'tipo_cultivo_id',
        'enfermedad_id',
        'estacion_id'
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'minutos' => 'integer'
    ];

    /**
     * Relación con tipo de cultivo
     */
    public function tipoCultivo()
    {
        return $this->belongsTo(TipoCultivos::class, 'tipo_cultivo_id');
    }

    /**
     * Relación con enfermedad
     */
    public function enfermedad()
    {
        return $this->belongsTo(Enfermedades::class, 'enfermedad_id');
    }

    /**
     * Relación con estación
     */
    public function estacion()
    {
        return $this->belongsTo(Estaciones::class, 'estacion_id');
    }

    /**
     * Scope para filtrar por fecha
     */
    public function scopePorFecha($query, $fecha)
    {
        return $query->whereDate('fecha', $fecha);
    }

    /**
     * Scope para filtrar por tipo de cultivo
     */
    public function scopePorTipoCultivo($query, $tipoCultivoId)
    {
        return $query->where('tipo_cultivo_id', $tipoCultivoId);
    }

    /**
     * Scope para filtrar por enfermedad
     */
    public function scopePorEnfermedad($query, $enfermedadId)
    {
        return $query->where('enfermedad_id', $enfermedadId);
    }

    /**
     * Scope para filtrar por estación
     */
    public function scopePorEstacion($query, $estacionId)
    {
        return $query->where('estacion_id', $estacionId);
    }
}
