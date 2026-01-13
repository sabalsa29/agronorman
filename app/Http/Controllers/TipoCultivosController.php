<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTipoCultivosRequest;
use App\Http\Requests\UpdateTipoCultivosRequest;
use App\Models\Cultivo;
use App\Models\Enfermedades;
use App\Models\EtapaFenologica;
use App\Models\Plaga;
use App\Models\TipoCultivos;
use Illuminate\Http\Request;

class TipoCultivosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $cultivo = Cultivo::find($request->id);
        $tipoCultivos = TipoCultivos::where('cultivo_id', $cultivo->id)->get();
        return view('cultivos.tipo_cultivos.index', [
            "section_name" => "Tipo de cultivos del " . $cultivo->nombre,
            "section_description" => "Cultivos de las plantas",
            "list" => $tipoCultivos,
            "cultivo_id" => $cultivo->id,
        ]);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $cultivo = Cultivo::find($request->id);
        $EtapaFenologica = EtapaFenologica::where('status', 1)->whereNotNull('nombre')->get();
        $plagas = Plaga::whereNotNull('nombre')->get();
        $enfermedades = Enfermedades::where('status', 1)->whereNotNull('nombre')->get();
        return view('cultivos.tipo_cultivos.create', [
            "section_name" => "Crear tipo de cultivo de " . $cultivo->nombre,
            "section_description" => "Cultivos de las plantas",
            "cultivo_id" => $cultivo->id,
            "etapas_fenologicas" => $EtapaFenologica,
            "plagas" => $plagas,
            "enfermedades" => $enfermedades,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTipoCultivosRequest $request)
    {
        $tipoCultivos = TipoCultivos::create($request->all());
        $enfermedad_id = $request->input('enfermedad_id', []);
        $tipoCultivos->enfermedades()->sync($enfermedad_id);
        $plaga_id = $request->input('plaga_id', []);
        $tipoCultivos->plagas()->sync($plaga_id);
        $etapa_fenologica_id = $request->input('etapa_fenologica_id', []);
        $tipoCultivos->etapas_fenologicas()->sync($etapa_fenologica_id);
        $tipoCultivos->save();
        return redirect()->route('tipo_cultivos.index', ['id' => $request->cultivo_id])->with('success', 'Tipo de cultivo creado correctamente');
    }

    /**
     * Display the specified resource.
     */
    public function show(TipoCultivos $tipo_cultivo)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, $id, TipoCultivos $tipo_cultivo)
    {
        $cultivo = Cultivo::find($id);
        $EtapaFenologica = EtapaFenologica::where('status', 1)->whereNotNull('nombre')->get();
        $plagas = Plaga::whereNotNull('nombre')->get();
        $enfermedades = Enfermedades::where('status', 1)->whereNotNull('nombre')->get();

        // Cargar relaciones necesarias
        $tipo_cultivo->load(['etapas_fenologicas', 'plagas', 'enfermedades']);

        return view('cultivos.tipo_cultivos.edit', [
            "section_name" => "Editar tipo de cultivo de " . $cultivo->nombre,
            "section_description" => "Cultivos de las plantas",
            "cultivo_id" => $cultivo->id,
            "etapas_fenologicas" => $EtapaFenologica,
            "plagas" => $plagas,
            "enfermedades" => $enfermedades,
            "tipo_cultivo" => $tipo_cultivo,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTipoCultivosRequest $request, $id, TipoCultivos $tipo_cultivo)
    {
        $tipo_cultivo->update($request->all());
        $enfermedad_id = $request->input('enfermedad_id', []);
        $tipo_cultivo->enfermedades()->sync($enfermedad_id);
        $plaga_id = $request->input('plaga_id', []);
        $tipo_cultivo->plagas()->sync($plaga_id);
        $etapa_fenologica_id = $request->input('etapa_fenologica_id', []);
        $tipo_cultivo->etapas_fenologicas()->sync($etapa_fenologica_id);
        return redirect()->route('tipo_cultivos.index', ['id' => $request->cultivo_id])->with('success', 'Tipo de cultivo actualizado correctamente');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id, TipoCultivos $tipo_cultivo)
    {
        $tipo_cultivo->delete();
        return redirect()->route('tipo_cultivos.index', ['id' => $id])->with('success', 'Tipo de cultivo eliminado correctamente');
    }
}
