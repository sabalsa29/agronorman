<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParcelaErrorPresion extends Model
{
    use HasFactory;

    protected $table = 'parcela_error_presion';

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
     * Relación con Parcelas
     */
    public function parcela()
    {
        return $this->belongsTo(Parcelas::class);
    }

    /**
     * Scope para errores activos
     */
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Incrementar intentos fallidos
     */
    public function incrementarIntento()
    {
        $this->increment('intentos_fallidos');
    }

    /**
     * Marcar como inactivo
     */
    public function marcarInactivo()
    {
        $this->update(['activo' => false]);
    }
}