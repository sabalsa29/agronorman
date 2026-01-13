<?php

namespace App\Traits;

use App\Models\PlatformLog;
use Illuminate\Support\Facades\Auth;

trait LogsPlatformActions
{
    /**
     * Registrar una acción en el log de la plataforma
     */
    protected function logPlatformAction(
        string $seccion,
        string $accion,
        string $entidadTipo,
        string $descripcion,
        ?int $entidadId = null,
        ?array $datosAnteriores = null,
        ?array $datosNuevos = null,
        ?array $datosAdicionales = null
    ): void {
        $user = Auth::user();

        PlatformLog::crearLog(
            usuarioId: $user?->id,
            username: $user?->nombre ?? $user?->email ?? 'Sistema',
            seccion: $seccion,
            accion: $accion,
            entidadTipo: $entidadTipo,
            entidadId: $entidadId,
            descripcion: $descripcion,
            datosAnteriores: $datosAnteriores,
            datosNuevos: $datosNuevos,
            datosAdicionales: $datosAdicionales
        );
    }

    /**
     * Obtener datos de un modelo para el log (solo campos relevantes)
     */
    protected function getModelDataForLog($model, array $fields = null): array
    {
        if (!$model) {
            return [];
        }

        $data = $model->toArray();

        // Si se especifican campos, solo incluir esos
        if ($fields !== null) {
            $data = array_intersect_key($data, array_flip($fields));
        }

        // Excluir campos sensibles o innecesarios
        $exclude = ['password', 'remember_token', 'created_at', 'updated_at', 'deleted_at'];
        foreach ($exclude as $field) {
            unset($data[$field]);
        }

        return $data;
    }

    /**
     * Obtener lista de campos modificados comparando datos anteriores y nuevos
     */
    protected function getCamposModificados(array $datosAnteriores, array $datosNuevos): array
    {
        $camposModificados = [];

        foreach ($datosNuevos as $campo => $valorNuevo) {
            $valorAnterior = $datosAnteriores[$campo] ?? null;
            if ($valorAnterior !== $valorNuevo) {
                $camposModificados[] = $campo;
            }
        }

        return $camposModificados;
    }
}
