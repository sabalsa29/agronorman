<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParcelaErrorPrecipitacion extends Model
{
    use HasFactory;

    protected $table = 'parcelas_error_precipitacion';

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
        'activo' => 'boolean',
    ];

    /**
     * Relación con la parcela
     */
    public function parcela()
    {
        return $this->belongsTo(Parcelas::class, 'parcela_id');
    }

    /**
     * Scope para parcelas activas (que deben ser ignoradas)
     */
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope para parcelas inactivas (que pueden volver a intentarse)
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
     * Marcar como activa (ignorar)
     */
    public function marcarComoActiva()
    {
        $this->update(['activo' => true]);
    }

    /**
     * Marcar como inactiva (volver a intentar)
     */
    public function marcarComoInactiva()
    {
        $this->update(['activo' => false]);
    }
}
