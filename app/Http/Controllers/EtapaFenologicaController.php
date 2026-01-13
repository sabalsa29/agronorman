<?php

namespace App\Http\Controllers;

use App\Models\EtapaFenologica;
use Illuminate\Http\Request;

class EtapaFenologicaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $list = EtapaFenologica::where('status', 1)->whereNotNull('nombre')->get();
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

        return redirect()->route('etapasfenologicas.index')->with('success', 'Etapa Fenologica actualizada correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(EtapaFenologica $etapasfenologica)
    {
        $etapasfenologica->delete();
        return redirect()->route('etapasfenologicas.index')->with('success', 'Etapa Fenologica eliminada correctamente.');
    }
}
