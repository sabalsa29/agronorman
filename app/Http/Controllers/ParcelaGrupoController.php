<?php

namespace App\Http\Controllers;

use App\Models\GrupoParcela;
use App\Models\Grupos;
use App\Models\Parcelas;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use PhpParser\Builder\Param;

class ParcelaGrupoController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Obtener grupos accesibles al usuario
        $grupos = Grupos::forUser($user)->get();

        // Las asignaciones deben mostrarse agrupadas por grupo
        $asignaciones = GrupoParcela::whereIn('grupo_id', $grupos->pluck('id'))
            ->with(['grupo.subgrupos', 'parcela'])
            ->get()
            ->groupBy(function ($item) {
                return ['id' => $item->grupo->id];
            });


        $estructuraJerarquica = collect();

        return view('parcela_grupos.index', [
            "section_name" => "Asignación de parcelas a grupos",
            "section_description" => "Asignar parcelas a los grupos.",
            "list" => $grupos, // Mantener para compatibilidad
            "estructuraJerarquica" => $estructuraJerarquica,
            "asignaciones" => $asignaciones,
        ]);

    }
    public function assign()
    {

    $user = auth()->user();

    $parcelas =  Parcelas::where('status', true)->get()
        ->map(function ($parcela) {
            return [
                'id' => $parcela->id,
                'nombre' => $parcela->nombre,
            ];
        });

        // Carga todos los grupos a excepción del grupo raíz "norman" si el usuario no es superadmin 
        $gruposDisponibles = Grupos::with('grupoPadre')
            ->forUser($user)
            ->where('is_root', false)
            ->get()
            ->map(function ($grupo) {
                return [
                    'id' => $grupo->id,
                    'nombre' => $grupo->ruta_completa,
                ];
            });

        // Lógica para mostrar el formulario de asignación de parcelas a grupos
        return view('parcela_grupos.assign', [
            "section_name" => "Asignar Parcelas a Grupos",
            "section_description" => "Asigne parcelas específicas a los grupos.",
            "gruposDisponibles" => $gruposDisponibles,
            'parcelas' => $parcelas,
        ]);
    }

    public function store(Request $request) 
{
    //dd( 'request recibido en store parcela grupo', $request->all() );
    // Normaliza grupos (name="grupo_id[]")
    $grupoIds = array_values(array_filter(Arr::wrap($request->input('grupo_id'))));

    $request->validate([
        'nombre'      => 'required|string|max:255',
        'cliente_id'  => 'required|exists:clientes,id',
        'superficie'  => 'required',
        // 'lat'         => 'required',
        // 'lon'         => 'required',
        'status'      => 'required|in:0,1',
        'grupo_id'    => 'required|array|min:1',
        'grupo_id.*'  => 'exists:grupos,id',
    ]);

    return DB::transaction(function () use ($request, $grupoIds) {

        // 1) Crear parcela
        $parcela = Parcelas::create([
            'cliente_id'  => $request->input('cliente_id'),
            'nombre'      => $request->input('nombre'),
            'superficie'  => $request->input('superficie'),
            'lat'         => $request->input('lat'),
            'lon'         => $request->input('lon'),
            'status'      => (int) $request->input('status'),
        ]);

        // 2) Crear relaciones parcela ↔ grupo
        foreach ($grupoIds as $grupoId) {
            GrupoParcela::firstOrCreate(
                [
                    'grupo_id'   => (int) $grupoId,
                    'parcela_id' => (int) $parcela->id,
                ],
                [
                    'user_id' => 0, // si no lo necesitas, déjalo en 0
                ]
            );
        }

        return redirect()
            ->route('parcelas.index', ['id' => $parcela->cliente_id]) // ajusta a tu ruta real
            ->with('success', 'Parcela creada y asignada a los grupos correctamente.');
    });
}
}
