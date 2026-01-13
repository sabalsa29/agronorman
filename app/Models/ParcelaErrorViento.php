<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParcelaErrorViento extends Model
{
    use HasFactory;

    protected $table = 'parcela_error_viento';

    protected $fillable = [
        'parcela_id',
        'error_tipo',
        'error_mensaje',
        'intentos_fallidos',
        'ultimo_intento',
        'activo'
    ];

    protected $casts = [
        'ultimo_intento' => 'datetime',
        'activo' => 'boolean'
    ];

    /**
     * Relación con Parcela
     */
    public function parcela()
    {
        return $this->belongsTo(Parcelas::class, 'parcela_id');
    }

    /**
     * Scope para errores activos
     */
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para errores inactivos
     */
    public function scopeInactivas($query)
    {
        return $query->where('activo', false);
    }

    /**
     * Incrementar intentos fallidos
     */
    public function incrementarIntento()
    {
        $this->increment('intentos_fallidos');
        $this->update(['ultimo_intento' => now()]);
    }

    /**
     * Marcar como inactivo
     */
    public function marcarInactivo()
    {
        $this->update(['activo' => false]);
    }

    /**
     * Marcar como activo
     */
    public function marcarActivo()
    {
        $this->update(['activo' => true]);
    }

    /**
     * Obtener errores por tipo
     */
    public static function obtenerPorTipo($tipo)
    {
        return self::where('error_tipo', $tipo)->activas()->get();
    }

    /**
     * Obtener errores recientes (últimas 24 horas)
     */
    public static function obtenerRecientes()
    {
        return self::where('ultimo_intento', '>=', now()->subDay())
            ->activas()
            ->orderBy('ultimo_intento', 'desc')
            ->get();
    }

    /**
     * Limpiar errores antiguos (más de 7 días)
     */
    public static function limpiarErroresAntiguos()
    {
        return self::where('ultimo_intento', '<', now()->subWeek())
            ->delete();
    }
}
