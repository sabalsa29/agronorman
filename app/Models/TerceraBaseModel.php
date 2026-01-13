<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TerceraBaseModel extends Model
{
    /**
     * Especificar la conexión de base de datos
     */
    protected $connection = 'tercera_db';

    /**
     * Los atributos que son asignables masivamente.
     */
    protected $fillable = [
        'nombre',
        'descripcion',
        // Agrega aquí los campos que quieras permitir asignación masiva
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
