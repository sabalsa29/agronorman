<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ConfiguracionMqttAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar si el usuario está autenticado en la sesión especial
        if (!$request->session()->has('configuracion_mqtt_authenticated')) {
            return redirect()->route('configuracion-mqtt.login');
        }

        return $next($request);
    }
}
