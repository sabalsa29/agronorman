<?php

namespace App\Http\Controllers;

use App\Models\EtapaFenologica;
use App\Models\TipoCultivos;
use Illuminate\Http\Request;

class EtapaFenologicaTipoCultivoController extends Controller
{
    public function index(Request $request)
    {
        $etapas = TipoCultivos::where('id', $request->tipo_cultivo)
            ->first();
        $etapas_fenologicas = EtapaFenologica::where('status', 1)->get();

        $etapas->load(['etapas_fenologicas']);

        return view('cultivos.tipo_cultivos.parametros.index', [
            "name_routes" => "Tipo de cultivo " . $etapas->nombre,
            "section_name" => "Parámetros de etapa fenológica del tipo de cultivo " . $etapas->nombre,
            "section_description" => $etapas->nombre,
            "list" => $etapas->etapas_fenologicas,
            "tipo_cultivo_id" => $etapas->id,
            "cultivo_id" => $request->id,
            "etapas_fenologicas" => $etapas_fenologicas,
        ]);
    }
}
