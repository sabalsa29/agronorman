<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class AppBitacoraAuthService
{
    /**
     * Retorna un arreglo de accesos (filas) o vacío si credenciales inválidas.
     */
    public function getAccesos(string $user, string $pwd): array
    {
        // Ajusta el connection() si usas otra conexión: DB::connection('agronorman')
        //$rows = DB::select('CALL getAccesosAppBitacora(?, ?)', [$user, $pwd]);

    // $rows = DB::connection('mysql')
    // ->select('CALL getAccesosAppBitacora(?, ?)', [$user, $pwd]);


        // DB::select regresa array de stdClass
        return $rows ?? [];
    }
}
