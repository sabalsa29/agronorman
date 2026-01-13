<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Indicador extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'indicadores';

    protected $fillable = [
        'nombre',
        'variable_id',
        'momento_dia'
    ];

    protected $casts = [
        'momento_dia' => 'string'
    ];

    /**
     * Get the variable that owns the indicador.
     */
    public function variable()
    {
        return $this->belongsTo(VariablesMedicion::class, 'variable_id');
    }

    /**
     * Get the indicadores calculados for this indicador.
     */
    public function indicadoresCalculados()
    {
        return $this->hasMany(IndicadorCalculado::class, 'indicador_id');
    }
}
