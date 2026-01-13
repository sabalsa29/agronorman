<?php

namespace App\Http\Controllers;

use App\Models\Almacen;
use App\Models\Cliente;
use App\Models\Estaciones;
use App\Models\Fabricante;
use App\Models\TipoEstacion;
use App\Models\VariablesMedicion;
use Illuminate\Http\Request;

class EstacionesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Precargamos las relaciones necesarias, incluyendo la pivote y sus joins
        $registers = Estaciones::with(['tipo_estacion', 'fabricante', 'almacen', 'virtuales.parcela.cliente'])->get()
            ->map(function (Estaciones $est) {
                // Filtramos sólo virtuales cuyo usuario está activo
                $validos = $est->virtuales
                    ->filter(fn($ev) => $ev->parcela?->cliente?->status);

                // Construimos los campos zona y donde
                $est->zona  = $validos->pluck('nombre')->unique()->implode(',');
                $est->donde = $validos
                    ->map(fn($ev) => $ev->parcela->cliente->nombre)
                    ->unique()
                    ->implode(',');

                return $est;
            });

        return view('estaciones.index', [
            'section_name'        => 'Estaciones de Medición',
            'section_description' => 'Estaciones de Medición',
            'list'                => $registers,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $fabricantes = Fabricante::where('status', 1)->get();
        $tipos = TipoEstacion::orderBy('nombre', 'asc')->get();
        $clientes = Cliente::orderBy('nombre', 'asc')->get();
        $almacenes = Almacen::orderBy('nombre', 'asc')->get();
        $variables = VariablesMedicion::orderBy('nombre', 'asc')->get();
        return view('estaciones.create', [
            "section_name" => "Crear Estación de Medición",
            "section_description" => "Crear Estación de Medición",
            "fabricantes" => $fabricantes,
            "tipos" => $tipos,
            'estatusOptions' => Estaciones::getEstatusOptions(),
            "clientes" => $clientes,
            "almacenes" => $almacenes,
            "variables" => $variables,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $estacion = Estaciones::create($request->all());
        $variables = $request->input('variables_medicion_id', []);
        $estacion->variables()->sync($variables);
        $estacion->save();
        return redirect()->route('estaciones.index')->with('success', 'Estación de Medición creada correctamente.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Estaciones $estacione)
    {
        $estacion = Estaciones::with(['tipo_estacion', 'fabricante', 'almacen', 'virtuales.parcela.cliente'])->where('estaciones.id', $estacione->id)->first();

        $fabricantes = Fabricante::where('status', 1)->get();
        $tipos = TipoEstacion::orderBy('nombre', 'asc')->get();
        $clientes = Cliente::orderBy('nombre', 'asc')->get();
        $almacenes = Almacen::orderBy('nombre', 'asc')->get();
        $variables = VariablesMedicion::orderBy('nombre', 'asc')->get();

        return view('estaciones.edit', [
            "section_name" => "Editar Estación de Medición",
            "section_description" => "Editar Estación de Medición",
            "estacion" => $estacion,
            "fabricantes" => $fabricantes,
            "tipos" => $tipos,
            'estatusOptions' => Estaciones::getEstatusOptions(),
            "clientes" => $clientes,
            "almacenes" => $almacenes,
            'variables' => $variables,
        ]);
    }

    public function show(Estaciones $estacione)
    {
        $estacion = Estaciones::with(['tipo_estacion', 'fabricante', 'almacen', 'virtuales.parcela.cliente'])->where('estaciones.id', $estacione->id)->first();
        return view('estaciones.show', [
            "section_name" => "Ver Estación de Medición",
            "section_description" => "Ver Estación de Medición",
            "estacion" => $estacion,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Estaciones $estacione)
    {
        $estacione->update($request->all());
        $variables = $request->input('variables_medicion_id', []);
        $estacione->variables()->sync($variables);
        $estacione->save();
        return redirect()->route('estaciones.index')->with('success', 'Estación de Medición actualizada correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Estaciones $estacione)
    {
        $estacione->delete();
        return redirect()->route('estaciones.index')->with('success', 'Estación de Medición eliminada correctamente.');
    }
}
