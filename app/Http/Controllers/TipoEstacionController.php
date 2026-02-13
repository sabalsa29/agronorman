<?php

namespace App\Http\Controllers;

use App\Models\TipoEstacion;
use Illuminate\Http\Request;
use App\Traits\LogsPlatformActions;

class TipoEstacionController extends Controller
{
    use LogsPlatformActions;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Se cargan las etapas fenologicas con su estatus
        $list = TipoEstacion::all();
        // crear log
        $this->logPlatformAction(
            seccion: 'tipo_estaciones',
            accion: 'ver',
            entidadTipo: 'TipoEstacion',
            descripcion: 'Visualización de la lista de tipos de estaciones',
        );

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

        // crear log
        $this->logPlatformAction(
            seccion: 'tipo_estaciones',
            accion: 'crear',
            entidadTipo: 'TipoEstacion',
            descripcion: "Creación del tipo de estación '{$tipoEstacion->nombre}' (ID: {$tipoEstacion->id})",
            entidadId: $tipoEstacion->id,
            datosAdicionales: [
                'nombre' => $tipoEstacion->nombre,
                'tipo_nasa' => $tipoEstacion->tipo_nasa,
                'status' => $tipoEstacion->status,
            ]
        );

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

        // crear log
        $this->logPlatformAction(
            seccion: 'tipo_estaciones',
            accion: 'actualizar',
            entidadTipo: 'TipoEstacion',
            descripcion: "Actualización del tipo de estación '{$tipo_estacion->nombre}' (ID: {$tipo_estacion->id})",
            entidadId: $tipo_estacion->id,
            datosAdicionales: [
                'nombre' => $tipo_estacion->nombre,
                'tipo_nasa' => $tipo_estacion->tipo_nasa,
                'status' => $tipo_estacion->status,
            ]
        );

        return redirect()->route('tipo_estacion.index')->with('success', 'Tipo de estación actualizada correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TipoEstacion $tipo_estacion)
    {
            // crear log
            $this->logPlatformAction(
                seccion: 'tipo_estaciones',
                accion: 'eliminar',
                entidadTipo: 'TipoEstacion',
                descripcion: "Eliminación del tipo de estación '{$tipo_estacion->nombre}' (ID: {$tipo_estacion->id})",
                entidadId: $tipo_estacion->id,
                datosAdicionales: [
                    'nombre' => $tipo_estacion->nombre,
                    'tipo_nasa' => $tipo_estacion->tipo_nasa,
                    'status' => $tipo_estacion->status,
                ]
            );
        $tipo_estacion->delete();
        return redirect()->route('tipo_estacion.index')->with('success', 'Tipo de estación eliminada correctamente.');
    }
}
