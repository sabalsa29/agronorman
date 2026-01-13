<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$grupo = \App\Models\Grupos::with(['subgrupos', 'zonaManejos', 'usuarios'])->find(1);

if ($grupo) {
    echo "=== GRUPO ID=1 ===" . PHP_EOL;
    echo "Nombre: " . $grupo->nombre . PHP_EOL;
    echo "Status: " . $grupo->status . PHP_EOL;
    echo "Grupo Padre ID: " . ($grupo->grupo_id ?? 'NULL') . PHP_EOL;
    echo PHP_EOL;

    echo "=== SUBGRUPOS ===" . PHP_EOL;
    if ($grupo->subgrupos->count() > 0) {
        foreach ($grupo->subgrupos as $subgrupo) {
            echo "- ID: " . $subgrupo->id . " | Nombre: " . $subgrupo->nombre . " | Status: " . $subgrupo->status . PHP_EOL;

            // Mostrar subgrupos anidados
            $subgrupo->load('subgrupos');
            if ($subgrupo->subgrupos->count() > 0) {
                foreach ($subgrupo->subgrupos as $subsubgrupo) {
                    echo "  └─ ID: " . $subsubgrupo->id . " | Nombre: " . $subsubgrupo->nombre . " | Status: " . $subsubgrupo->status . PHP_EOL;
                }
            }
        }
    } else {
        echo "No tiene subgrupos" . PHP_EOL;
    }
    echo PHP_EOL;

    echo "=== ZONAS DE MANEJO ===" . PHP_EOL;
    if ($grupo->zonaManejos->count() > 0) {
        foreach ($grupo->zonaManejos as $zona) {
            echo "- ID: " . $zona->id . " | Nombre: " . $zona->nombre . PHP_EOL;
        }
    } else {
        echo "No tiene zonas de manejo asignadas directamente" . PHP_EOL;
    }
    echo PHP_EOL;

    // También buscar zonas en subgrupos
    $gruposDescendientes = $grupo->obtenerDescendientes();
    $zonasEnSubgrupos = \App\Models\ZonaManejos::whereIn('grupo_id', $gruposDescendientes)
        ->where('grupo_id', '!=', $grupo->id)
        ->get();

    if ($zonasEnSubgrupos->count() > 0) {
        echo "=== ZONAS EN SUBGRUPOS ===" . PHP_EOL;
        foreach ($zonasEnSubgrupos as $zona) {
            $grupoZona = \App\Models\Grupos::find($zona->grupo_id);
            echo "- ID: " . $zona->id . " | Nombre: " . $zona->nombre . " | Grupo: " . ($grupoZona ? $grupoZona->nombre : 'N/A') . PHP_EOL;
        }
        echo PHP_EOL;
    }

    echo "=== USUARIOS ===" . PHP_EOL;
    if ($grupo->usuarios->count() > 0) {
        foreach ($grupo->usuarios as $usuario) {
            echo "- ID: " . $usuario->id . " | Nombre: " . $usuario->nombre . " | Email: " . $usuario->email . PHP_EOL;
        }
    } else {
        echo "No tiene usuarios asignados directamente" . PHP_EOL;
    }
    echo PHP_EOL;

    // También buscar usuarios en subgrupos
    $usuariosEnSubgrupos = \App\Models\User::whereIn('grupo_id', $gruposDescendientes)
        ->where('grupo_id', '!=', $grupo->id)
        ->get();

    if ($usuariosEnSubgrupos->count() > 0) {
        echo "=== USUARIOS EN SUBGRUPOS ===" . PHP_EOL;
        foreach ($usuariosEnSubgrupos as $usuario) {
            $grupoUsuario = \App\Models\Grupos::find($usuario->grupo_id);
            echo "- ID: " . $usuario->id . " | Nombre: " . $usuario->nombre . " | Email: " . $usuario->email . " | Grupo: " . ($grupoUsuario ? $grupoUsuario->nombre : 'N/A') . PHP_EOL;
        }
    } else {
        echo "No hay usuarios en subgrupos" . PHP_EOL;
    }

    // Buscar específicamente el usuario ulises@tquis.com
    echo PHP_EOL . "=== BUSCAR USUARIO ulises@tquis.com ===" . PHP_EOL;
    $usuarioUlises = \App\Models\User::where('email', 'ulises@tquis.com')->first();
    if ($usuarioUlises) {
        echo "ID: " . $usuarioUlises->id . PHP_EOL;
        echo "Nombre: " . $usuarioUlises->nombre . PHP_EOL;
        echo "Email: " . $usuarioUlises->email . PHP_EOL;
        echo "Grupo ID: " . ($usuarioUlises->grupo_id ?? 'NULL') . PHP_EOL;

        if ($usuarioUlises->grupo_id) {
            $grupoUlises = \App\Models\Grupos::find($usuarioUlises->grupo_id);
            echo "Grupo: " . ($grupoUlises ? $grupoUlises->nombre : 'N/A') . PHP_EOL;
        } else {
            echo "No tiene grupo asignado" . PHP_EOL;
        }

        // Verificar zonas asignadas directamente
        echo PHP_EOL . "Zonas asignadas directamente:" . PHP_EOL;
        $zonasDirectas = $usuarioUlises->zona_manejos()->whereNull('zona_manejos_user.deleted_at')->get();
        if ($zonasDirectas->count() > 0) {
            foreach ($zonasDirectas as $zona) {
                $grupoZona = $zona->grupo ? $zona->grupo->nombre : 'Sin grupo';
                echo "  - ID: " . $zona->id . " | " . $zona->nombre . " (Grupo: " . $grupoZona . ")" . PHP_EOL;
            }
        } else {
            echo "  No tiene zonas asignadas directamente" . PHP_EOL;
        }

        // Verificar todas las zonas accesibles usando forUser()
        echo PHP_EOL . "Todas las zonas accesibles (usando forUser()):" . PHP_EOL;
        $zonasAccesibles = \App\Models\ZonaManejos::forUser($usuarioUlises)->get();
        echo "Total: " . $zonasAccesibles->count() . PHP_EOL;
        foreach ($zonasAccesibles as $zona) {
            $grupoZona = $zona->grupo ? $zona->grupo->nombre : 'Sin grupo';
            echo "  - ID: " . $zona->id . " | " . $zona->nombre . " (Grupo: " . $grupoZona . ")" . PHP_EOL;
        }
    } else {
        echo "Usuario no encontrado" . PHP_EOL;
    }
} else {
    echo "Grupo con ID=1 no encontrado" . PHP_EOL;
}
