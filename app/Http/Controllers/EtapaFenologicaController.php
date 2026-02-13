<?php

namespace App\Http\Controllers;

use App\Models\EtapaFenologica;
use Illuminate\Http\Request;
use App\Traits\LogsPlatformActions;

class EtapaFenologicaController extends Controller
{
    use LogsPlatformActions;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $list = EtapaFenologica::where('status', 1)->whereNotNull('nombre')->get();
        // crear log de visualización de la lista de etapas fenologicas
        $this->logPlatformAction(
            seccion: 'etapas_fenologicas',
            accion: 'visualizar_lista',
            entidadTipo: 'EtapaFenologica',
            descripcion: 'Visualización de la lista de etapas fenologicas',
        );
        return view('etapasfenologicas.index', [
            "section_name" => "Etapas Fenologicas",
            "section_description" => "Etapas Fenologicas de las plantas",
            "list" => $list,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('etapasfenologicas.create', [
            "section_name" => "Etapas Fenologicas",
            "section_description" => "Crear Etapa Fenologica",
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
        ]);

        EtapaFenologica::create($request->all());

            // crear log de creación de etapa fenologica
            $this->logPlatformAction(
                seccion: 'etapas_fenologicas',
                accion: 'crear',
                entidadTipo: 'EtapaFenologica',
                descripcion: "Creación de la etapa fenológica '{$request->nombre}'",
                datosAdicionales: [
                    'nombre' => $request->nombre,
                ]
            );

        return redirect()->route('etapasfenologicas.index')->with('success', 'Etapa Fenologica creada correctamente.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(EtapaFenologica $etapasfenologica)
    {
        return view('etapasfenologicas.edit', [
            "section_name" => "Etapas Fenologicas",
            "section_description" => "Editar Etapa Fenologica",
            "etapa" => $etapasfenologica,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, EtapaFenologica $etapasfenologica)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
        ]);

        $etapasfenologica->update($request->all());

            // crear log de actualización de etapa fenologica
            $this->logPlatformAction(
                seccion: 'etapas_fenologicas',
                accion: 'actualizar',
                entidadTipo: 'EtapaFenologica',
                descripcion: "Actualización de la etapa fenológica '{$etapasfenologica->nombre}' (ID: {$etapasfenologica->id})",
                entidadId: $etapasfenologica->id,
                datosAdicionales: [
                    'nombre' => $request->nombre,
                ]
            );

        return redirect()->route('etapasfenologicas.index')->with('success', 'Etapa Fenologica actualizada correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EtapaFenologica $etapasfenologica)
    {
        // crear log de eliminación de etapa fenologica
        $this->logPlatformAction(
            seccion: 'etapas_fenologicas',
            accion: 'eliminar',
            entidadTipo: 'EtapaFenologica',
            descripcion: "Eliminación de la etapa fenológica '{$etapasfenologica->nombre    }' (ID: {$etapasfenologica->id})",
            entidadId: $etapasfenologica->id,
            datosAdicionales: [
                'nombre' => $etapasfenologica->nombre,
            ]
        );  
        $etapasfenologica->delete();
        return redirect()->route('etapasfenologicas.index')->with('success', 'Etapa Fenologica eliminada correctamente.');
    }
}
