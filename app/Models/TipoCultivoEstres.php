<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TipoCultivoEstres extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'tipo_cultivo_estres';

    protected $fillable = [
        'tipo_cultivo_id',
        'variable_id',
        'tipo',
        'muy_bajo',
        'bajo_min',
        'bajo_max',
        'optimo_min',
        'optimo_max',
        'alto_min',
        'alto_max',
        'muy_alto'
    ];

    protected $casts = [
        'tipo' => 'string',
        'muy_bajo' => 'decimal:2',
        'bajo_min' => 'decimal:2',
        'bajo_max' => 'decimal:2',
        'optimo_min' => 'decimal:2',
        'optimo_max' => 'decimal:2',
        'alto_min' => 'decimal:2',
        'alto_max' => 'decimal:2',
        'muy_alto' => 'decimal:2'
    ];

    /**
     * Get the tipo cultivo that owns the estres.
     */
    public function tipoCultivo()
    {
        return $this->belongsTo(TipoCultivos::class, 'tipo_cultivo_id');
    }

    /**
     * Get the variable that owns the estres.
     */
    public function variable()
    {
        return $this->belongsTo(VariablesMedicion::class, 'variable_id');
    }

    /**
     * Scope para filtrar por tipo de cultivo
     */
    public function scopePorTipoCultivo($query, $tipoCultivoId)
    {
        return $query->where('tipo_cultivo_id', $tipoCultivoId);
    }

    /**
     * Scope para filtrar por variable
     */
    public function scopePorVariable($query, $variableId)
    {
        return $query->where('variable_id', $variableId);
    }

    /**
     * Scope para filtrar por tipo (diurno/nocturno)
     */
    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    /**
     * Obtener parámetros de estrés para un tipo de cultivo y variable específicos
     */
    public static function obtenerParametros($tipoCultivoId, $variableId, $tipo = null)
    {
        $query = static::where('tipo_cultivo_id', $tipoCultivoId)
            ->where('variable_id', $variableId);

        if ($tipo) {
            $query->where('tipo', $tipo);
        }

        return $query->get();
    }
}
