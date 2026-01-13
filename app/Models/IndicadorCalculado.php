<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndicadorCalculado extends Model
{
    use HasFactory;

    protected $table = 'indicador_calculado';

    protected $fillable = [
        'fecha',
        'indicador_id',
        'zona_manejo_id',
        'escala1',
        'escala2',
        'escala3',
        'escala4',
        'escala5',
        'horas1',
        'horas2',
        'horas3',
        'horas4',
        'horas5'
    ];

    protected $casts = [
        'fecha' => 'date',
        'escala1' => 'decimal:2',
        'escala2' => 'decimal:2',
        'escala3' => 'decimal:2',
        'escala4' => 'decimal:2',
        'escala5' => 'decimal:2',
        'horas1' => 'decimal:2',
        'horas2' => 'decimal:2',
        'horas3' => 'decimal:2',
        'horas4' => 'decimal:2',
        'horas5' => 'decimal:2'
    ];

    /**
     * Get the indicador that owns the calculado.
     */
    public function indicador()
    {
        return $this->belongsTo(Indicador::class, 'indicador_id');
    }

    /**
     * Get the zona manejo that owns the calculado.
     */
    public function zonaManejo()
    {
        return $this->belongsTo(ZonaManejos::class, 'zona_manejo_id');
    }
}
