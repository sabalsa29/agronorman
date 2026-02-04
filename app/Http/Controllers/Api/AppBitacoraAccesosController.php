<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AppBitacoraAccesosController extends Controller
{
    public function index(Request $request)
    {
        $userId = (int)$request->user()->id;

        // Se debe validar si existe en user_grupo con ese id, si existe, vamos a obtener todas las 
        // parcelas de ese grupo, depues todas las zonas de manejo de esas parcelas
        // $rows = DB::table('user_grupo as a')
        //     ->join('zona_manejos as zm', 'zm.id', '=', 'a.zona_manejo_id')
        //     ->join('parcelas as p', 'p.id', '=', 'zm.parcela_id')
        //     ->where('a.user_id', $userId)
        //     ->selectRaw('p.nombre as predio, zm.nombre as zm, zm.id as id_zm')
        //     ->distinct()
        //     ->orderBy('p.nombre')
        //     ->orderBy('zm.nombre')
        //     ->get();
        // Accesos asociados a los grupos que tiene el usuario
        $rows = DB::table('zona_manejos as zm')
            ->join('parcelas as p', 'p.id', '=', 'zm.parcela_id')
            ->join('grupo_parcela as pg', 'pg.parcela_id', '=', 'p.id')
            ->join('user_grupo as ug', 'ug.grupo_id', '=', 'pg.grupo_id')
            ->where('ug.user_id', $userId)
            ->selectRaw('p.nombre as predio, zm.nombre as zm, zm.id as id_zm')
            ->distinct()
            ->orderBy('p.nombre')
            ->orderBy('zm.nombre')
            ->get();

        // Accesos asociados a parcelas asignadas directamente al usuario
        $rowsDirectos = DB::table('zona_manejos as zm')
            ->join('parcelas as p', 'p.id', '=', 'zm.parcela_id')
            ->join('grupo_parcela as up', 'up.parcela_id', '=', 'p.id')
            ->where('up.user_id', $userId)
            ->selectRaw('p.nombre as predio, zm.nombre as zm, zm.id as id_zm')
            ->distinct()
            ->orderBy('p.nombre')
            ->orderBy('zm.nombre')
            ->get();

        // Accesos asociados a zonas de manejo asignadas directamente al usuario
        $rowsZonasDirectas = DB::table('zona_manejos as zm')
            ->join('parcelas as p', 'p.id', '=', 'zm.parcela_id')
            ->join('grupo_zona_manejo as uzm', 'uzm.zona_manejo_id', '=', 'zm.id')
            ->where('uzm.user_id', $userId)
            ->selectRaw('p.nombre as predio, zm.nombre as zm, zm.id as id_zm')
            ->distinct()
            ->orderBy('p.nombre')
            ->orderBy('zm.nombre')
            ->get();    
        // Unir todos los accesos y eliminar duplicados
        $rows = $rows->merge($rowsDirectos)->merge($rowsZonasDirectas)->unique(function ($item) {
            return $item->predio . '|' . $item->zm . '|' . $item->id_zm;
        }); 

        $accesos = $rows->map(fn($r) => [
            'parcela' => (string)$r->predio,
            'zm'     => (string)$r->zm,
            'id_zm'  => (string)$r->id_zm,
        ])->values();

        return response()->json([
            'ok' => true,
            'accesos' => $accesos,
        ]);
    }
}
