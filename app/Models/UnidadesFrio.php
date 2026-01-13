<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UnidadesFrio extends Model
{
    use SoftDeletes;

    protected $table = 'unidades_frio';
    public $timestamps = true;

    protected $fillable = [
        'zona_manejo_id',
        'fecha',
        'unidades',
    ];

    /**
     * Filtra por rango de fechas (inclusive).
     */
    public function scopeBetweenFecha($query, string $from, string $to)
    {
        return $query->whereBetween('fecha', [$from, $to]);
    }

    /**
     * Filtra todas las filas a partir de una fecha.
     */
    public function scopeSinceFecha($query, string $from)
    {
        return $query->where('fecha', '>=', $from);
    }

    /**
     * Filtra por zona.
     */
    public function scopeZonaManejos($query, int $estacionId)
    {
        return $query->where('zona_manejo_id', $estacionId);
    }
}
