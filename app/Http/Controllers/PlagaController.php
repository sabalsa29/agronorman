<?php

namespace App\Http\Controllers;

use App\Models\Cultivo;
use App\Models\Plaga;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Traits\LogsPlatformActions;

class PlagaController extends Controller
{
    use LogsPlatformActions;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Cargamos las plagas y sus especies relacionadas mediante Eloquent
        $plagues = Plaga::with('cultivos')->get();

        foreach ($plagues as $plaga) {
            $plaga->nombres_cultivos = $plaga->cultivos->pluck('nombre')->implode(', ');
        }

        // Crear log de visualización de la lista de plagas
        $this->logPlatformAction(
            seccion: 'plagas',
            accion: 'visualizar_lista',
            entidadTipo: 'Plaga',
            descripcion: 'Visualización de la lista de plagas',
        );

        return view('plagas.index', [
            "section_name" => "Plagas",
            "section_description" => "Plagas de las plantas",
            "list" => compact('plagues'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $cultivos = Cultivo::all();
        return view('plagas.create', [
            "section_name" => "Plagas",
            "section_description" => "Crear plaga",
            'cultivos' => $cultivos,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Crea la plaga. Ajusta los campos según tu tabla.
        $plaga = Plaga::create([
            'nombre' => $request['nombre'],
            'slug'   => Str::slug($request['nombre']),
            'descripcion' => $request['descripcion'],
            'unidades_calor_ciclo' => $request['unidades_calor_ciclo'],
            'umbral_min' => $request['umbral_min'],
            'umbral_max' => $request['umbral_max'],
            'imagen' => $request['imagen'] ?? '',
        ]);

        $plaga->cultivos()->sync($request['cultivo_id']);

            // Crear log de creación de plaga
            $this->logPlatformAction(
                seccion: 'plagas',
                accion: 'crear',
                entidadTipo: 'Plaga',
                descripcion: "Creación de la plaga '{$plaga->nombre}'",
                entidadId: $plaga->id,
                datosAdicionales: [
                    'nombre' => $plaga->nombre,
                    'descripcion' => $plaga->descripcion,
                    'unidades_calor_ciclo' => $plaga->unidades_calor_ciclo,
                    'umbral_min' => $plaga->umbral_min,
                    'umbral_max' => $plaga->umbral_max,
                    'cultivo_id' => $request['cultivo_id'],
                ]
            );

        // Redirecciona a la ruta que prefieras (ajusta según tu routes/web.php)
        return redirect()->route('plaga.index')->with('success', 'Plaga creada correctamente.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Plaga $plaga)
    {
        $cultivos = Cultivo::all();
        $selectedCultivos = $plaga->cultivos()->pluck('cultivo_id')->toArray();
        return view('plagas.edit', [
            "section_name" => "Plagas",
            "section_description" => "Editar plaga",
            'plaga' => $plaga,
            'cultivos' => $cultivos,
            'selectedCultivos' => $selectedCultivos
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Plaga $plaga)
    {

        // Actualiza los campos de la plaga.
        $plaga->update([
            'nombre' => $request['nombre'],
            'slug'   => Str::slug($request['nombre']),
            'descripcion' => $request['descripcion'],
            'unidades_calor_ciclo' => $request['unidades_calor_ciclo'],
            'umbral_min' => $request['umbral_min'],
            'umbral_max' => $request['umbral_max'],
        ]);

        // Relaciona la plaga con las especies, sin usar foreach.
        // 'sync' se encargará de insertar en la tabla pivote.
        $plaga->cultivos()->sync($request['cultivo_id']);

            // Crear log de actualización de plaga
            $this->logPlatformAction(
                seccion: 'plagas',
                accion: 'actualizar',
                entidadTipo: 'Plaga',
                descripcion: "Actualización de la plaga '{$plaga->nombre}' (ID: {$plaga->id})",
                entidadId: $plaga->id,
                datosAdicionales: [
                    'nombre' => $plaga->nombre,
                    'descripcion' => $plaga->descripcion,
                    'unidades_calor_ciclo' => $plaga->unidades_calor_ciclo,
                    'umbral_min' => $plaga->umbral_min,
                    'umbral_max' => $plaga->umbral_max,
                    'cultivo_id' => $request['cultivo_id'],
                ]
            );

        // Redirecciona a la ruta que prefieras (ajusta según tu routes/web.php)
        return redirect()->route('plaga.index')->with('success', 'Plaga actualizada correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Plaga $plaga)
    {
        // Crear log de eliminación de plaga
        $this->logPlatformAction(
            seccion: 'plagas',
            accion: 'eliminar',
            entidadTipo: 'Plaga',
            descripcion: "Eliminación de la plaga '{$plaga->nombre}' (ID: {$plaga->id})",
            entidadId: $plaga->id,
            datosAdicionales: [
                'nombre' => $plaga->nombre,
                'descripcion' => $plaga->descripcion,
                'unidades_calor_ciclo' => $plaga->unidades_calor_ciclo,
                'umbral_min' => $plaga->umbral_min,
                'umbral_max' => $plaga->umbral_max,
                'cultivo_id' => $plaga->cultivos()->pluck('cultivo_id')->toArray(),
            ]
        );

        // Elimina la plaga
        $plaga->delete();
        // Redirecciona (ajusta la ruta según tu archivo de rutas)
        return redirect()->route('plaga.index')->with('success', 'Plaga eliminada correctamente.');
    }
}
