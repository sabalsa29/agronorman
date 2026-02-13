<?php

namespace App\Http\Controllers;

use App\Models\Cultivo;
use App\Models\EtapaFenologica;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Traits\LogsPlatformActions;

class CultivoController extends Controller
{
    use LogsPlatformActions;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $cultivos = Cultivo::all();
        // Crear log de visualización de la lista de cultivos
        $this->logPlatformAction(
            seccion: 'cultivos',
            accion: 'visualizar_lista',
            entidadTipo: 'Cultivo',
            descripcion: 'Visualización de la lista de cultivos',
        );
        return view('cultivos.index', [
            "section_name" => "Cultivos",
            "section_description" => "Cultivos de las plantas",
            "list" => compact('cultivos'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('cultivos.create', [
            "section_name" => "Cultivos",
            "section_description" => "Crear cultivo"
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Cultivo $cultivo)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Cultivo $cultivo)
    {
        $list = EtapaFenologica::where('status', 1)->whereNotNull('nombre')->get();
        return view('cultivos.edit', [
            "section_name" => "Cultivos",
            "section_description" => "Editar cultivo",
            'etapas_fenologicas' => $list,
            'cultivo' => $cultivo,
            'cultivo_id' => $cultivo->id,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Cultivo $cultivo)
    {
        // Validar los campos
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'temp_base_calor' => 'nullable|numeric',
            'tipo_vida' => 'nullable|integer',
            'imagen' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'icono' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'etapas_fenologicas' => 'nullable|array',
            'etapas_fenologicas.*' => 'exists:etapa_fenologicas,id',
        ]);

        // Actualizar campos simples
        $cultivo->nombre = $validated['nombre'];
        $cultivo->descripcion = $validated['descripcion'] ?? null;
        $cultivo->temp_base_calor = $validated['temp_base_calor'] ?? null;
        $cultivo->tipo_vida = $validated['tipo_vida'] ?? null;

        // Manejar imagen
        if ($request->hasFile('imagen')) {
            if ($cultivo->imagen && Storage::disk('public')->exists($cultivo->imagen)) {
                Storage::disk('public')->delete($cultivo->imagen);
            }
            $path = $request->file('imagen')->store('cultivos/imagenes', 'public');
            $cultivo->imagen = $path;
        }

        // Manejar icono
        if ($request->hasFile('icono')) {
            if ($cultivo->icono && Storage::disk('public')->exists($cultivo->icono)) {
                Storage::disk('public')->delete($cultivo->icono);
            }
            $path = $request->file('icono')->store('cultivos/iconos', 'public');
            $cultivo->icono = $path;
        }

        $cultivo->save();

        // CrEar log de actualización de cultivo
        $this->logPlatformAction(
            seccion: 'cultivos',
            accion: 'actualizar',
            entidadTipo: 'Cultivo',
            descripcion: "Actualización del cultivo '{$cultivo->nombre}' (ID: {$cultivo->id})",
            entidadId: $cultivo->id,
            datosAdicionales: [
                'nombre' => $cultivo->nombre,
                'descripcion' => $cultivo->descripcion,
                'temp_base_calor' => $cultivo->temp_base_calor,
                'tipo_vida' => $cultivo->tipo_vida,
                'imagen' => $cultivo->imagen,
                'icono' => $cultivo->icono,
                'etapas_fenologicas' => $request->input('etapas_fenologicas', []),
            ]
        );

        return redirect()->route('cultivos.edit', $cultivo)->with('success', 'Cultivo actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Cultivo $cultivo)
    {
        $cultivo->delete();
        return redirect()->route('cultivos.index')->with('success', 'Cultivo eliminado correctamente.');
    }
}
