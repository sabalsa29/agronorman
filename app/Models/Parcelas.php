<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Parcelas extends Model
{
    /** @use HasFactory<\Database\Factories\ParcelasFactory> */
    use HasFactory;
    use SoftDeletes;
    protected $table = 'parcelas';

    public $timestamps = true;

    protected $fillable = [
        'id',
        'cliente_id',
        'nombre',
        'superficie',
        'lat',
        'lon',
        'status',
    ];

    public function cliente()
    {
        return $this->belongsTo(Clientes::class);
    }

    public function zonaManejos()
    {
        return $this->hasMany(ZonaManejos::class, 'parcela_id');
    }
}
