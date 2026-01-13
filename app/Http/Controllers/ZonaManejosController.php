<?php

namespace App\Http\Controllers;

use App\Models\Cultivo;
use App\Models\Estaciones;
use App\Models\Grupos;
use App\Models\TipoSuelo;
use App\Models\User;
use App\Models\ZonaManejos;
use App\Models\ZonaManejosUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ZonaManejosController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Filtrar zonas de manejo según el usuario autenticado
        $user = Auth::check() ? Auth::user() : null;
        $zonaManejos = ZonaManejos::where('parcela_id', $request->parcela_id)
            ->forUser($user)
            ->get();

        $parcelaNombre = $zonaManejos->isNotEmpty() ? $zonaManejos[0]->parcela->nombre : '';
        return view('clientes.parcelas.zona_manejo.index', [
            "section_name" => "Lista de zonas de la parcela " . $parcelaNombre,
            "section_description" => "Zonas de la parcela",
            "list" => $zonaManejos,
            "parcela_id" => $request->parcela_id,
            "cliente_id" => $request->id,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $cultivos = Cultivo::all();
        $tipo_suelo = TipoSuelo::all();

        // Obtener grupos disponibles según el usuario (solo los que puede ver)
        $user = Auth::check() ? Auth::user() : null;
        $gruposDisponibles = Grupos::with('grupoPadre')
            ->forUser($user)
            ->get()
            ->map(function ($grupo) {
                return [
                    'id' => $grupo->id,
                    'nombre' => $grupo->ruta_completa,
                ];
            });

        return view('clientes.parcelas.zona_manejo.create', [
            "section_name" => "Crear zona de manejo",
            "section_description" => "Crear zona de manejo",
            "cultivos" => $cultivos,
            "tipo_suelo" => $tipo_suelo,
            "gruposDisponibles" => $gruposDisponibles,
            "parcela_id" => $request->parcela_id,
            "cliente_id" => $request->id,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $zona_manejo = new ZonaManejos();
        $zona_manejo->parcela_id = $request->parcela_id;
        $zona_manejo->grupo_id = $request->grupo_id ?: null;
        $zona_manejo->nombre = $request->nombre;
        $zona_manejo->temp_base_calor = $request->temp_base_calor;
        $zona_manejo->tipo_suelo_id = $request->tipo_suelo_id;
        $zona_manejo->fecha_inicial_uca = $request->fecha_inicial_uca;
        $zona_manejo->fecha_siembra = $request->fecha_siembra;
        $zona_manejo->save();

        $tipo_cultivo_id = $request->input('tipo_cultivo_id', []);
        $zona_manejo->tipoCultivos()->sync($tipo_cultivo_id);

        return redirect()->route('zona_manejo.index', ['id' => $request->cliente_id, 'parcela_id' => $request->parcela_id])
            ->with('success', 'Zona de manejo creada exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(ZonaManejos $ZonaManejos)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id, $parcela_id, ZonaManejos $zona_manejo)
    {
        $cultivos = Cultivo::all();
        $tipo_suelo = TipoSuelo::all();

        // Obtener grupos disponibles según el usuario (solo los que puede ver)
        $user = Auth::check() ? Auth::user() : null;
        $gruposDisponibles = Grupos::with('grupoPadre')
            ->forUser($user)
            ->get()
            ->map(function ($grupo) {
                return [
                    'id' => $grupo->id,
                    'nombre' => $grupo->ruta_completa,
                ];
            });

        return view('clientes.parcelas.zona_manejo.edit', [
            "section_name" => "Editar zona de manejo",
            "section_description" => "Editar zona de manejo",
            "cultivos" => $cultivos,
            "tipo_suelo" => $tipo_suelo,
            "gruposDisponibles" => $gruposDisponibles,
            "parcela_id" => $parcela_id,
            "cliente_id" => $id,
            'zona_manejo' => $zona_manejo,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id, $parcela_id, ZonaManejos $zona_manejo)
    {
        $zona_manejo->parcela_id = $request->parcela_id;
        $zona_manejo->grupo_id = $request->grupo_id ?: null;
        $zona_manejo->nombre = $request->nombre;
        $zona_manejo->temp_base_calor = $request->temp_base_calor;
        $zona_manejo->tipo_suelo_id = $request->tipo_suelo_id;
        $zona_manejo->fecha_inicial_uca = $request->fecha_inicial_uca;
        $zona_manejo->fecha_siembra = $request->fecha_siembra;
        $zona_manejo->save();

        $tipo_cultivo_id = $request->input('tipo_cultivo_id', []);
        $zona_manejo->tipoCultivos()->sync($tipo_cultivo_id);

        return redirect()->route('zona_manejo.index', ['id' => $id, 'parcela_id' => $parcela_id])
            ->with('success', 'Zona de manejo actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id, $parcela_id, ZonaManejos $zona_manejo)
    {
        $zona_manejo->delete();
        return redirect()->route('zona_manejo.index', ['id' => $id, 'parcela_id' => $parcela_id])
            ->with('success', 'Zona de manejo eliminada exitosamente.');
    }

    public function permissions($id, $parcela_id, ZonaManejos $zona_manejo)
    {

        $usuarios = User::where('cliente_id', $id)->get();
        $selectedUserIds = ZonaManejosUser::where('zona_manejo_id', $zona_manejo->id)->pluck('user_id')->toArray();
        return view('clientes.parcelas.zona_manejo.permissions', [
            "section_name" => "Permisos de la zona de manejo",
            "section_description" => "Permisos de la zona de manejo",
            "usuarios" => $usuarios,
            "selectedUserIds" => $selectedUserIds,
            "parcela_id" => $parcela_id,
            "cliente_id" => $id,
            'zona_manejo' => $zona_manejo,
        ]);
    }

    public function StoreZonaManejosUser(Request $request)
    {
        $zona_manejo = ZonaManejos::find($request->zona_manejo_id);
        $userIds = $request->input('user_id', []);
        $zona_manejo->users()->sync($userIds);

        return redirect()->route('zona_manejo.index', ['id' => $request->cliente_id, 'parcela_id' => $request->parcela_id])
            ->with('success', 'Zona de manejo creada exitosamente.');
    }
}
