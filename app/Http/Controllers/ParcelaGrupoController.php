<?php

namespace App\Http\Controllers;

use App\Models\GrupoParcela;
use App\Models\Grupos;
use App\Models\Parcelas;
use Illuminate\Http\Request;
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

        //dd($asignaciones);

        //dd($gruposRaiz);
         //$gruposRaiz->load('parcelas');
            

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

    //dd($parcelas);

        // Cargar grupos disponibles según el usuario (solo los que puede ver)
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

        //dd($gruposDisponibles);

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

        $request->validate([
            'grupo_id' => 'required|exists:grupos,id',
            'parcela_ids' => 'required|array|min:1',
            'parcela_ids.*' => 'exists:parcelas,id',
        ]);

        $grupo = Grupos::findOrFail($request->input('grupo_id'));
        $parcelaIds = $request->input('parcela_ids');

        //Foreach para crear las relaciones 
        foreach ($parcelaIds as $parcelaId) {
            //Validar que no exista la relacion
            $exists = GrupoParcela::where('grupo_id', $grupo->id)
                ->where('parcela_id', $parcelaId)
                ->exists();
            if ($exists) {
                continue; // Saltar si ya existe la relación
            }

            $grupoParcela = new GrupoParcela();
            $grupoParcela->grupo_id = $grupo->id;
            $grupoParcela->parcela_id = $parcelaId;
            $grupoParcela->save();
        }

        return redirect()->route('accesos.parcelas.index')
            ->with('success', 'Parcelas asignadas al grupo correctamente.');
    }
}
