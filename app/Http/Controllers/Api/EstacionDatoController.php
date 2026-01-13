<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EstacionDato;
use Illuminate\Http\Request;

class EstacionDatoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $datos = EstacionDato::all();
        return response()->json($datos);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $dato = EstacionDato::findOrFail($id);
        return response()->json($dato);
    }

    /**
     * Display the datos for the specified estacion.
     */
    public function porEstacion($estacion_id)
    {
        $datos = EstacionDato::where('estacion_id', $estacion_id)->get();
        return response()->json($datos);
    }

    /**
     * Display the last dato for the specified estacion.
     */
    public function ultimoDato($estacion_id)
    {
        $dato = EstacionDato::where('estacion_id', $estacion_id)
            ->orderBy('fecha', 'desc')
            ->first();
        return response()->json($dato);
    }
}
