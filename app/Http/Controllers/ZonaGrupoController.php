<?php

namespace App\Http\Controllers;

use App\Models\Grupos;
use App\Models\GrupoZonaManejo;
use App\Models\Parcelas;
use App\Models\ZonaManejos;
use Illuminate\Http\Request;

class ZonaGrupoController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Obtener grupos accesibles al usuario
        $grupos = Grupos::forUser($user)->get();

        // Las asignaciones deben mostrarse agrupadas por grupo
        $asignaciones = GrupoZonaManejo::whereIn('grupo_id', $grupos->pluck('id'))
            ->with(['grupo.subgrupos', 'zona_manejo'])
            ->get()
            ->groupBy(function ($item) {
                return ['id' => $item->grupo->id];
            });


        $estructuraJerarquica = collect();

        return view('zona_grupos.index', [
            "section_name" => "Asignación de zonas a grupos",
            "section_description" => "Asignar zonas a los grupos.",
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

    $zonas =  ZonaManejos::where('status', true)->get()
        ->map(function ($zona) {
            return [
                'id' => $zona->id,
                'nombre' => $zona->nombre,
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

        // Lógica para mostrar el formulario de asignación de zonas a grupos
        return view('zona_grupos.assign', [
            "section_name" => "Asignar Zonas a Grupos",
            "section_description" => "Asigne zonas específicas a los grupos.",
            "gruposDisponibles" => $gruposDisponibles,
            'zonas' => $zonas,
            'parcelas' => $parcelas,
        ]);
    }

    public function zonasByPredio(Request $request)
    {
        $predioIds = $request->input('predio_ids', []);

        //dd($predioIds);

        // Normalizar a array
        if (!is_array($predioIds)) {
            $predioIds = explode(',', (string) $predioIds);
        }

        $predioIds = collect($predioIds)
            ->filter()
            ->map(fn($v) => (int)$v)
            ->unique()
            ->values()
            ->toArray();

        if (count($predioIds) === 0) {
            return response()->json([]);
        }

        // Traer nombres de predios en un solo query
        $prediosMap = Parcelas::whereIn('id', $predioIds)
            ->get(['id', 'nombre'])
            ->keyBy('id');

        $zonas = ZonaManejos::query()
            ->whereIn('parcela_id', $predioIds)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'parcela_id']);

        $response = $zonas->map(function ($zona) use ($prediosMap) {
            $predio = $prediosMap->get($zona->parcela_id);

            return [
                'id' => $zona->id,
                'nombre' => $zona->nombre,
                'predio_id' => $zona->parcela_id,
                'predio' => [
                    'id' => $zona->parcela_id,
                    'nombre' => $predio?->nombre ?? '(Sin predio)',
                ],
            ];
        });

        return response()->json($response->values());
    }

    public function store(Request $request)
    {

        $request->validate([
            'grupo_id' => 'required|exists:grupos,id',
            'zona_manejo_ids' => 'required|array|min:1',
            'zona_manejo_ids.*' => 'exists:zona_manejos,id',
        ]); 

        //dd( $request->all());

        $grupoId = $request->input('grupo_id');
        $zonaManejoIds = $request->input('zona_manejo_ids');
        foreach ($zonaManejoIds as $zonaManejoId) {
            GrupoZonaManejo::firstOrCreate([
                'grupo_id' => $grupoId,
                'zona_manejo_id' => $zonaManejoId,
            ]);
        }
        return redirect()->route('accesos.zonas.index')
            ->with('success', 'Zonas asignadas al grupo correctamente.');

    }   
    
}