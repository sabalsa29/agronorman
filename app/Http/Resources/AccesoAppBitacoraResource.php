<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccesoAppBitacoraResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'predio' => (string) ($this->predio ?? ''),
            'zm'     => (string) ($this->zm ?? ''),
            'id_zm'  => (string) ($this->id_zm ?? ''),
        ];
    }
}
