<?php

namespace App\Http\Controllers;

use App\Models\Almacen;
use Illuminate\Http\Request;
use App\Traits\LogsPlatformActions;


class AlmacenController extends Controller
{
    use LogsPlatformActions;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $list = Almacen::all();
        // crear log de visualización de la lista de almacenes
        $this->logPlatformAction(
            seccion: 'almacenes',
            accion: 'visualizar_lista',
            entidadTipo: 'Almacen',
            descripcion: 'Visualización de la lista de almacenes',
        );  

        return view('almacenes.index', [
            "section_name" => "Almacenes",
            "section_description" => "Listado de almacenes",
            "list" => $list,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('almacenes.create', [
            "section_name" => "Almacenes",
            "section_description" => "Crear almacenes",
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $almacen = new Almacen();
        $almacen->nombre = $request->nombre;
        $almacen->status = $request->boolean('status');
        $almacen->save();

        // crear log
        $this->logPlatformAction(
            seccion: 'almacenes',
            accion: 'crear',
            entidadTipo: 'Almacen',
            descripcion: "Creación del almacén '{$almacen->nombre}' (ID: {$almacen->id})",
            entidadId: $almacen->id,
            datosAdicionales: [
                'nombre' => $almacen->nombre,
                'status' => $almacen->status,
            ]
        );  

        return redirect()->route('almacenes.index')->with('success', 'Almacen creado correctamente');
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Almacen $almacene)
    {
        return view('almacenes.edit', [
            "section_name" => "Almacenes",
            "section_description" => "Editar almacenes",
            "almacen" => $almacene,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Almacen $almacene)
    {
        $almacene->nombre = $request->nombre;
        $almacene->status = $request->boolean('status');
        $almacene->save();

        // crear log
        $this->logPlatformAction(
            seccion: 'almacenes',
            accion: 'actualizar',
            entidadTipo: 'Almacen', 
            descripcion: "Actualización del almacén '{$almacene->nombre}' (ID: {$almacene->id})",
            entidadId: $almacene->id,
            datosAdicionales: [
                'nombre' => $almacene->nombre,
                'status' => $almacene->status,
            ]
        );  

        return redirect()->route('almacenes.index')->with('success', 'Almacen actualizado correctamente');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Almacen $almacene)
    {
            // crear log
            $this->logPlatformAction(
                seccion: 'almacenes',
                accion: 'eliminar',
                entidadTipo: 'Almacen', 
                descripcion: "Eliminación del almacén '{$almacene->nombre}' (ID: {$almacene->id})",
                entidadId: $almacene->id,
                datosAdicionales: [
                    'nombre' => $almacene->nombre,
                    'status' => $almacene->status,
                ]
            );
        $almacene->delete();

        return redirect()->route('almacenes.index')->with('success', 'Almacen eliminado correctamente');
    }
}
