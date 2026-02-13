<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClientesRequest;
use App\Http\Requests\UpdateClientesRequest;
use App\Models\Clientes;
use App\Models\User;
use App\Traits\LogsPlatformActions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientesController extends Controller
{
    use LogsPlatformActions;
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (!$user) {
            abort(403, 'Debe iniciar sesión para acceder a esta página.');
        }

        // Registrar visualización en el log (solo para super admin)
        // if ($user->isSuperAdmin()) {
        //     $this->logPlatformAction(
        //         seccion: 'clientes',
        //         accion: 'ver_lista',
        //         entidadTipo: 'Clientes',
        //         descripcion: 'Visualización de lista de clientes',
        //         entidadId: null,
        //         datosAdicionales: [
        //             'total_clientes' => Clientes::count(),
        //         ]
        //     );
        // }
        // Se debe registrar log 
        $this->logPlatformAction(
            seccion: 'productores',
            accion: 'ver_lista',
            entidadTipo: 'Productores',
            descripcion: 'Visualización de lista de productores',
            entidadId: null,
            datosAdicionales: [
                'total_productores' => Clientes::count(),
            ]
        );

        // Si es Super Admin, muestra la lista de todos los clientes
        if ($user->isSuperAdmin()) {
            $list = Clientes::all();
            return view('clientes.index', [
                "section_name" => "Productores",
                "section_description" => "Lista de Productores",
                "list" => $list,
            ]);
        }

        // Si es Administrador (role_id = 2), redirigir a usuarios
        if ($user->role_id == 2 && $user->cliente_id) {
            return redirect()->route('usuarios.index', ['id' => $user->cliente_id]);
        }

        // Si es Cliente (role_id = 3), mostrar solo su cliente en la lista
        if ($user->role_id == 3 && $user->cliente_id) {
            $list = Clientes::where('id', $user->cliente_id)->get();
            return view('clientes.index', [
                "section_name" => "Mi Cliente",
                "section_description" => "Información de mi cliente",
                "list" => $list,
            ]);
        }

        // Si no tiene cliente asignado, mostrar error
        abort(403, 'No tiene un productor asignado.');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Solo Super Admin puede crear clientes
        /** @var User|null $user */
        $user = Auth::user();
        if (!$user || !$user->isSuperAdmin()) {
            abort(403, 'Solo el Super Administrador puede crear productores.');
        }

        return view('clientes.create', [
            "section_name" => "Nuevo Productor",
            "section_description" => "Crear un nuevo productor",
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreClientesRequest $request)
    {
        // Solo Super Admin puede crear clientes
        /** @var User|null $user */
        $user = Auth::user();
        if (!$user || !$user->isSuperAdmin()) {
            abort(403, 'Solo el Super Administrador puede crear clientes.');
        }

        try {
            $cliente = Clientes::create([
                'nombre' => $request->nombre,
                'empresa' => $request->empresa,
                'ubicacion' => $request->ubicacion,
                'telefono' => $request->telefono,
                'status' => $request->status ?? 1,
            ]);

            // Registrar en el log
            $this->logPlatformAction(
                seccion: 'Productores',
                accion: 'crear',
                entidadTipo: 'Productores',
                descripcion: "Productor '{$cliente->nombre}' creado exitosamente",
                entidadId: $cliente->id,
                datosNuevos: $this->getModelDataForLog($cliente, ['nombre', 'empresa', 'ubicacion', 'telefono', 'status']),
                datosAdicionales: [
                    'empresa' => $cliente->empresa,
                    'ubicacion' => $cliente->ubicacion,
                ]
            );

            return redirect()->route('clientes.index')
                ->with('success', 'Productor creado exitosamente.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al crear el productor: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Clientes $cliente)
    {
        // Registrar visualización en el log 
        $this->logPlatformAction(
            seccion: 'productores',
            accion: 'ver',
            entidadTipo: 'Productores',
            descripcion: "Visualización de detalles del productor '{$cliente->nombre}' (ID: {$cliente->id})",
            entidadId: $cliente->id,
            datosAdicionales: [
                'nombre' => $cliente->nombre,
            ]
        );

        return view('clientes.show', [
            "section_name" => "Detalles del Productor",
            "section_description" => "Información detallada del productor",
            "cliente" => $cliente,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Clientes $cliente)
    {
        // Solo Super Admin puede editar clientes
        /** @var User|null $user */
        $user = Auth::user();
        if (!$user || !$user->isSuperAdmin()) {
            abort(403, 'Solo el Super Administrador puede editar clientes.');
        }

        return view('clientes.edit', [
            "section_name" => "Editar Productor",
            "section_description" => "Modificar información del productor",
            "cliente" => $cliente,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateClientesRequest $request, Clientes $cliente)
    {
        // Solo Super Admin puede actualizar clientes
        /** @var User|null $user */
        $user = Auth::user();
        if (!$user || !$user->isSuperAdmin()) {
            abort(403, 'Solo el Super Administrador puede actualizar clientes.');
        }

        try {
            // Guardar datos anteriores para el log
            $datosAnteriores = $this->getModelDataForLog($cliente, ['nombre', 'empresa', 'ubicacion', 'telefono', 'status']);

            $cliente->update([
                'nombre' => $request->nombre,
                'empresa' => $request->empresa,
                'ubicacion' => $request->ubicacion,
                'telefono' => $request->telefono,
                'status' => $request->status ?? $cliente->status,
            ]);

            // Refrescar el modelo para obtener los datos nuevos
            $cliente->refresh();
            $datosNuevos = $this->getModelDataForLog($cliente, ['nombre', 'empresa', 'ubicacion', 'telefono', 'status']);

            // Registrar en el log
            $this->logPlatformAction(
                seccion: 'productores',
                accion: 'editar',
                entidadTipo: 'Productores',
                descripcion: "Productor '{$cliente->nombre}' (ID: {$cliente->id}) actualizado",
                entidadId: $cliente->id,
                datosAnteriores: $datosAnteriores,
                datosNuevos: $datosNuevos,
                datosAdicionales: [
                    'campos_modificados' => $this->getCamposModificados($datosAnteriores, $datosNuevos),
                ]
            );

            return redirect()->route('clientes.index')
                ->with('success', 'Productor actualizado exitosamente.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al actualizar el productor: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Clientes $cliente)
    {
        // Solo Super Admin puede eliminar clientes
        /** @var User|null $user */
        $user = Auth::user();
        if (!$user || !$user->isSuperAdmin()) {
            abort(403, 'Solo el Super Administrador puede eliminar clientes.');
        }

        try {
            // Guardar datos antes de eliminar para el log
            $datosAnteriores = $this->getModelDataForLog($cliente, ['nombre', 'empresa', 'ubicacion', 'telefono', 'status']);
            $nombre = $cliente->nombre;
            $clienteId = $cliente->id;

            $cliente->delete();

            // Registrar en el log
            $this->logPlatformAction(
                seccion: 'productores',
                accion: 'eliminar',
                entidadTipo: 'Productores',
                descripcion: "Productor '{$nombre}' (ID: {$clienteId}) eliminado",
                entidadId: $clienteId,
                datosAnteriores: $datosAnteriores,
                datosAdicionales: [
                    'nombre' => $nombre,
                ]
            );

            return redirect()->route('clientes.index')
                ->with('success', "Productor '{$nombre}' eliminado exitosamente.");
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al eliminar el productor: ' . $e->getMessage());
        }
    }

    /**
     * Mostrar formulario para gestionar grupos del cliente
     */
    public function grupos(Clientes $cliente)
    {
        // Solo Super Admin puede gestionar grupos de clientes
        /** @var User|null $user */
        $user = Auth::user();
        if (!$user || !$user->isSuperAdmin()) {
            abort(403, 'Solo el Super Administrador puede gestionar grupos de clientes.');
        }

        // Obtener solo grupos padre (raíz) - grupos que no tienen grupo_id
        $gruposDisponibles = \App\Models\Grupos::whereNull('grupo_id')
            ->get()
            ->map(function ($grupo) {
                return [
                    'id' => $grupo->id,
                    'nombre' => $grupo->nombre,
                ];
            });

        // Obtener grupos asignados al cliente
        $gruposAsignados = $cliente->grupos->pluck('id')->toArray();

        return view('clientes.grupos', [
            "section_name" => "Gestionar Grupos",
            "section_description" => "Asignar grupos al cliente: {$cliente->nombre}",
            "cliente" => $cliente,
            "gruposDisponibles" => $gruposDisponibles,
            "gruposAsignados" => $gruposAsignados,
        ]);
    }

    /**
     * Guardar grupos asignados al cliente
     */
    public function storeGrupos(Request $request, Clientes $cliente)
    {
        // Solo Super Admin puede gestionar grupos de clientes
        /** @var User|null $user */
        $user = Auth::user();
        if (!$user || !$user->isSuperAdmin()) {
            abort(403, 'Solo el Super Administrador puede gestionar grupos de clientes.');
        }

        try {
            // Validar que los grupos existan y sean grupos padre (raíz)
            $gruposIds = $request->input('grupos', []);
            if (!empty($gruposIds)) {
                $gruposExistentes = \App\Models\Grupos::whereIn('id', $gruposIds)
                    ->whereNull('grupo_id') // Solo grupos padre
                    ->pluck('id')
                    ->toArray();
                $gruposIds = $gruposExistentes; // Solo usar grupos padre que existen
            }

            // Sincronizar grupos (elimina los que no están y agrega los nuevos)
            $cliente->grupos()->sync($gruposIds);

            // Registrar en el log
            $gruposNombres = \App\Models\Grupos::whereIn('id', $gruposIds)->pluck('nombre')->toArray();
            $this->logPlatformAction(
                seccion: 'clientes',
                accion: 'asignar_grupos',
                entidadTipo: 'Clientes',
                descripcion: "Grupos asignados al cliente '{$cliente->nombre}' (ID: {$cliente->id})",
                entidadId: $cliente->id,
                datosAdicionales: [
                    'grupos_asignados' => $gruposNombres,
                    'grupos_ids' => $gruposIds,
                ]
            );

            return redirect()->route('clientes.grupos', $cliente)
                ->with('success', 'Grupos asignados exitosamente.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al asignar grupos: ' . $e->getMessage())
                ->withInput();
        }
    }
}
