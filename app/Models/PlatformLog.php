<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformLog extends Model
{
    protected $table = 'platform_logs';

    protected $fillable = [
        'usuario_id',
        'username',
        'seccion',
        'accion',
        'entidad_tipo',
        'entidad_id',
        'descripcion',
        'datos_anteriores',
        'datos_nuevos',
        'datos_adicionales',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'datos_anteriores' => 'array',
        'datos_nuevos' => 'array',
        'datos_adicionales' => 'array',
    ];

    /**
     * Relación con el usuario
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    /**
     * Crear un log de acción
     */
    public static function crearLog(
        ?int $usuarioId,
        string $username,
        string $seccion,
        string $accion,
        string $entidadTipo,
        ?int $entidadId = null,
        string $descripcion,
        ?array $datosAnteriores = null,
        ?array $datosNuevos = null,
        ?array $datosAdicionales = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): self {
        return self::create([
            'usuario_id' => $usuarioId,
            'username' => $username,
            'seccion' => $seccion,
            'accion' => $accion,
            'entidad_tipo' => $entidadTipo,
            'entidad_id' => $entidadId,
            'descripcion' => $descripcion,
            'datos_anteriores' => $datosAnteriores,
            'datos_nuevos' => $datosNuevos,
            'datos_adicionales' => $datosAdicionales,
            'ip_address' => $ipAddress ?? request()->ip(),
            'user_agent' => $userAgent ?? request()->userAgent(),
        ]);
    }

    /**
     * Scope para filtrar por sección
     */
    public function scopePorSeccion($query, string $seccion)
    {
        return $query->where('seccion', $seccion);
    }

    /**
     * Scope para filtrar por acción
     */
    public function scopePorAccion($query, string $accion)
    {
        return $query->where('accion', $accion);
    }

    /**
     * Scope para filtrar por entidad
     */
    public function scopePorEntidad($query, string $entidadTipo, ?int $entidadId = null)
    {
        $query->where('entidad_tipo', $entidadTipo);
        if ($entidadId !== null) {
            $query->where('entidad_id', $entidadId);
        }
        return $query;
    }
}
