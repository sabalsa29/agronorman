<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ForecastHourly extends Model
{
    /** @use HasFactory<\Database\Factories\ForecastHourlyFactory> */
    use HasFactory;

    protected $table = 'forecast_hourlies';

    public $timestamps = true;

    protected $fillable = [
        'forecast_id',
        'paercela_id',
        'fecha',
        'humedad_relativa',
        'temperatura',
    ];
}
