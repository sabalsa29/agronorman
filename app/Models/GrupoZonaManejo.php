<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GrupoZonaManejo extends Model
{
    protected $table = 'grupo_zona_manejo';

    public $timestamps = true; 

    protected $fillable = [
        'user_id',
        'grupo_id',
        'zona_manejo_id',
        'created_at',
    ];

    public function grupo()
    {
        return $this->belongsTo(Grupos::class, 'grupo_id');
    }

    public function zonaManejo()
    {
        return $this->belongsTo(\App\Models\ZonaManejos::class, 'zona_manejo_id');
    }

    public function zona_manejo ()
    {
        return $this->belongsTo(ZonaManejos::class, 'zona_manejo_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function asignarZonasAUsuario($userId, $zonasIds)
    {
        foreach ($zonasIds as $zonaId) {

            if ($zonaId) {
                self::updateOrInsert([
                    'user_id' => $userId,
                    'grupo_id' => null,
                    'zona_manejo_id' => $zonaId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    
}
