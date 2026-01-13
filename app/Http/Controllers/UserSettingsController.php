<?php

namespace App\Http\Controllers;

use App\Models\UserSetting;
use Illuminate\Http\Request;

class UserSettingsController extends Controller
{
    /**
     * Devuelve la configuración asociada a una zona de manejo.
     */
    public function show(Request $request)
    {
        // La petición debe incluir zona_manejo_id
        $zonaManejoId = $request->input('zona_manejo_id');

        // Busca la configuración para esa zona de manejo
        $setting = UserSetting::where('zona_manejo_id', $zonaManejoId)->first();

        return response()->json($setting);
    }

    /**
     * Guarda o actualiza la configuración para una zona de manejo específica.
     */
    public function store(Request $request)
    {
        // Validar los IDs recibidos
        $data = $request->validate([
            'zona_manejo_id'      => 'required|exists:zona_manejos,id',
            'tipo_cultivo_id'     => 'required|exists:tipo_cultivos,id',
            'etapa_fenologica_id' => 'required|exists:etapa_fenologicas,id',
        ]);

        // Crea o actualiza la configuración usando zona_manejo_id como llave única
        UserSetting::updateOrCreate(
            ['zona_manejo_id' => $data['zona_manejo_id']],
            $data
        );

        return response()->json(['success' => true]);
    }
}
