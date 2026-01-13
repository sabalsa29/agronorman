<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UnidadesCalorZona extends Model
{
    use SoftDeletes;

    protected $table = 'unidades_calor_zona';

    protected $fillable = [
        'zona_manejo_id',
        'fecha',
        'unidades'
    ];

    protected $casts = [
        'fecha' => 'date',
        'unidades' => 'double'
    ];

    // Relación con zona de manejo
    public function zonaManejo()
    {
        return $this->belongsTo(ZonaManejos::class, 'zona_manejo_id');
    }

    // Scope para filtrar por fecha
    public function scopePorFecha($query, $fecha)
    {
        return $query->where('fecha', $fecha);
    }

    // Scope para filtrar por zona
    public function scopePorZona($query, $zonaId)
    {
        return $query->where('zona_manejo_id', $zonaId);
    }
}
