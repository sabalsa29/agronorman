<?php

namespace App\Http\Controllers;

use App\Models\TipoEstacion;
use Illuminate\Http\Request;

class TipoEstacionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Se cargan las etapas fenologicas con su estatus
        $list = TipoEstacion::all();
        return view('tipo_estacion.index', [
            "section_name" => "Tipo de Estación",
            "section_description" => "Listado de tipos de estaciones",
            "list" => $list,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('tipo_estacion.create', [
            "section_name" => "Tipo de Estación",
            "section_description" => "Crear nuevo tipo de estación",
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $tipoEstacion = new TipoEstacion();
        $tipoEstacion->nombre = $request->nombre;
        $tipoEstacion->tipo_nasa = $request->tipo_nasa;
        $tipoEstacion->status = $request->status;
        $tipoEstacion->save();

        return redirect()->route('tipo_estacion.index')->with('success', 'Tipo de estación creada correctamente.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TipoEstacion $tipo_estacion)
    {
        return view('tipo_estacion.edit', [
            "section_name" => "Tipo de Estación",
            "section_description" => "Editar tipo de estación",
            "tipoEstacion" => $tipo_estacion,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TipoEstacion $tipo_estacion)
    {
        $tipo_estacion->nombre = $request->nombre;
        $tipo_estacion->tipo_nasa = $request->tipo_nasa;
        $tipo_estacion->status = $request->status;
        $tipo_estacion->save();

        return redirect()->route('tipo_estacion.index')->with('success', 'Tipo de estación actualizada correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TipoEstacion $tipo_estacion)
    {
        $tipo_estacion->delete();
        return redirect()->route('tipo_estacion.index')->with('success', 'Tipo de estación eliminada correctamente.');
    }
}
