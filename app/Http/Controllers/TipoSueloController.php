<?php

namespace App\Http\Controllers;

use App\Models\TipoSuelo;
use Illuminate\Http\Request;

class TipoSueloController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tiposSuelo = TipoSuelo::all();
        return view('tiposuelo.index', [
            "section_name" => "Tipos de Suelo",
            "section_description" => "Tipos de suelo disponibles",
            "suelos" => $tiposSuelo,
        ]);
    }

    public function updateSuelos(Request $request)
    {
        // Obtenemos todos los registros
        $suelos = TipoSuelo::all();

        // Iteramos y asignamos los valores usando $request->input()
        foreach ($suelos as $suelo) {
            $suelo->bajo        = $request->input("bajo.{$suelo->id}");
            $suelo->optimo_min  = $request->input("optimo_min.{$suelo->id}");
            $suelo->optimo_max  = $request->input("optimo_max.{$suelo->id}");
            $suelo->alto        = $request->input("alto.{$suelo->id}");

            $suelo->save();
        }

        // Puedes retornar una redirección, una vista o un JSON de confirmación
        return redirect()->route('textura-suelo.index')
            ->with('success', 'Valores actualizados correctamente.');
    }
}
