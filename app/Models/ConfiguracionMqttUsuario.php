<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class ConfiguracionMqttUsuario extends Model
{
    protected $table = 'configuracion_mqtt_usuarios';

    protected $fillable = [
        'username',
        'password',
        'activo',
        'estaciones_permitidas',
        'parametros_permitidos',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'estaciones_permitidas' => 'array',
        'parametros_permitidos' => 'array',
    ];

    /**
     * Verificar credenciales
     */
    public static function verificarCredenciales(string $username, string $password): ?self
    {
        $usuario = self::where('username', $username)
            ->where('activo', true)
            ->first();

        if ($usuario && Hash::check($password, $usuario->password)) {
            return $usuario;
        }

        return null;
    }

    /**
     * Verificar si el usuario tiene permiso para modificar una estación
     */
    public function tienePermisoEstacion(int $estacionId): bool
    {
        // Si no tiene restricciones (null), tiene acceso a todas
        if ($this->estaciones_permitidas === null) {
            return true;
        }

        // Verificar si la estación está en la lista de permitidas
        return in_array($estacionId, $this->estaciones_permitidas ?? []);
    }

    /**
     * Verificar si el usuario tiene permiso para enviar un parámetro
     */
    public function tienePermisoParametro(string $parametro): bool
    {
        // Si no tiene restricciones (null), tiene acceso a todos
        if ($this->parametros_permitidos === null) {
            return true;
        }

        // Verificar si el parámetro está permitido
        return $this->parametros_permitidos[$parametro] ?? false;
    }

    /**
     * Obtener parámetros con valores por defecto aplicados según permisos
     */
    public function aplicarPermisosParametros(array $parametros): array
    {
        $valoresPorDefecto = [
            'PCF' => 0,
            'PCR' => 1,
            'PTP' => 0,
            'PTC' => 60,
            'PTR' => 0,
            'PRS' => 0,
        ];

        $parametrosFinales = [];

        foreach ($valoresPorDefecto as $param => $valorDefecto) {
            // Si el usuario tiene permiso, usar el valor enviado (si existe y no es null), sino usar el por defecto
            if ($this->tienePermisoParametro($param)) {
                $parametrosFinales[$param] = isset($parametros[$param]) && $parametros[$param] !== null 
                    ? $parametros[$param] 
                    : $valorDefecto;
            } else {
                $parametrosFinales[$param] = $valorDefecto;
            }
        }

        return $parametrosFinales;
    }
}
