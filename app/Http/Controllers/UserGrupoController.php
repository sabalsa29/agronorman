<?php

namespace App\Http\Controllers;

use App\Models\GrupoParcela;
use App\Models\Grupos;
use App\Models\Parcelas;
use App\Models\User;
use App\Models\UserGrupo;
use Illuminate\Http\Request;

class UserGrupoController extends Controller
{
    public function index()
    {
        $users = User::where('status', 1)->get();

        $grupos = UserGrupo::with('grupo')->distinct('grupo_id')->get()->map(function ($item) {
            return $item->grupo;
        });

        $asignaciones = UserGrupo::with('user', 'grupo')->get();

        //dd($asignaciones);

        $estructuraJerarquica = collect();
        //dd($asignaciones);

        return view('usuarios_asignacion.index', [
            "section_name" => "Asignar Usuarios a Grupos",
            "section_description" => "Asigne usuarios específicos a los grupos.",
            'asignaciones' => $asignaciones,
            'users' => $users,
            'grupos' => $grupos,
            "estructuraJerarquica" => $estructuraJerarquica,
        ]);

    }

    public function assign()
    {

    $user = auth()->user();

    $users = User::where('status', 1)->get();
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

        //dd($gruposDisponibles);

        // Lógica para mostrar el formulario de asignación de usuarios a grupos
        return view('usuarios_asignacion.assign', [
            "section_name" => "Asignar Usuarios a Grupos",
            "section_description" => "Asigne usuarios específicos a los grupos.",
            "gruposDisponibles" => $gruposDisponibles,
            'users' => $users,
        ]);
    }

    public function store(Request $request)
    {

        $request->validate([
            'grupo_id' => 'required|array|min:1',
            'grupo_id.*' => 'exists:grupos,id',
            'usuario_id' => 'required|exists:users,id',
        ]);

        //dd( $request->all());

        $grupoIds = $request->input('grupo_id');
        $userId = $request->input('usuario_id');

        // Eliminar asignaciones previas del usuario
        UserGrupo::where('user_id', $userId)->delete();

        // Crear nuevas asignaciones
        foreach ($grupoIds as $grupoId) {
            UserGrupo::create([
                'grupo_id' => $grupoId,
                'user_id' => $userId, 
            ]);
        }

        return redirect()->route('accesos.usuarios.index')
            ->with('success', 'Asignaciones de usuario a grupos actualizadas correctamente.');
    }   

   public function prediosByGrupo(Grupos $grupo)
    {
        $predios = Parcelas::query()
            ->whereIn('id', function ($q) use ($grupo) {
                $q->select('parcela_id')
                ->from('grupo_parcela')
                ->where('grupo_id', $grupo->id);
            })
            ->select('id', 'nombre')
            ->orderBy('nombre')
            ->get();

        return response()->json($predios);
}
}
