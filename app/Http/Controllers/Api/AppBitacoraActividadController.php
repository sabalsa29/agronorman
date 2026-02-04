<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse as HttpJsonResponse;
use Illuminate\Http\Request;
use JsonResponse;

class AppBitacoraActividadController extends Controller
{
    public function store(Request $request, int $id_zm): HttpJsonResponse
    {
        // Lógica para almacenar la actividad en la zona de manejo $id_zm
        // Validar que el usuario tenga acceso a la zona de manejo
        // Guardar la actividad en la base de datos

        return response()->json([
            'ok' => true,
            'message' => 'Actividad registrada correctamente.',
        ]);
    }

    public function show(Request $request, int $id): HttpJsonResponse
    {
        // Lógica para mostrar los detalles de una actividad específica por su ID
        // Validar que el usuario tenga acceso a la actividad

        return response()->json([
            'ok' => true,
            'actividad' => [
                'id' => $id,
                'zona_manejo_id' => 123,
                'tipo_actividad' => 'Riego',
                'descripcion' => 'Riego por goteo en la zona de manejo.',
                'fecha_hora' => '2024-06-01T10:00:00Z',
            ],
        ]);
    }

    public function update(Request $request, int $id): HttpJsonResponse
    {
        // Lógica para actualizar una actividad específica por su ID
        // Validar que el usuario tenga acceso a la actividad
        // Actualizar la actividad en la base de datos

        return response()->json([
            'ok' => true,
            'message' => 'Actividad actualizada correctamente.',
        ]);
    }
}
