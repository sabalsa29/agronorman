<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GrupoParcela extends Model
{
     protected $table = 'grupo_parcela';

    public $timestamps = true; 

    protected $fillable = [
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
}
