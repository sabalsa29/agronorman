<?php

namespace App\Http\Controllers;

use App\Models\Fabricante;
use Illuminate\Http\Request;

class FabricanteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $fabricantes = Fabricante::all();
        return view('fabricantes.index', [
            "section_name" => "Fabricantes",
            "section_description" => "Fabricantes de estaciones meteorológicas",
            "list" => compact('fabricantes'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('fabricantes.create', [
            "section_name" => "Fabricantes",
            "section_description" => "Crear fabricante",
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $fabricante = new Fabricante();
        $fabricante->nombre = $request->nombre;
        $fabricante->status = 1;
        $fabricante->save();

        return redirect()->route('fabricantes.index')->with('success', 'Fabricante creado correctamente.');
    }


    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Fabricante $fabricante)
    {
        return view('fabricantes.edit', [
            "section_name" => "Fabricantes",
            "section_description" => "Editar fabricante",
            "fabricante" => $fabricante,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Fabricante $fabricante)
    {
        $fabricante->nombre = $request->nombre;
        $fabricante->status = $request->status;
        $fabricante->save();

        return redirect()->route('fabricantes.index')->with('success', 'Fabricante actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Fabricante $fabricante)
    {
        $fabricante->delete();
        return redirect()->route('fabricantes.index')->with('success', 'Fabricante eliminado correctamente.');
    }
}
