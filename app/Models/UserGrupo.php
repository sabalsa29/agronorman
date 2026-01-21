<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserGrupo extends Model
{
    protected $table = 'user_grupo';

    public $timestamps = true; 

    protected $fillable = [
        'user_id',
        'grupo_id',
        'created_at',
    ];

    public function grupo()
    {
        return $this->belongsTo(Grupos::class, 'grupo_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
}
