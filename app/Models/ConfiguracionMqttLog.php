<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConfiguracionMqttLog extends Model
{
    protected $table = 'configuracion_mqtt_logs';

    protected $fillable = [
        'usuario_id',
        'username',
        'accion',
        'descripcion',
        'datos_adicionales',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'datos_adicionales' => 'array',
    ];

    /**
     * Relación con el usuario
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(ConfiguracionMqttUsuario::class, 'usuario_id');
    }

    /**
     * Crear un log de acción
     */
    public static function crearLog(
        ?int $usuarioId,
        string $username,
        string $accion,
        string $descripcion,
        ?array $datosAdicionales = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): self {
        return self::create([
            'usuario_id' => $usuarioId,
            'username' => $username,
            'accion' => $accion,
            'descripcion' => $descripcion,
            'datos_adicionales' => $datosAdicionales,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }
}
