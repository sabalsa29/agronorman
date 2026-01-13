<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TelemetriaUdp extends Model
{
    protected $table = 'telemetria_udp';

    protected $fillable = [
        'payload',
        'encoding',
        'type',
        'value',
        'received',
        'message_id',
        'source',
        'version',
        'iccid',
        'ip',
        'imsi',
        'estacion_id',
        'transaccion_id',
        'temp_npk_lv1',
        'hum_npk_lv1',
        'ph_npk_lv1',
        'cond_npk_lv1',
        'nit_npk_lv1',
        'pot_npk_lv1',
        'phos_npk_lv1',
        'temp_sns_lv1',
        'hum_sns_lv1',
        'co2_sns_lv1',
        'fecha',
        'voltaje',
        'contador_mnsj',
        'tec',
        'ARS',
        'TON',
        'CELLID',
        'CIT',
        'SWV',
        'MNC',
        'MCC',
        'RAT',
        'LAC',
        'PROJECT',
        'RSRP',
        'RSRQ'
    ];

    protected $casts = [
        'payload' => 'array',
        'received' => 'string',
        'message_id' => 'string',
        'source' => 'string',
        'version' => 'string',
        'iccid' => 'string',
        'ip' => 'string',
        'imsi' => 'string',
        'estacion_id' => 'string',
        'transaccion_id' => 'string',
        'temp_npk_lv1' => 'decimal:2',
        'hum_npk_lv1' => 'decimal:2',
        'ph_npk_lv1' => 'decimal:2',
        'cond_npk_lv1' => 'decimal:2',
        'nit_npk_lv1' => 'decimal:2',
        'pot_npk_lv1' => 'decimal:2',
        'phos_npk_lv1' => 'decimal:2',
        'temp_sns_lv1' => 'decimal:2',
        'hum_sns_lv1' => 'decimal:2',
        'co2_sns_lv1' => 'decimal:2',
        'fecha' => 'string',
        'voltaje' => 'integer',
        'contador_mnsj' => 'integer',
        'tec' => 'integer',
        'ARS' => 'string',
        'TON' => 'integer',
        'CELLID' => 'string',
        'CIT' => 'string',
        'SWV' => 'integer',
        'MNC' => 'string',
        'MCC' => 'string',
        'RAT' => 'string',
        'LAC' => 'string',
        'PROJECT' => 'string',
        'RSRP' => 'integer',
        'RSRQ' => 'integer'
    ];

    /**
     * Procesa un payload UDP y crea un registro
     */
    public static function procesarPayload($data)
    {
        try {
            // Decodificar el valor base64
            $decodedValue = base64_decode($data['payload']['value']);
            $payloadData = json_decode($decodedValue, true);

            if (!$payloadData) {
                throw new \Exception('Error decodificando el payload JSON');
            }

            // Crear el registro con todos los datos
            $telemetria = self::create([
                'payload' => $data['payload'],
                'encoding' => $data['payload']['encoding'],
                'type' => $data['payload']['type'],
                'value' => $decodedValue,
                'received' => $data['received'],
                'message_id' => $data['id'],
                'source' => $data['source'],
                'version' => $data['version'],
                'iccid' => $data['device']['iccid'],
                'ip' => $data['device']['ip'],
                'imsi' => $data['device']['imsi'],
                'estacion_id' => $payloadData['estacion_id'] ?? null,
                'transaccion_id' => $payloadData['transaccion_id'] ?? null,
                'temp_npk_lv1' => $payloadData['temp_npk_lv1'] ?? null,
                'hum_npk_lv1' => $payloadData['hum_npk_lv1'] ?? null,
                'ph_npk_lv1' => $payloadData['ph_npk_lv1'] ?? null,
                'cond_npk_lv1' => $payloadData['cond_npk_lv1'] ?? null,
                'nit_npk_lv1' => $payloadData['nit_npk_lv1'] ?? null,
                'pot_npk_lv1' => $payloadData['pot_npk_lv1'] ?? null,
                'phos_npk_lv1' => $payloadData['phos_npk_lv1'] ?? null,
                'temp_sns_lv1' => $payloadData['temp_sns_lv1'] ?? null,
                'hum_sns_lv1' => $payloadData['hum_sns_lv1'] ?? null,
                'co2_sns_lv1' => $payloadData['co2_sns_lv1'] ?? null,
                'fecha' => $payloadData['fecha'] ?? null,
                'voltaje' => $payloadData['voltaje'] ?? null,
                'contador_mnsj' => $payloadData['contador_mnsj'] ?? null,
                'tec' => $payloadData['tec'] ?? null,
                'ARS' => $payloadData['ARS'] ?? null,
                'TON' => $payloadData['TON'] ?? null,
                'CELLID' => $payloadData['CELLID'] ?? null,
                'CIT' => $payloadData['CIT'] ?? null,
                'SWV' => $payloadData['SWV'] ?? null,
                'MNC' => $payloadData['MNC'] ?? null,
                'MCC' => $payloadData['MCC'] ?? null,
                'RAT' => $payloadData['RAT'] ?? null,
                'LAC' => $payloadData['LAC'] ?? null,
                'PROJECT' => $payloadData['PROJECT'] ?? null,
                'RSRP' => $payloadData['RSRP'] ?? null,
                'RSRQ' => $payloadData['RSRQ'] ?? null
            ]);

            return $telemetria;
        } catch (\Exception $e) {
            Log::error('Error procesando payload UDP: ' . $e->getMessage(), [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
