<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AppBitacoraLoginRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AppBitacoraAuthController extends Controller
{
    public function login(AppBitacoraLoginRequest $request)
    {
        $userInput = trim((string)$request->input('user'));
        $pwd       = (string)$request->input('pwd');
        $device    = (string)($request->input('device_name') ?: 'app-bitacora');

        // Ajusta el campo si no usas email
        $user = User::query()->where('email', $userInput)->first();

        if (!$user || !Hash::check($pwd, $user->password)) {
            return response()->json([
                'ok' => false,
                'message' => 'Credenciales inválidas.',
            ], 401);
        }

        // Opcional: 1 token por dispositivo (borra el anterior con el mismo nombre)
        $user->tokens()->where('name', $device)->delete();

        $token = $user->createToken($device)->plainTextToken;

        return response()->json([
            'ok' => true,
            'token_type' => 'Bearer',
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['ok' => true]);
    }

    public function me(Request $request)
    {
        $u = $request->user();

        return response()->json([
            'ok' => true,
            'user' => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
            ],
        ]);
    }
}
