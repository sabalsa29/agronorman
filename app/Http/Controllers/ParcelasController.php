<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreParcelasRequest;
use App\Http\Requests\UpdateParcelasRequest;
use App\Models\Grupos;
use App\Models\Parcelas;
use Illuminate\Http\Request;

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
            "section_name" => "Lista de predios del usuario " . $clienteNombre,
            "section_description" => "Parcelas de los usuarios",
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
        dd( 'request recibido en store parcelas', $request->all() );
        $parcelas = new Parcelas();
        $parcelas->cliente_id = $request->cliente_id;
        $parcelas->nombre = $request->nombre;
        $parcelas->superficie = $request->superficie;
        $parcelas->lat = $request->lat;
        $parcelas->lon = $request->lon;
        $parcelas->status = $request->status;
        $parcelas->save();
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
        return view('clientes.parcelas.edit', [
            "section_name" => "Editar parcela",
            "section_description" => "Editar parcela",
            'parcela' => $parcela,
            'cliente_id' => $id,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateParcelasRequest $request, $id, Parcelas $parcela)
    {
        $parcela->nombre = $request->nombre;
        $parcela->superficie = $request->superficie;
        $parcela->lat = $request->lat;
        $parcela->lon = $request->lon;
        $parcela->status = $request->status;
        $parcela->save();
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
