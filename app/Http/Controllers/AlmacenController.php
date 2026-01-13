<?php

namespace App\Http\Controllers;

use App\Models\Almacen;
use Illuminate\Http\Request;

class AlmacenController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $list = Almacen::all();
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

        return redirect()->route('almacenes.index')->with('success', 'Almacen actualizado correctamente');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Almacen $almacene)
    {
        $almacene->delete();

        return redirect()->route('almacenes.index')->with('success', 'Almacen eliminado correctamente');
    }
}
