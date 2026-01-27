<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreParcelasRequest;
use App\Http\Requests\UpdateParcelasRequest;
use App\Models\GrupoParcela;
use App\Models\Grupos;
use App\Models\Parcelas;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ParcelasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $parcelas = Parcelas::where('cliente_id', $request->id)->get();
        $clienteNombre = $parcelas->isNotEmpty() ? $parcelas[0]->cliente->nombre : '';
        return view('clientes.parcelas.index', [
            "section_name" => "Lista de parcelas del usuario " . $clienteNombre,
            "section_description" => "Parcelas de los productores",
            "list" => $parcelas,
            "cliente_id" => $request->id,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */ 
    public function create(Request $request)
    {
        $user = auth()->user();

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

        return view('clientes.parcelas.create', [
            "section_name" => "Crear parcela",
            "section_description" => "Crear parcela",
            'cliente_id' => $request->id,
            'gruposDisponibles' => $gruposDisponibles,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreParcelasRequest $request)
    {
        //dd( 'request recibido en store parcelas', $request->all() );+

        $request->validate([
            'cliente_id'  => ['required','exists:clientes,id'],
            'nombre'      => ['required','string','max:255'],
            'superficie'  => ['required','numeric','min:0'],
            'lat'         => ['required','numeric','between:-90,90'],
            'lon'         => ['required','numeric','between:-180,180'],
            'status'      => ['required','in:0,1'],
            'grupo_id'   => ['required', 'array'],
            'grupo_id.*' => ['exists:grupos,id'],
        ]);

        $grupoIds = array_values(array_filter(Arr::wrap($request->input('grupo_id'))));

        if (empty($grupoIds)) {
            return redirect()->back()->withErrors(['grupo_id' => 'Seleccione al menos un grupo.']);
        }

        $parcela = new Parcelas();
        $parcela->cliente_id = (int) $request->cliente_id;
        $parcela->nombre     = $request->nombre;
        $parcela->superficie = (float) $request->superficie;

        // Cast explícito a decimal (float) y normaliza coma -> punto si el usuario escribe "20,67"
        $lat = str_replace(',', '.', (string) $request->lat);
        $lon = str_replace(',', '.', (string) $request->lon);

        $parcela->lat = (float) $lat;
        $parcela->lon = (float) $lon;

        $parcela->status = (int) $request->status;
        $parcela->save();

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

        
        return redirect()->route('parcelas.index', ['id' => $request->id])->with('success', 'Parcela creada correctamente');
    }

    /**
     * Display the specified resource.
     */
    public function show(Parcelas $parcelas)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id, Parcelas $parcela)
    {
        $user = auth()->user();

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

        $gruposAsignados = GrupoParcela::where('parcela_id', $parcela->id)->pluck('grupo_id')
        ->map(fn($v)=>(string)$v)->toArray();
        
        //dd( 'gruposAsignados', $gruposAsignados );

        //dd($gruposDisponibles, 'gruposAsignados', $gruposAsignados );

        return view('clientes.parcelas.edit', [
            "section_name" => "Editar parcela",
            "section_description" => "Editar parcela",
            'parcela' => $parcela,
            'cliente_id' => $id,
            'gruposDisponibles' => $gruposDisponibles,
            'gruposAsignados' => $gruposAsignados,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateParcelasRequest $request, $id, Parcelas $parcela)
    {
        $parcela->nombre = $request->nombre;
        $parcela->superficie = $request->superficie;
       // Cast explícito a decimal (float) y normaliza coma -> punto si el usuario escribe "20,67"
        $lat = str_replace(',', '.', (string) $request->lat);
        $lon = str_replace(',', '.', (string) $request->lon);

        $parcela->lat = (float) $lat;
        $parcela->lon = (float) $lon;

        $parcela->status = $request->status;
        $parcela->save();

        // debemos guardar los grupos asignados a la parcela, pero antes borrar los anteriores
        GrupoParcela::where('parcela_id', $parcela->id)->delete();
        $grupoIds = array_values(array_filter(Arr::wrap($request->input('grupo_id'))));
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
        
        return redirect()->route('parcelas.index', ['id' => $id])->with('success', 'Parcela actualizada correctamente');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id, Parcelas $parcela)
    {
        // 1) Borrar todas las zonas hijas
        $parcela->zonaManejos()->delete();

        // 2) Ahora sí puedes borrar la parcela
        $parcela->delete();

        return redirect()
            ->route('parcelas.index', ['id' => $id])
            ->with('success', 'Parcela y sus zonas de manejo eliminadas.');
    }
}
