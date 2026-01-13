<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Measurement extends Model
{
    protected $fillable = [
        'imei','transaction_id','measured_at_utc',
        'temp_npk_c','hum_npk_pct','ph_npk','cond_us_cm',
        'nit_mg_kg','pot_mg_kg','phos_mg_kg',
        'temp_sns_c','hum_sns_pct','co2_ppm',
        'voltaje_mv','contador_mnsj','tec','ARS','TON','CELLID','CIT',
        'SWV','MNC','MCC','RAT','LAC','PROJECT','RSRP','RSRQ','raw_payload'
    ];

    protected $casts = [
        'measured_at_utc' => 'datetime',
        'raw_payload' => 'array',
    ];
}
