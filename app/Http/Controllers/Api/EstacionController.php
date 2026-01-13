<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Estacion;
use Illuminate\Http\Request;

class EstacionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $estaciones = Estacion::all();
        return response()->json($estaciones);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $estacion = Estacion::findOrFail($id);
        return response()->json($estacion);
    }

    /**
     * Display the datos of the specified estacion.
     */
    public function datos($id)
    {
        $estacion = Estacion::findOrFail($id);
        $datos = $estacion->datos;
        return response()->json($datos);
    }
}
