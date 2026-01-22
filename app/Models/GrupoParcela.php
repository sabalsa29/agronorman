<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GrupoParcela extends Model
{
    protected $table = 'grupo_parcela';

    public $timestamps = true; 

    protected $fillable = [
        'user_id',
        'grupo_id',
        'parcela_id',
        'created_at',
    ];

    public function grupo()
    {
        return $this->belongsTo(Grupos::class, 'grupo_id');
    }

    public function parcela()
    {
        return $this->belongsTo(Parcelas::class, 'parcela_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public static function asignarPrediosAUsuario($userId, $prediosIds)
    {
        foreach ($prediosIds as $predioId) {
            // Aquí debes definir cómo obtener el grupo asociado al predio
            $grupoParcela = self::where('parcela_id', $predioId)->first();

            if ($grupoParcela) {
                self::updateOrInsert([
                    'user_id' => $userId,
                    'grupo_id' => $grupoParcela->grupo_id,
                    'parcela_id' => $predioId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } 
        }
    }
}
