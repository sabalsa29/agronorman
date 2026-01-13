<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EtapaFenologicaTipoCultivo;
use Illuminate\Http\Request;
use App\Models\NutricionEtapaFenologicaTipoCultivo;

class NutricionEtapaFenologicaTipoCultivoApiController extends Controller
{
    public function index()
    {
        return NutricionEtapaFenologicaTipoCultivo::all();
    }

    public function show(Request $request)
    {
        $etapaFenologicaTipoCultivo = EtapaFenologicaTipoCultivo::where('tipo_cultivo_id', $request->tipo_cultivo_id)
            ->where('etapa_fenologica_id', $request->etapa_fenologica_id)
            ->first();

        if (!$etapaFenologicaTipoCultivo) {
            return response()->json([
                'id' => null,
                'etapa_fenologica_tipo_cultivo_id' => null,
                'tipo_cultivo_id' => $request->tipo_cultivo_id,
                'variable' => $request->query('variable'),
                'min' => '',
                'optimo_min' => '',
                'optimo_max' => '',
                'max' => ''
            ]);
        }

        $variable = $request->query('variable');
        $item = NutricionEtapaFenologicaTipoCultivo::where('etapa_fenologica_tipo_cultivo_id', $etapaFenologicaTipoCultivo->id)
            ->where('variable', $variable)
            ->first();

        if ($item) {
            return response()->json($item);
        } else {
            $item = NutricionEtapaFenologicaTipoCultivo::create([
                'etapa_fenologica_tipo_cultivo_id' => $etapaFenologicaTipoCultivo->id,
                'variable' => $variable,
                'min' => null,
                'optimo_min' => null,
                'optimo_max' => null,
                'max' => null,
            ]);
            return response()->json($item);
        }
    }

    public function store(Request $request)
    {
        // Usar firstOrCreate para evitar duplicados
        $etapaFenologicaTipoCultivo = EtapaFenologicaTipoCultivo::firstOrCreate([
            'tipo_cultivo_id' => $request->tipo_cultivo_id,
            'etapa_fenologica_id' => $request->etapa_fenologica_id,
        ]);

        // Buscar si ya existe un registro con esta variable para esta etapa
        $existingItem = NutricionEtapaFenologicaTipoCultivo::where('etapa_fenologica_tipo_cultivo_id', $etapaFenologicaTipoCultivo->id)
            ->where('variable', $request->variable)
            ->first();

        if ($existingItem) {
            // Si existe, actualizar el registro existente
            $existingItem->min = $request->min;
            $existingItem->optimo_min = $request->optimo_min;
            $existingItem->optimo_max = $request->optimo_max;
            $existingItem->max = $request->max;
            $existingItem->save();

            return response()->json($existingItem, 200);
        } else {
            // Si no existe, crear uno nuevo
            $item = new NutricionEtapaFenologicaTipoCultivo();
            $item->etapa_fenologica_tipo_cultivo_id = $etapaFenologicaTipoCultivo->id;
            $item->variable = $request->variable;
            $item->min = $request->min;
            $item->optimo_min = $request->optimo_min;
            $item->optimo_max = $request->optimo_max;
            $item->max = $request->max;
            $item->save();

            return response()->json($item, 201);
        }
    }

    public function update(Request $request, $nutricion)
    {
        $item = NutricionEtapaFenologicaTipoCultivo::findOrFail($nutricion);
        $data = $request->validate([
            'etapa_fenologica_tipo_cultivo_id' => 'sometimes|integer|exists:etapa_fenologica_tipo_cultivo,id',
            'variable' => 'sometimes|string',
            'min' => 'nullable',
            'optimo_min' => 'nullable',
            'optimo_max' => 'nullable',
            'max' => 'nullable',
        ]);

        $item->min = $data['min'];
        $item->optimo_min = $data['optimo_min'];
        $item->optimo_max = $data['optimo_max'];
        $item->max = $data['max'];
        $item->save();

        return response()->json($item);
    }

    public function destroy($nutricion)
    {
        $item = NutricionEtapaFenologicaTipoCultivo::findOrFail($nutricion);
        $item->delete();
        return response()->json(null, 204);
    }

    public function buscar(Request $request)
    {
        $etapa_fenologica_id = $request->query('etapa_fenologica_id');
        $tipo_cultivo_id = $request->query('tipo_cultivo_id');
        $variable = $request->query('variable');

        // Busca el id de la tabla pivote
        $etapaFenologicaTipoCultivo = EtapaFenologicaTipoCultivo::where('tipo_cultivo_id', $tipo_cultivo_id)
            ->where('etapa_fenologica_id', $etapa_fenologica_id)
            ->first();

        // Si no existe la relación pivote, devuelve objeto vacío con id null
        if (!$etapaFenologicaTipoCultivo) {
            return response()->json([
                'id' => null,
                'etapa_fenologica_tipo_cultivo_id' => null,
                'tipo_cultivo_id' => $tipo_cultivo_id,
                'variable' => $variable,
                'min' => '',
                'optimo_min' => '',
                'optimo_max' => '',
                'max' => ''
            ]);
        }

        // Busca el registro de nutrición usando el id de la pivote
        $item = NutricionEtapaFenologicaTipoCultivo::where('etapa_fenologica_tipo_cultivo_id', $etapaFenologicaTipoCultivo->id)
            ->where('variable', $variable)
            ->first();

        if ($item) {
            return response()->json($item);
        } else {
            return response()->json([
                'id' => null,
                'etapa_fenologica_tipo_cultivo_id' => $etapaFenologicaTipoCultivo->id,
                'tipo_cultivo_id' => $tipo_cultivo_id,
                'variable' => $variable,
                'min' => '',
                'optimo_min' => '',
                'optimo_max' => '',
                'max' => ''
            ]);
        }
    }
}
