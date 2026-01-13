<?php

namespace App\Http\Controllers;

use App\Models\PlatformLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlatformLogController extends Controller
{
    /**
     * Mostrar logs de la plataforma (solo super admin)
     */
    public function index(Request $request)
    {
        /** @var User|null $user */
        $user = Auth::user();

        // Solo super admin puede ver los logs
        if (!$user || !$user->isSuperAdmin()) {
            abort(403, 'Solo el Super Administrador puede ver los logs de la plataforma.');
        }

        // Filtros
        $seccion = $request->get('seccion');
        $accion = $request->get('accion');
        $entidadTipo = $request->get('entidad_tipo');
        $usuarioId = $request->get('usuario_id');
        $fechaDesde = $request->get('fecha_desde');
        $fechaHasta = $request->get('fecha_hasta');

        $query = PlatformLog::with('usuario')
            ->orderBy('created_at', 'desc');

        // Aplicar filtros
        if ($seccion) {
            $query->where('seccion', $seccion);
        }

        if ($accion) {
            $query->where('accion', $accion);
        }

        if ($entidadTipo) {
            $query->where('entidad_tipo', $entidadTipo);
        }

        if ($usuarioId) {
            $query->where('usuario_id', $usuarioId);
        }

        if ($fechaDesde) {
            $query->whereDate('created_at', '>=', $fechaDesde);
        }

        if ($fechaHasta) {
            $query->whereDate('created_at', '<=', $fechaHasta);
        }

        $logs = $query->paginate(50);

        // Obtener opciones para los filtros
        $secciones = PlatformLog::distinct()->pluck('seccion')->sort();
        $acciones = PlatformLog::distinct()->pluck('accion')->sort();
        $entidades = PlatformLog::distinct()->pluck('entidad_tipo')->sort();
        $usuarios = User::whereIn('id', PlatformLog::distinct()->pluck('usuario_id')->filter())
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'email']);

        return view('platform-logs.index', [
            'section_name' => 'Logs de la Plataforma',
            'section_description' => 'Registro de todas las acciones realizadas en la plataforma',
            'logs' => $logs,
            'secciones' => $secciones,
            'acciones' => $acciones,
            'entidades' => $entidades,
            'usuarios' => $usuarios,
            'filtros' => [
                'seccion' => $seccion,
                'accion' => $accion,
                'entidad_tipo' => $entidadTipo,
                'usuario_id' => $usuarioId,
                'fecha_desde' => $fechaDesde,
                'fecha_hasta' => $fechaHasta,
            ],
        ]);
    }

    /**
     * Mostrar detalles de un log específico
     */
    public function show($id)
    {
        /** @var User|null $user */
        $user = Auth::user();

        // Solo super admin puede ver los logs
        if (!$user || !$user->isSuperAdmin()) {
            abort(403, 'Solo el Super Administrador puede ver los logs de la plataforma.');
        }

        $platformLog = PlatformLog::with('usuario')->findOrFail($id);

        return view('platform-logs.show', [
            'section_name' => 'Detalles del Log',
            'section_description' => 'Información detallada del registro',
            'log' => $platformLog,
        ]);
    }
}
