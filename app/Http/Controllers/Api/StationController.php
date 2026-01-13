<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EstacionDato;
use App\Models\Estaciones;
use App\Models\ZonaManejos;
use App\Models\TelemetriaUdp;
use Carbon\Carbon;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Faker\Factory as Faker;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

date_default_timezone_set('America/Mexico_City');
class StationController extends Controller
{

    public function datos_estacion_de_parcela($parcela_id, $estacion_id)
    {
        //Obtenemos el estado actual de las variables
        $estadoActual = new ZonaManejos();
        $resultado = $estadoActual->obtenerEstadoActual($estacion_id);

        return response()->json($resultado, 200);
    }

    public function datos_estacion()
    {
        $register = EstacionDato::first();
        return response()->json(['data' => $register, 'status' => 200], 200);
        //return $register;
    }

    public function datosWebService()
    {
        $client = new Client(['headers' => ['x-api-key' => config('webservice.key_header')]]);
        $startRange = Carbon::now()->subDays(30)->timestamp . '000';
        $endRange   = Carbon::now()->timestamp . '000';
        $url    = "https://ps2w5pi512.execute-api.us-east-2.amazonaws.com/dev/export-data?startRange=$startRange&endRange=$endRange";
        $result = $client->request('GET', $url);

        if ($result->getStatusCode() == 201) {
            $result = json_decode($result->getBody());
            //Si el resultado no viene vacío entonces procesamos el registro
            if (!$result->info->isEmpty) {

                foreach ($result->data as $webservice_register) {
                    //Obtenemos el id de la estación
                    $qEstaciones = "SELECT * FROM estaciones WHERE uuid='" . $webservice_register->sensor_id . "'";
                    $dEstaciones = DB::select(DB::raw($qEstaciones));
                    //DB::connection()->enableQueryLog();
                    //Verificamos si este dato ya existe en la base de datos
                    $last_register_station = EstacionDato::where('estacion_id', $dEstaciones[0]->id)->where('id_origen', $webservice_register->id)->orderBy('created_at', 'desc')->first();
                    /*$queries = DB::getQueryLog();
                    Log::info($queries);
                    exit;*/
                    //Si no existe, lo insertamos
                    if (is_null($last_register_station)) {
                        $formated_date = Carbon::parse($webservice_register->local_data);

                        if (!property_exists($webservice_register, 'precipitation'))
                            $webservice_register->precipitation = 0;
                        if (!property_exists($webservice_register, 'wind_direction'))
                            $webservice_register->wind_direction = 0;
                        if (!property_exists($webservice_register, 'wind_velocity'))
                            $webservice_register->wind_velocity = 0;


                        $data = [
                            'temperature' => $webservice_register->temperature,
                            'humidity'    => $webservice_register->humidity,
                            'brightness'    => $webservice_register->brightness,
                            'pressure'    => $webservice_register->pressure,
                            //'conductivity'    => $webservice_register->conductivity,=>este dato se obtiene de s4
                            'precipitation'    => $webservice_register->precipitation,
                            'wind_direction'    => $webservice_register->wind_direction,
                            'wind_velocity'    => $webservice_register->wind_velocity,
                            'created_at'  => $formated_date,
                            'battery_level' => $webservice_register->battery_level,
                            'id_origen'    => $webservice_register->id
                        ];



                        $this->createFakeRegister($dEstaciones[0]->id, $data);
                    }
                    /*else
                    {
                        echo $webservice_register->id." ya registrado";
                    }*/
                }
            } else {
                return 'no hay datos que almacenar.';
            }
        } else {
            return 'Error en la peticion';
        }
        return 'se termino';
    }

    private function createFakeRegister($estacion_id, $data)
    {
        $faker      = Faker::create();
        $fake_data = [
            'estacion_id'      => $estacion_id,
            'id_origen'       => $data['id_origen'],
            'temperatura'      => $data['temperature'],
            'humedad_relativa' => $data['humidity'],
            'precipitacion_acumulada' => $data['precipitation'],
            'brillo' => $data['brightness'],
            'presion' => $data['pressure'],
            'bateria' => $data['battery_level'],
            'direccion_viento' => $data['wind_direction'],
            'velocidad_viento' => $data['wind_velocity'],
            'created_at' => $data['created_at'],
            'updated_at' => $data['created_at'],
        ];

        DB::table('estacion_dato')->insert($fake_data);
    }



    public function marcaIdeal($estacion_id)
    {

        $qDatos = "SELECT ve.*, v.nombre_alerta 
        FROM tipo_cultivo_variable ve 
        INNER JOIN tipo_cultivos e ON e.id=ve.tipo_cultivo_id 
        INNER JOIN variables_medicion v ON v.id=ve.variable_id
        WHERE e.id IN (SELECT tipo_cultivo_id FROM  zona_manejos ev  WHERE id='" . $estacion_id . "')
        ";

        $array = array();
        DB::connection()->enableQueryLog();
        $register = DB::select(DB::raw($qDatos));
        $queries = DB::getQueryLog();
        Log::info($queries);
        if (count($register) > 0) {
            foreach ($register as $registro) {
                $array[$registro->nombre_alerta] = $registro;
            }
            return response()->json(['data' => $array, 'status' => 200], 200);
        } else {
            return response()->json(['data' => 'Sin datos', 'status' => 400], 400);
        }
    }


    public function obtenerImagenGrafico($estacion_id, $imagen)
    {
        /*$qEstacionVirtual="SELECT * FROM estacion_virtual WHERE id='".$estacion_id."'";
        $dEstacionVirtual=DB::select( DB:: raw($qEstacionVirtual) );
        $ids=explode(",",$dEstacionVirtual[0]->id_compuesto);*/

        $qDatos = "SELECT ve.imagen_grafico as imagen, ve.imagen_colorimetria as colorimetria 
        FROM tipo_cultivo_variable ve 
        INNER JOIN tipo_cultivos e ON e.id=ve.tipo_cultivo_id 
        INNER JOIN variables_medicion v ON v.id=ve.variable_id
        WHERE (v.nombre='" . $imagen . "' OR v.nombre_colorimetria='" . $imagen . "') AND e.id IN (SELECT tipo_cultivo_id FROM  zona_manejos ev WHERE ev.id='" . $estacion_id . "')
        ";

        DB::connection()->enableQueryLog();
        $register = DB::select(DB::raw($qDatos));
        $queries = DB::getQueryLog();
        Log::info($queries);
        if (count($register) > 0) {
            //echo $imagen;
            if (strpos($imagen, 'colorimetria') !== false) {
                //echo $register[0]->colorimetria;
                $img = file_get_contents($register[0]->colorimetria);
            } else {
                //echo $register[0]->imagen;
                $img = file_get_contents($register[0]->imagen);
            }
            $img = base64_encode($img);
            return response()->json(['data' => $img, 'status' => 200], 200);
        } else {
            return response()->json(['data' => 'Sin datos', 'status' => 400], 400);
        }


        //return response()->json(['data' => $img, 'status' => 200], 200);
    }

    public function guardarSigfox(Request $request)
    {
        $contents = 'Fecha:' . date('Ymd-H:m:s') . '==';
        foreach ($request->all() as $key => $value) {
            $contents .= $key . ":" . $value . ','; // or use `"\r\n"`            
        }
        $contents .= "\n";
        file_put_contents('sigfox.txt', $contents, FILE_APPEND);
    }

    public function guardarS4iot(Request $request)
    {
        try {
            // Mantener compatibilidad con $_GET directo si no se pasa Request
            if ($request === null) {
                $request = new \Illuminate\Http\Request($_GET);
            }

            // Log de los datos recibidos tal como llegan (sin procesar)
            $rawData = [
                'timestamp' => now()->toDateTimeString(),
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
                'url' => $_SERVER['REQUEST_URI'] ?? 'UNKNOWN',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN',
                'all_get_params' => $_GET,
                'all_post_params' => $_POST,
                'all_request_params' => $request->all(),
                'raw_input' => file_get_contents('php://input'),
                'headers' => getallheaders()
            ];

            // Guardar log detallado de datos crudos
            $logPath = storage_path('logs/s4iot_raw_data_' . date('Ymd') . '.log');
            file_put_contents($logPath, json_encode($rawData, JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);

            // Log de los datos recibidos para debugging
            Log::info('Datos recibidos de S4IoT Lambda', $request->all());

            // Validar que el parámetro 'msg' existe y es válido
            if (!$request->has('msg')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parámetro "msg" requerido'
                ], 400);
            }

            $msg = $request->get('msg');

            // Log del parámetro msg tal como llega
            $msgLogPath = storage_path('logs/s4iot_msg_raw_' . date('Ymd') . '.log');
            file_put_contents(
                $msgLogPath,
                "=== " . now()->toDateTimeString() . " ===\n" .
                    "MSG PARAMETER: " . $msg . "\n" .
                    "MSG LENGTH: " . strlen($msg) . "\n" .
                    "MSG TYPE: " . gettype($msg) . "\n" .
                    "=====================================\n\n",
                FILE_APPEND
            );

            // Validar que msg es un JSON válido
            if (!is_string($msg)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El parámetro "msg" debe ser una cadena JSON válida'
                ], 400);
            }

            $vars = json_decode($msg);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'success' => false,
                    'message' => 'JSON inválido en el parámetro "msg": ' . json_last_error_msg()
                ], 400);
            }

            // Log del JSON decodificado
            $jsonLogPath = storage_path('logs/s4iot_json_decoded_' . date('Ymd') . '.log');
            file_put_contents(
                $jsonLogPath,
                "=== " . now()->toDateTimeString() . " ===\n" .
                    "DECODED JSON: " . json_encode($vars, JSON_PRETTY_PRINT) . "\n" .
                    "=====================================\n\n",
                FILE_APPEND
            );

            // Validar campos requeridos
            if (!isset($vars->estacion_id) || !isset($vars->transaccion_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Campos requeridos: estacion_id, transaccion_id'
                ], 400);
            }

            // Configurar zona horaria
            $formated_date = Carbon::now('UTC');
            $formated_date->setTimezone('America/Mexico_City');

            // Log de los datos para debugging
            $logData = [
                'estacion_id' => $vars->estacion_id,
                'transaccion_id' => $vars->transaccion_id,
                'fecha' => $formated_date->toDateTimeString(),
                'datos_completos' => $vars
            ];
            file_put_contents('s4iot_log_' . date('Ymd') . '.txt', json_encode($logData, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);

            // Buscar la estación de forma segura
            $estacion = Estaciones::where('uuid', $vars->estacion_id)->first();

            if (!$estacion) {
                Log::warning('Estación no encontrada', ['uuid' => $vars->estacion_id]);
                return response()->json([
                    'success' => false,
                    'message' => 'Estación no encontrada con UUID: ' . $vars->estacion_id
                ], 404);
            }

            $data = [
                'estacion_id' => $estacion->id,
                'id_origen' => $vars->transaccion_id,
            ];

            // Hack para fechas incorrectas (año 1980)
            if ($formated_date->year == 1980) {
                $anterior = EstacionDato::where('estacion_id', $estacion->id)
                    ->where('estacion_id', $estacion->id)
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($anterior) {
                    $formated_date = Carbon::createFromFormat('Y-m-d H:i:s', $anterior->created_at)->addMinutes(15);
                }
            }

            // Variables atmosféricas con validación
            $data['temperatura'] = $this->getValidatedValue($vars, ['atemp_lv1', 'temp_sns_lv1'], 100);
            $data['temperatura_lvl1'] = $this->getValidatedValue($vars, ['atemp_lv1', 'temp_sns_lv1'], 100);
            $data['humedad_relativa'] = $this->getValidatedValue($vars, ['ahum_lv1', 'hum_sns_lv1'], 100);
            $data['co2'] = $this->getValidatedValue($vars, ['co2_sns_lv1'], 100, false);

            // Precipitación acumulada (procesar independientemente)
            $data['precipitacion_acumulada'] = $this->getValidatedValue($vars, ['preci_lv1'], 100, false) ?? 0;

            // Procesar datos de viento si existen
            if (isset($vars->anemo) && isset($vars->direc)) {
                $windData = $this->processWindData($vars);
                $data = array_merge($data, $windData);
            }

            // Variables de suelo
            $data['humedad_15'] = $this->getValidatedValue($vars, ['hum_lv1'], 100, false, 2) ??
                $this->getValidatedValue($vars, ['hum_npk_lv1'], 10, false, 2);
            $data['temperatura_suelo'] = $this->getValidatedValue($vars, ['temp_npk_lv1'], 10, false, 2) ??
                $this->getValidatedValue($vars, ['temp_lv1'], 100, false, 2);
            $data['nit'] = $this->getValidatedValue($vars, ['nit_npk_lv1'], 1, false, 2);
            $data['ph'] = $this->getValidatedValue($vars, ['ph_npk_lv1'], 100, false, 2);
            $data['phos'] = $this->getValidatedValue($vars, ['phos_npk_lv1'], 1, false, 2);
            $data['pot'] = $this->getValidatedValue($vars, ['pot_npk_lv1'], 1, false, 2);

            // Conductividad eléctrica
            $data['conductividad_electrica'] = $this->getValidatedValue($vars, ['conductividad', 'cond_lv1', 'cond_lv2', 'cond_npk_lv1'], 1000, false);

            // Batería
            if (isset($vars->voltaje)) {
                $data['bateria'] = ($vars->voltaje * 0.0762) - 224.46;
            }

            $data['created_at'] = $formated_date;
            $data['updated_at'] = $formated_date;

            // Validar duplicados antes de insertar
            $existingRecord = DB::table('estacion_dato')
                ->where('created_at', $formated_date)
                ->where('id_origen', $data['id_origen'])
                ->first();

            if ($existingRecord) {
                Log::info('Registro duplicado encontrado', [
                    'id_origen' => $data['id_origen'],
                    'fecha' => $formated_date->toDateTimeString()
                ]);
                return response()->json([
                    'success' => true,
                    'message' => 'Registro duplicado, no se insertó'
                ], 200);
            }

            // Insertar datos
            DB::table('estacion_dato')->insert($data);

            // Procesar alertas de enfermedades
            $this->processDiseaseAlerts($data);

            return response()->json([
                'success' => true,
                'message' => 'Datos guardados correctamente',
                'estacion_id' => $estacion->id,
                'transaccion_id' => $vars->transaccion_id
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error en guardarS4iot', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request ? $request->all() : $_GET
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Obtiene un valor validado de las variables con división opcional
     */
    private function getValidatedValue($vars, $keys, $divisor = 1, $required = true, $decimals = 2)
    {
        foreach ($keys as $key) {
            if (isset($vars->$key)) {
                return round($vars->$key / $divisor, $decimals);
            }
        }

        return $required ? 0 : null;
    }

    /**
     * Procesa los datos de viento
     */
    private function processWindData($vars)
    {
        $wind_velocity = 0;
        $velocidades = json_decode(json_encode($vars->anemo), true);

        foreach ($velocidades as $key => $value) {
            $velocidad = reset($value);
            if ($velocidad > 0) {
                $wind_velocity = $velocidad;
                break;
            }
        }

        $direccion = json_decode(json_encode($vars->direc), true);

        // Transformar el arreglo a asociativo
        foreach ($direccion as $key => $value) {
            $direccion[key($value)] = reset($value);
            unset($direccion[$key]);
        }

        $direccion = array_count_values($direccion);
        arsort($direccion);

        $wind_direction = $this->wind_cardinals(key($direccion) / 10);
        $wind_velocity = $wind_velocity / 100;

        return [
            'direccion_viento' => $wind_direction,
            'velocidad_viento' => $wind_velocity
        ];
    }

    /**
     * Procesa las alertas de enfermedades (misma función que StationController)
     */
    private function processDiseaseAlerts($data)
    {
        try {
            $enfermedades = DB::select("SELECT ee.*  FROM enfermedades e INNER JOIN tipo_cultivos_enfermedades ee ON ee.enfermedad_id=e.id WHERE 1");

            foreach ($enfermedades as $enfermedad) {
                //Vamos por el registro en la tabla que acumula las horas
                $qEnfermedadHoras = "SELECT * FROM enfermedad_horas_condiciones WHERE tipo_cultivo_id='" . $enfermedad->tipo_cultivo_id . "' AND enfermedad_id='" . $enfermedad->enfermedad_id . "' AND estacion_id='" . $data['estacion_id'] . "'";
                $dEnfermedadHoras = DB::select($qEnfermedadHoras);

                //Si no existe el registro lo creamos
                if (count($dEnfermedadHoras) == 0) {
                    $dataHoras = array();
                    $dataHoras['fecha_ultima_transmision'] = $data['created_at'];
                    $dataHoras['enfermedad_id'] = $enfermedad->enfermedad_id;
                    $dataHoras['tipo_cultivo_id'] = $enfermedad->tipo_cultivo_id;
                    $dataHoras['estacion_id'] = $data['estacion_id'];
                    $dataHoras['minutos'] = 0;

                    try {
                        DB::table('enfermedad_horas_condiciones')->insert($dataHoras);
                    } catch (QueryException $ex) {
                        Log::info($ex->getMessage());
                    }
                }

                //Si se cumplen las condiciones de riesgo entonces se acumulan las horas
                if ($data['humedad_relativa'] >= $enfermedad->riesgo_humedad && $data['humedad_relativa'] <= $enfermedad->riesgo_humedad_max && $data['temperatura'] >= $enfermedad->riesgo_temperatura && $data['temperatura'] <= $enfermedad->riesgo_temperatura_max) {
                    if ($dEnfermedadHoras[0]->minutos == 0)
                        $minutosTranscurridos = 1;
                    else
                        $minutosTranscurridos = abs((strtotime($data['created_at']) - strtotime($dEnfermedadHoras[0]->fecha_ultima_transmision))) / 60;
                    DB::update("UPDATE enfermedad_horas_condiciones SET fecha_ultima_transmision='" . $data['created_at'] . "', minutos=minutos+" . $minutosTranscurridos . " WHERE tipo_cultivo_id='" . $enfermedad->tipo_cultivo_id . "' AND enfermedad_id='" . $enfermedad->enfermedad_id . "' AND estacion_id='" . $data['estacion_id'] . "'");
                } else {
                    //Antes de reiniciar el contador registrar en historial las horas acumuladas hasta el momento 
                    $minutosAcumulados = $dEnfermedadHoras[0]->minutos ?? 0;

                    if ($minutosAcumulados > 0) {
                        DB::insert("INSERT INTO enfermedad_horas_acumuladas_condiciones SET fecha='" . $data['created_at'] . "', created_at='" . $data['created_at'] . "', minutos='" . $minutosAcumulados . "', tipo_cultivo_id='" . $enfermedad->tipo_cultivo_id . "', enfermedad_id='" . $enfermedad->enfermedad_id . "', estacion_id='" . $data['estacion_id'] . "'");
                    }

                    // Guardar marcador explícito de reinicio (minutos = 0) evitando duplicados consecutivos
                    $ultimoRegistro = DB::table('enfermedad_horas_acumuladas_condiciones')
                        ->where('tipo_cultivo_id', $enfermedad->tipo_cultivo_id)
                        ->where('enfermedad_id', $enfermedad->enfermedad_id)
                        ->where('estacion_id', $data['estacion_id'])
                        ->orderBy('fecha', 'desc')
                        ->first();

                    if (!$ultimoRegistro || (int)($ultimoRegistro->minutos ?? -1) !== 0) {
                        DB::insert("INSERT INTO enfermedad_horas_acumuladas_condiciones SET fecha='" . $data['created_at'] . "', created_at='" . $data['created_at'] . "', minutos='0', tipo_cultivo_id='" . $enfermedad->tipo_cultivo_id . "', enfermedad_id='" . $enfermedad->enfermedad_id . "', estacion_id='" . $data['estacion_id'] . "'");
                    }

                    // Reiniciar el contador de la tabla de estado
                    DB::update("UPDATE enfermedad_horas_condiciones SET fecha_ultima_transmision='" . $data['created_at'] . "', minutos=0 WHERE tipo_cultivo_id='" . $enfermedad->tipo_cultivo_id . "' AND enfermedad_id='" . $enfermedad->enfermedad_id . "' AND estacion_id='" . $data['estacion_id'] . "'");
                }
            }
        } catch (\Exception $e) {
            Log::error("Error procesando alertas de enfermedades: " . $e->getMessage());
        }
    }


    public function alertaEnfermedades()
    {
        date_default_timezone_set('America/Mexico_City');
        //Esta función deberá ejecutarse cada hora
        $startRange = date('Y-m-d 00:00:00');
        $endRange   = Carbon::now();
        //1. Seleccionar todas las estaciones atmosféricas que tengan transmisiones en las últimas 24 horas
        $qEstaciones = "SELECT * FROM estaciones ie";
        $dEstaciones = DB::select($qEstaciones);
        $alertas = array();
        foreach ($dEstaciones as $estacion) {

            //2. Por cada estación extraer el promedio por hora de las 24 horas del día anterior
            $qTemperatura = "SELECT * FROM estacion_dato WHERE created_at>='" . $startRange . "' AND created_at<='" . $endRange . "' AND estacion_id='" . $estacion->id . "' AND humedad_relativa IS NOT NULL AND temperatura IS NOT NULL GROUP BY HOUR(created_at)";
            $mediciones = DB::select(DB::raw($qTemperatura));
            if (count($mediciones) == 0)
                continue;

            //3. Evaluamos enfermedad por enfermedad con las mediciones de la estación
            $qEndermedades = "SELECT * 
                            FROM enfermedades e
                            JOIN tipo_cultivos_enfermedades ef on e.id = enfermedad_id ";
            $dEnfermedades = DB::select(DB::raw($qEndermedades));
            foreach ($dEnfermedades as $enfermedad) {
                $contador = 0;
                foreach ($mediciones as $medicion) {
                    //Mientras se cumpla esta condición sumamos al contador de riesgo
                    if ($medicion->humedad_relativa >= $enfermedad->riesgo_humedad && $medicion->temperatura >= $enfermedad->riesgo_temperatura)
                        $contador++;
                    else
                        $contador = 0;

                    //En cada vuelta evaluamos si se han alcanzado las condiciones de riesgo; de ser así no tiene
                    //sentido seguir el recorrido de mediciones pues solo se manejará una alerta por día
                    if ($contador == $enfermedad->riesgo_mediciones) {
                        $alerta = 1;
                        break;
                    } else
                        $alerta = 0;
                }


                //Tras tener los datos para la estación y una enfermedad se instera la alerta
                if ($alerta == 1) {
                    $qEstacionVirtual = "SELECT zm.* FROM zona_manejos zm JOIN zona_manejos_estaciones zme ON zm.id = zme.zona_manejos_id WHERE zme.estacion_id = " . $estacion->id;
                    $dEstacionVirtual = DB::select(DB::raw($qEstacionVirtual));
                    $data = array();
                    foreach ($dEstacionVirtual as $estacionVirtual) {
                        $data['fecha'] = $startRange;
                        $data['enfermedad_id'] = $enfermedad->enfermedad_id;
                        $data['estacion_id'] = $estacion->id;
                        $data['parcela_id'] = $estacionVirtual->parcela_id;
                        $data['horas'] = $enfermedad->riesgo_mediciones;

                        //Primero validamos si ya existe esta alerta
                        try {
                            DB::table('alerta_enfermedad')->insert($data);
                        } catch (QueryException $ex) {
                            echo ($ex->getMessage());
                        }
                    }
                }
            }
        }
    }



    /*public function detalleAlertaEnfermedades()
    {
        $xHoras=72;
        $xMedicionas=6;
        $xTemperatura=27;
        $xHumedad=60;
        $startRange = Carbon::now()->subHours($xHoras);
        $endRange   = Carbon::now();
        //Consultar todas las transmisiones de las últimas $xHoras
        $qTemperatura="SELECT * FROM estacion_dato WHERE created_at>='".$startRange."' AND created_at<='".$endRange."' AND humedad_relativa IS NOT NULL AND temperatura IS NOT NULL GROUP BY HOUR(created_at), estacion_id";
        $mediciones=DB::select( DB:: raw($qTemperatura) );
        $alertas=array();
        foreach($mediciones as $medicion)
        {
            if($medicion->humedad_relativa>=$xHumedad && $medicion->temperatura>=$xTemperatura)
                $alertas[$medicion->estacion_id]+=1;
            else
                $alertas[$medicion->estacion_id]=0;
        }
        echo "<pre>";
        print_r($alertas);


    }*/


    public function alertaTransmision()
    {
        $startRange = Carbon::now()->subMinutes(300);
        $qDatos = "SELECT MAX(ed.created_at) as created_at, ed.estacion_id FROM estacion_dato ed INNER JOIN estaciones i ON i.id=ed.estacion_id GROUP BY ed.estacion_id";
        $estaciones = DB::select(DB::raw($qDatos));
        $est = '';
        foreach ($estaciones as $estacion) {
            if ($estacion->created_at < $startRange) {
                $est .= $estacion->estacion_id . ",";
                /*try
                {*/

                /*}
                catch(Exception $e){
                    dd($e);
                }*/
                echo $estacion->estacion_id . "<br>";
            }
        }
        if ($est != '') {
            Mail::raw('Alerta, las estaciones ' . $est . ' no están transmitiendo', function ($message) {
                $message->to("notificaciones@appgricolaapi.solucionesoftware.com.mx")
                    ->subject("Alerta de transmisión");
            });
        }
    }




    public function listaEnfermedades($id)
    {
        $enfermedades = array();
        $startRange = date('Y-m-d');
        $qEnfermedades = "SELECT e.id as id, e.nombre as nombre, e.slug as slug, 
        (SELECT COUNT(*) FROM alerta_enfermedad ae  
         LEFT JOIN zona_manejos zm on ae.parcela_id = zm.parcela_id
         WHERE ae.enfermedad_id = e.id AND zm.id = '" . $id . "'
        and fecha = '$startRange') as alertas FROM enfermedades e  
            INNER JOIN tipo_cultivos_enfermedades ee ON ee.enfermedad_id=e.id 
            INNER JOIN zona_manejos zm ON zm.tipo_cultivo_id=ee.tipo_cultivo_id WHERE zm.id='" . $id . "' ORDER BY nombre ASC";
        $dEnfermedades = DB::select(DB::raw($qEnfermedades));
        foreach ($dEnfermedades as $enfermedad) {

            $enfermedades[] = array('nombre' => $enfermedad->nombre, 'peticion' => $enfermedad->slug, 'depth' => 1, 'wait' => 0, 'alertas' => $enfermedad->alertas);
        }


        //$qEnfermedades="SELECT e.nombre as nombre, slug as peticion, 1 as alertas, 1 as depth, 0 as wait FROM enfermedad e INNER JOIN alerta_enfermedad a ON a.enfermedad_id=e.id WHERE fecha='".$startRange."' AND parcela_id='".$id."'";
        //$enfermedades=DB::select( DB:: raw($qEnfermedades) );

        return response()->json(['data' => $enfermedades, 'status' => 200], 300);
    }

    public function detalleEnfermedad($parcela, $enfermedad)
    {
        date_default_timezone_set('America/Mexico_City');



        $startRange = date('Y-m-d 00:00:00');
        $endRange   = Carbon::now();
        //Obtenemos los datos de la enfermedad en curso
        $qEnfermedad = "SELECT ef.* FROM enfermedades e
                    JOIN tipo_cultivos_enfermedades ef ON e.id = enfermedad_id
                    JOIN zona_manejos zm ON zm.tipo_cultivo_id=ef.tipo_cultivo_id 
                    JOIN parcelas p ON p.id = zm.parcela_id
                    WHERE  slug='" . $enfermedad . "'";
        $dEnfermedad = DB::select(DB::raw($qEnfermedad))[0];

        //Ahora me interesa conocer el número de horas durante las cuales se han presentado
        //las condiciones de riesgo de la enfermedad
        $qTemperatura = "SELECT * FROM estacion_dato WHERE created_at>='" . $startRange . "' AND created_at<='" . $endRange . "' AND estacion_id IN (SELECT estacion_id FROM zona_manejos_estaciones WHERE zona_manejos_id = '" . $parcela . "') AND humedad_relativa >='" . $dEnfermedad->riesgo_humedad . "' AND temperatura >='" . $dEnfermedad->riesgo_temperatura . "' GROUP BY HOUR(created_at)";
        $mediciones = DB::select(DB::raw($qTemperatura));


        $enfermedad = array();
        $enfermedad['riskFactor'] = array('humidity' => '>' . $dEnfermedad->riesgo_humedad, 'temperature' => '>' . $dEnfermedad->riesgo_temperatura);

        //Obtenemos la última medición de temperatura y humedad
        $qUltima = "SELECT * FROM estacion_dato WHERE  estacion_id IN (SELECT estacion_id FROM zona_manejos_estaciones WHERE zona_manejos_id = '" . $parcela . "') AND humedad_relativa IS NOT NULL AND  humedad_relativa>0 ORDER BY created_at DESC LIMIT 1";
        $dUltima = DB::select(DB::raw($qUltima));

        if (empty($dUltima))
            $enfermedad['actualCondition'] = array('humidity' => null, 'temperature' => null);
        else
            $enfermedad['actualCondition'] = array('humidity' => $dUltima[0]->humedad_relativa, 'temperature' => $dUltima[0]->temperatura);

        if (count($mediciones) <= 0) {
            $enfermedad['hoursAccumulated'] = 0;
            $nHoras = 0;
        } else {
            $enfermedad['hoursAccumulated'] = count($mediciones);
            $nHoras = count($mediciones);
        }
        if ($nHoras <= ($dEnfermedad->riesgo_medio / 2))
            $enfermedad['image'] = 1;
        if ($nHoras > ($dEnfermedad->riesgo_medio / 2) && $nHoras <= $dEnfermedad->riesgo_medio)
            $enfermedad['image'] = 2;
        if ($nHoras > $dEnfermedad->riesgo_medio && $nHoras <= ($dEnfermedad->riesgo_mediciones - $dEnfermedad->riesgo_medio) / 2)
            $enfermedad['image'] = 3;
        if ($nHoras > (($dEnfermedad->riesgo_mediciones - $dEnfermedad->riesgo_medio) / 2) && $nHoras <= $dEnfermedad->riesgo_mediciones)
            $enfermedad['image'] = 4;
        if ($nHoras > $dEnfermedad->riesgo_mediciones)
            $enfermedad['image'] = 6;

        if ($enfermedad['image'] == 1 || $enfermedad['image'] == 2)
            $enfermedad['status'] = 'NULO RIESGO DE PROPAGACIÓN';
        if ($enfermedad['image'] == 3 || $enfermedad['image'] == 4)
            $enfermedad['status'] = 'RIESGO DE PROPAGACIÓN MEDIO';
        if ($enfermedad['image'] == 5 || $enfermedad['image'] == 6)
            $enfermedad['status'] = 'PROPAGACIÓN DE LA ENFERMEDAD';


        //Extraemos el pronóstico del clima para los siguientes 5 días de esta parcela
        $fInicio = explode(" ", Carbon::now()->addDay(1));
        $fFin = explode(" ", Carbon::now()->addDay(5));

        //Obtenemos el dato de la estación virtual
        $qVirtual = "SELECT * FROM zona_manejos WHERE id='" . $parcela . "'";
        $dVirtual = DB::select(DB::raw($qVirtual));

        $qPronostico = "SELECT * FROM forecast WHERE fecha_prediccion>='" . $fInicio[0] . "' AND fecha_prediccion<='" . $fFin[0] . "' AND parcela_id='" . $dVirtual[0]->parcela_id . "' AND fecha_solicita='" . date('Y-m-d') . "'";
        $dPronostico = DB::select(DB::raw($qPronostico));
        foreach ($dPronostico as $pronostico) {
            $nameOfDay = date('w', strtotime($pronostico->fecha_prediccion));
            switch ($nameOfDay) {
                case 0:
                    $dia = 'Domingo';
                    break;
                case 1:
                    $dia = 'Lunes';
                    break;
                case 2:
                    $dia = 'Martes';
                    break;
                case 3:
                    $dia = 'Miércoles';
                    break;
                case 4:
                    $dia = 'Jueves';
                    break;
                case 5:
                    $dia = 'Viernes';
                    break;
                case 6:
                    $dia = 'Sábado';
                    break;
            }

            $pronosticoHora = json_decode($pronostico->hourly, true);

            $v = 'NA';
            $color = '#ffffff';

            if (is_array($pronosticoHora)) {
                $contador = 0;
                foreach ($pronosticoHora as $hora) {
                    if (($hora['humidity'] * 100) >= $dEnfermedad->riesgo_humedad && $hora['temperature'] >= $dEnfermedad->riesgo_temperatura)
                        $contador++;
                }


                if ($contador <= ($dEnfermedad->riesgo_medio / 2)) {
                    $v = 'N';
                    $color = '#00FF00';
                }
                if ($contador > ($dEnfermedad->riesgo_medio / 2) && $contador <= $dEnfermedad->riesgo_medio) {
                    $v = 'N';
                    $color = '#00FF00';
                }
                if ($contador > $dEnfermedad->riesgo_medio && $contador <= ($dEnfermedad->riesgo_mediciones - $dEnfermedad->riesgo_medio) / 2) {
                    $v = 'M';
                    $color = '#e0a204';
                }
                if ($contador > (($dEnfermedad->riesgo_mediciones - $dEnfermedad->riesgo_medio) / 2) && $contador <= $dEnfermedad->riesgo_mediciones) {
                    $v = 'M';
                    $color = '#e0a204';
                }
                if ($contador > $dEnfermedad->riesgo_mediciones) {
                    $v = 'A';
                    $color = '#ff0000';
                }
            }
            if ($v == 'NA')
                $enfermedad['forecast'][] = array('day' => '', 'value' => '', 'color' => '#ffffff');
            else
                $enfermedad['forecast'][] = array('day' => $dia, 'value' => $v, 'color' => $color);
        }

        return response()->json(['data' => $enfermedad, 'status' => 200], 300);
    }

    public function listaPlagas($id)
    {
        $especie = "SELECT tipo_cultivo_id, parcela_id FROM zona_manejos
                    WHERE  id = '" . $id . "'";
        $especie = DB::select(DB::raw($especie))[0];
        $data = [];
        $qPlagas = "SELECT nombre as nombre, id as peticion, 1 as depth, 0 as wait
        FROM plagas
        where tipo_cultivo_id = " . $especie->tipo_cultivo_id . " 
        ORDER BY nombre ASC";
        $ex = DB::select(DB::raw($qPlagas));

        foreach ($ex as $key => $value) {
            $data[$key]['id'] = $value->peticion;
            $data[$key]['nombre'] = $value->nombre;
            $data[$key]['peticion'] = $value->peticion;
            $data[$key]['depth'] = 1;
            $data[$key]['wait'] = 0;
            $uc = DB::select(DB::raw(
                "SELECT id FROM unidades_calor
                where plaga_id = " . $value->peticion . "
                and parcela_id = " . $especie->parcela_id . "
                and fecha = '" . date('Y-m-d') . "'"
            ));
            if (!empty($uc))
                $data[$key]['alertas'] = 1;
        }
        //$plagas=DB::select( DB:: raw($qPlagas) );

        return response()->json(['data' => $data, 'status' => 200], 300);
    }

    public function detallePlaga($parcela, $plaga)
    {
        date_default_timezone_set('America/Mexico_City');

        $startRange = date('Y-m-d 00:00:00');
        $endRange   = Carbon::now();

        $enfermedades = "SELECT plaga.* FROM zona_manejos
                    join plagas plaga on FIND_IN_SET( zona_manejos.tipo_cultivo_id, plaga.tipo_cultivo_id)
                    WHERE zona_manejos.id = " . $parcela . "
                    and plaga.id='" . $plaga . "'";
        $plagas = DB::select(DB::raw($enfermedades));

        $qVirtual = "SELECT * FROM estacion_virtual WHERE id='" . $parcela . "'";
        $dVirtual = DB::select(DB::raw($qVirtual));

        $qUca = "SELECT count(unidades_calor) AS uc FROM unidades_calor WHERE plaga_id='" . $plagas[0]->id . "' AND parcela_id='" . $parcela . "' AND fecha = '" . date('Y-m-d') . "' order by fecha desc";
        $uca = DB::select(DB::raw($qUca))[0];



        $qUcaY = "SELECT count(unidades_calor) AS uc FROM unidades_calor WHERE plaga_id='" . $plagas[0]->id . "' AND parcela_id='" . $parcela . "' AND fecha between '" . date('Y-01-01') . "' and  '" . date('Y-m-d') . "' order by fecha desc";
        $ucaY = DB::select(DB::raw($qUcaY))[0];


        $plagaArr = array(
            'image' => base64_encode(file_get_contents($plagas[0]->imagen)),
            'nombre' => $plagas[0]->nombre,
            'unidadesCalor' => $uca->uc,
            'ciclos' => round($ucaY->uc / $plagas[0]->unidades_calor_ciclo),
            'unidadesCalorY' => $ucaY->uc,
            'umbralMinimo' => $plagas[0]->umbral_min,
        );

        return response()->json(['data' => $plagaArr, 'status' => 200], 200);
    }

    public function conteoAlertas($parcela)
    {
        Log::info("Obteniendo cantidad de alertas para la parcela");
    }

    public function alertaPlagas()
    {

        $uca = array();


        $fecha_inicial = Carbon::create()->subHours(1)->format('Y-m-d H:00:00');
        $fecha_final   = Carbon::create()->subHours(1)->format('Y-m-d H:59:59');

        //Obtenemos los umbrales de cada una de las plagas de la plataforma
        $qPlagas = "SELECT * FROM plagas WHERE tipo_cultivo_id IS NOT NULL AND umbral_min is not null and umbral_max is not null";
        $plagas = DB::select(DB::raw($qPlagas));

        foreach ($plagas as $key => $plaga) {

            //Deducimos la parcela a partir de la estacion_id
            $qEstacionVirtual = "SELECT * FROM zona_manejos WHERE tipo_cultivo_id in (" . $plaga->tipo_cultivo_id . ")";
            $dEstacionVirtual = DB::select(DB::raw($qEstacionVirtual));

            if (empty($dEstacionVirtual))
                continue;

            foreach ($dEstacionVirtual as $key => $estacion) {
                //Primero obtenemos la temperatura media por estación para el día que vamos a evaluar
                $qMedias = "SELECT avg(temperatura) as temperatura, estacion_id FROM estacion_dato 
                    WHERE temperatura is not null
                    and created_at between '" . $fecha_inicial . "' AND '" . $fecha_final . "'
                    AND estacion_id IN (SELECT estacion_id FROM zona_manejos_estaciones WHERE zona_manejos_id = " . $estacion->id . ")";
                $medias = DB::select(DB::raw($qMedias));

                if (!empty($medias)) {
                    foreach ($medias as $key => $media) {
                        if ($media->temperatura == null)
                            continue;

                        if ($media->temperatura > $plaga->umbral_min && $media->temperatura < $plaga->umbral_max) {

                            $uca = array(
                                'fecha' => $fecha_inicial,
                                'unidades_calor' => 1,
                                'plaga_id' => $plaga->id,
                                'tipo_cultivo_id' => $estacion->tipo_cultivo_id,
                                'parcela_id' => $estacion->id,
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s')
                            );

                            //Insertamos esto en la tabla de unidades_calor
                            DB::table('unidades_calor')->insert($uca);
                        }
                    }
                } else {
                    continue;
                }
            }
        }
    }

    public function alertaPlagasUpdate()
    {

        $uca = array();


        $fecha_inicial = Carbon::createFromFormat('Y-m-d H', '2021-01-01 00');
        $fecha_final   = Carbon::create()->format('Y-04-26 23:00:00');



        while ($fecha_inicial <= $fecha_final) {
            echo  $fecha_inicial->format('Y-m-d H:i') . "|";
            $fecha_inicial->addHours(1);


            //Obtenemos los umbrales de cada una de las plagas de la plataforma
            $qPlagas = "SELECT * FROM plagas WHERE tipo_cultivo_id IS NOT NULL AND umbral_min is not null and umbral_max is not null";
            $plagas = DB::select(DB::raw($qPlagas));

            foreach ($plagas as $key => $plaga) {

                //Deducimos la parcela a partir de la estacion_id
                $qEstacionVirtual = "SELECT * FROM zona_manejos WHERE tipo_cultivo_id in (" . $plaga->tipo_cultivo_id . ")";
                $dEstacionVirtual = DB::select(DB::raw($qEstacionVirtual));

                if (empty($dEstacionVirtual))
                    continue;

                foreach ($dEstacionVirtual as $key => $estacion) {
                    //Primero obtenemos la temperatura media por estación para el día que vamos a evaluar
                    $qMedias = "SELECT avg(temperatura) as temperatura, estacion_id FROM estacion_dato 
                    WHERE temperatura is not null
                    and created_at between '" . $fecha_inicial . "' AND '" . $fecha_final . "'
                    AND estacion_id IN (SELECT estacion_id FROM zona_manejos_estaciones WHERE zona_manejos_id = " . $estacion->id . ")";
                    $medias = DB::select(DB::raw($qMedias));

                    if (!empty($medias)) {
                        foreach ($medias as $key => $media) {
                            if ($media->temperatura == null)
                                continue;

                            if ($media->temperatura > $plaga->umbral_min && $media->temperatura < $plaga->umbral_max) {

                                $uca = array(
                                    'fecha' => $fecha_inicial,
                                    'unidades_calor' => 1,
                                    'plaga_id' => $plaga->id,
                                    'tipo_cultivo_id' => $estacion->tipo_cultivo_id,
                                    'parcela_id' => $estacion->id,
                                    'created_at' => date('Y-m-d H:i:s'),
                                    'updated_at' => date('Y-m-d H:i:s')
                                );

                                //Insertamos esto en la tabla de unidades_calor
                                DB::table('unidades_calor')->insert($uca);
                            }
                        }
                    } else {
                        continue;
                    }
                }
            }
        }
    }

    public function estacionCerritos()
    {
        $salida = shell_exec('curl -H "Authorization: Token c8016b7f1443b118ea2b582e9b443d9805e938f4" http://globalmet.mx/estaciones/conditions/71/');
        //Convertimos la respuesta en un arreglo
        $datos = json_decode($salida, true);
        echo "<pre>";
        print_r($datos);
        $ms = $datos['current_observation']['wind_kph'] * 1000 / 3600;
        $data = [
            'temperature' => round((($datos['current_observation']['extraTemp1'] - 32) * 5 / 9), 2),
            'humidity'    => str_replace("%", "", $datos['current_observation']['extraHumid1']),
            'brightness'    => $datos['current_observation']['solarradiation'],
            'pressure'    => $datos['current_observation']['pressure_mb'],
            'battery_level'    => 100,
            'precipitation'    => $datos['current_observation']['precip_today_metric'],
            'wind_direction'    => $datos['current_observation']['wind_dir'],
            'wind_velocity'    => $ms,
            'created_at'  => date('Y-m-d H:i:s'),
            'id_origen'    => $datos['current_observation']['local_epoch']
        ];

        file_put_contents('test.txt', $salida, FILE_APPEND);

        $this->createFakeRegister(17, $data);
    }

    public function wind_cardinals($deg)
    {
        $cardinalDirections = array(
            'N' => array(348.75, 360),
            'N' => array(0, 11.25),
            'NNE' => array(11.25, 33.75),
            'NE' => array(33.75, 56.25),
            'ENE' => array(56.25, 78.75),
            'E' => array(78.75, 101.25),
            'ESE' => array(101.25, 123.75),
            'SE' => array(123.75, 146.25),
            'SSE' => array(146.25, 168.75),
            'S' => array(168.75, 191.25),
            'SSW' => array(191.25, 213.75),
            'SW' => array(213.75, 236.25),
            'WSW' => array(236.25, 258.75),
            'W' => array(258.75, 281.25),
            'WNW' => array(281.25, 303.75),
            'NW' => array(303.75, 326.25),
            'NNW' => array(326.25, 348.75)
        );
        foreach ($cardinalDirections as $dir => $angles) {
            if ($deg >= $angles[0] && $deg < $angles[1]) {
                $cardinal = $dir;
            }
        }
        return $cardinal;
    }

    public function scriptUpdate()
    {

        $ids = [];

        $myfile = fopen("test.txt", "r") or die("Unable to open file!");
        $data =  fread($myfile, filesize("test.txt"));
        foreach ($ids as $key => $id) {

            $pos = strpos($data, '"local_epoch": "' . $id . '"');

            // Nótese el uso de ===. Puesto que == simple no funcionará como se espera
            // porque la posición de 'a' está en el 1° (primer) caracter.
            if ($pos === false)
                continue;

            while ($data[$pos] != '{') {
                $pos--;
            }

            $posIni = $pos;

            while ($data[$pos] != '}') {
                $pos++;
            }


            $posFin = $pos;

            $substr = substr($data, $posIni, $posFin - $posIni + 1);

            $jssubstr = json_decode($substr, true);


            if (!isset($jssubstr['extraTemp1']))
                dd($jssubstr);

            DB::update(DB::raw("update estacion_dato set temperatura = " . round((($jssubstr['extraTemp1'] - 32) * 5 / 9), 2) . ",  humedad_relativa = " . str_replace("%", "", $jssubstr['extraHumid1']) . " where id_origen = " . $id));
            // dd("update estacion_dato set temperatura = ".round((($jssubstr['extraTemp1']-32)*5/9),2).",  humedad_relativa = ".str_replace("%","",$jssubstr['extraHumid1'])." where id_origen = ".$id );
            echo $key . '|';
        }

        //echo $data[$pos-5].'|'.$data[$pos-4].'|'.$data[$pos-3].'|'.$data[$pos-2].'|'.$data[$pos-1].'|'.$data[$pos];
    }

    public function guardarNasa(Request $request)
    {
        $qEstacionVirtual = "select estaciones.id
            from estaciones
            left join tipo_estacion on tipo_estacion.id = tipo_estacion_id 
            where tipo_nasa = 1";

        $dEstacionVirtual = DB::select(DB::raw($qEstacionVirtual));


        $vars = [
            "ALLSKY_SFC_SW_DWN" => "insolacion",
            "PRECTOT" => "precipitacion",
            "PS" => "presion",
            "RH2M" => "humedad_relativa_2_metros",
            "T2M" => "temperatura_2_metros",
            "T2MDEW" => "punto_rocio",
            "T2MWET"  => "temperatura_bulbo_2_metros",
            "T2M_MAX" => "temperatura_maxima_2_metros",
            "T2M_MIN" => "temperatura_minima_2_metros",
            "T2M_RANGE" => "rango_temperatura_2_metros",
            "WS2M" => "velocidad_viento_2_metros",
            "TS" => "temperatura_superficie",
            "WS10M" => "velocidad_viento_10_metros",
            "WS10M_MAX" => "velocidad_maxima_viento_10_metros",
            "WS10M_MIN" => "velocidad_minima_viento_10_metros",
            "WS2M_MAX" => "velocidad_maxima_viento_2_metros",
            "WS2M_MIN" => "velocidad_minima_viento_2_metros",
            "WS50M_RANGE" => "velocidad_viento_50_metros"
        ];

        $parameters = "parameters=";


        foreach ($vars as $key => $value) {
            $parameters .= $key . ',';
        }

        $parameters = trim($parameters, ',');


        foreach ($dEstacionVirtual as $key => $estacion) {

            $qParcela = "select parcelas.*
                from parcelas
                left join zona_manejos on parcelas.id = parcela_id
                where zona_manejos.id IN (SELECT zona_manejos_id FROM zona_manejos_estaciones WHERE estacion_id = " . $estacion->id . ")";

            $dParcela = DB::select(DB::raw($qParcela));

            $dParcela = $dParcela[0];

            $date = Carbon::now();
            $date->subDays(2);
            $date->format('Ymd');

            $dateYmd = $date->format('Ymd');

            $url = "https://power.larc.nasa.gov/cgi-bin/v1/DataAccess.py?&request=execute&tempAverage=DAILY&identifier=SinglePoint&$parameters&userCommunity=AG&lon=" . $dParcela->lon . "&lat=" . $dParcela->lat . "&startDate=$dateYmd&endDate=$dateYmd&outputList=ASCII&siteElev=50&user=DOCUMENTATION";


            $client = new \GuzzleHttp\Client;

            $response =  $client->request('GET', $url);

            if ($response->getStatusCode() != 200) {
                return response()->json(['data' => "No se alcanzó servidor", 'status' => 500], 500);
            }

            $data = json_decode($response->getBody());
            echo "<pre>";
            print_r($data);
            exit;

            $data = $data->features[0]->properties->parameter;

            $insert = array();
            $insert['estacion_id'] = $estacion->id;
            $insert['parcela_id'] = $dParcela->id;

            foreach ($vars as $key => $value) {
                $insert[$value] = $data->{$key}->{$dateYmd};
            }

            $insert['created_at'] = $date->format('Y-m-d');
            $insert['updated_at'] = $date->format('Y-m-d');

            DB::table('estacion_dato_nasa')->insert($insert);
        }
    }

    public function elementos()
    {
        //Deducimos la parcela a partir de la estacion_id
        $qEstacionVirtual = "SELECT zm.id as zona_manejo_id, zm.nombre as nombre, p.nombre as parcela, p.id as parcela_id FROM zona_manejos zm INNER JOIN parcelas p ON zm.parcela_id=p.id  WHERE 1";
        $dEstacionVirtual = DB::select(DB::raw($qEstacionVirtual));

        $datos = array();
        $ayer = date("Y-m-d", strtotime("-1 day", strtotime(date('Y-m-d'))));
        foreach ($dEstacionVirtual as $key => $estacion) {
            //Calculamos las horas de sol por estación o parcela
            $qHorasSol = "SELECT TIMESTAMPDIFF(HOUR, sunriseTime, sunsetTime)/24*100 as horas, sunriseTime, sunsetTime FROM forecast WHERE parcela_id='" . $estacion->parcela_id . "' AND fecha_prediccion='" . $ayer . "' AND fecha_solicita='" . $ayer . "'";
            $dHorasSol = DB::select(DB::raw($qHorasSol));
            if (count($dHorasSol) == 0) {
                $datos[$estacion->nombre]['horas_sol'] = 'Sin dato';
            } else {
                $dHorasSol = $dHorasSol[0];
                //$datos[$estacion->nombre]['horas_sol']=$dHorasSol->horas;

                //Calculamos las temperaturas diurnas
                $qMax = "SELECT 
                MAX(temperatura) as tmp_diurna_max, 
                MIN(temperatura) as tmp_diurna_min,
                AVG(temperatura) as tmp_diurna_prom,
                MAX(temperatura) - MIN(temperatura) as amplitud_diurna
                FROM estacion_dato WHERE estacion_id IN (SELECT estacion_id FROM zona_manejos_estaciones WHERE zona_manejos_id = " . $estacion->zona_manejo_id . ") AND 
                created_at>='" . $dHorasSol->sunriseTime . "' AND created_at<='" . $dHorasSol->sunsetTime . "'";
                $dMax = DB::select(DB::raw($qMax));
                $datos[$estacion->nombre]['tmp_diurna_max'] = $dMax[0]->tmp_diurna_max;
                $datos[$estacion->nombre]['tmp_diurna_min'] = $dMax[0]->tmp_diurna_min;
                $datos[$estacion->nombre]['tmp_diurna_prom'] = $dMax[0]->tmp_diurna_prom;
                $datos[$estacion->nombre]['amplitud_diurna'] = $dMax[0]->amplitud_diurna;

                //Calculamos las temperaturas nocturnas
                $qMin = "SELECT 
                MAX(temperatura) as tmp_nocturna_max, 
                MIN(temperatura) as tmp_nocturna_min,
                AVG(temperatura) as tmp_nocturna_prom,
                MAX(temperatura) - MIN(temperatura) as amplitud_nocturna
                FROM estacion_dato WHERE estacion_id IN (SELECT estacion_id FROM zona_manejos_estaciones WHERE zona_manejos_id = " . $estacion->zona_manejo_id . ") AND
                (created_at>='" . $ayer . " 00:00:00' AND created_at<'" . $dHorasSol->sunriseTime . "') OR
                (created_at>'" . $dHorasSol->sunsetTime . "' AND created_at<='" . $ayer . " 23:59:59')";
                $dMin = DB::select(DB::raw($qMin));
                $datos[$estacion->nombre]['tmp_nocturna_max'] = $dMin[0]->tmp_nocturna_max;
                $datos[$estacion->nombre]['tmp_nocturna_min'] = $dMin[0]->tmp_nocturna_min;
                $datos[$estacion->nombre]['tmp_nocturna_prom'] = $dMin[0]->tmp_nocturna_prom;
                $datos[$estacion->nombre]['amplitud_nocturna'] = $dMin[0]->amplitud_nocturna;

                //Calculamos la media y las amplitudes
                $qDiaria = "SELECT 
                AVG(temperatura) as tmp_prom_diaria,
                MAX(temperatura) - MIN(temperatura) as amplitud_diaria
                FROM estacion_dato WHERE estacion_id IN (SELECT estacion_id FROM zona_manejos_estaciones WHERE zona_manejos_id = " . $estacion->zona_manejo_id . ") AND
                (created_at>='" . $ayer . " 00:00:00' AND created_at<='" . $ayer . " 23:59:59')";
                $dDiaria = DB::select(DB::raw($qDiaria));
                $datos[$estacion->nombre]['tmp_prom_diaria'] = $dDiaria[0]->tmp_prom_diaria;
                $datos[$estacion->nombre]['amplitud_diaria'] = $dDiaria[0]->amplitud_diaria;
            }
        }
        echo "<h1>Indicadores</h1>";
        echo "<pre>";
        print_r($datos);
    }

    /**
     * Procesa datos UDP con payload base64
     */
    public function procesarUdpPayload(Request $request)
    {
        try {
            // Log de los datos recibidos (cualquier formato)
            Log::info('Datos UDP recibidos', [
                'method' => $request->method(),
                'headers' => $request->headers->all(),
                'data' => $request->all(),
                'raw_input' => $request->getContent()
            ]);

            // Intentar procesar como JSON directo (formato MQTT)
            $data = $request->all();
            
            // Si viene como JSON string, decodificarlo
            if (is_string($request->getContent()) && !empty($request->getContent())) {
                $jsonData = json_decode($request->getContent(), true);
                if ($jsonData) {
                    $data = $jsonData;
                }
            }

            // Si no tiene la estructura esperada, crear una estructura básica
            if (!isset($data['payload']) || !isset($data['device'])) {
                // Crear estructura básica para datos MQTT directos
                $data = [
                    'payload' => [
                        'encoding' => 'json',
                        'type' => 'JSON',
                        'value' => base64_encode(json_encode($data))
                    ],
                    'received' => time() * 1000,
                    'id' => uniqid(),
                    'source' => 'MQTT',
                    'type' => 'TELEMETRY_DATA',
                    'version' => '1.0.0',
                    'device' => [
                        'iccid' => $data['estacion_id'] ?? 'unknown',
                        'ip' => $request->ip(),
                        'imsi' => $data['estacion_id'] ?? 'unknown'
                    ]
                ];
            }

            // Procesar el payload usando el modelo
            $telemetria = TelemetriaUdp::procesarPayload($data);

            // Log del registro creado
            Log::info('Registro UDP creado exitosamente', [
                'id' => $telemetria->id,
                'estacion_id' => $telemetria->estacion_id,
                'transaccion_id' => $telemetria->transaccion_id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Datos UDP procesados correctamente',
                'data' => [
                    'id' => $telemetria->id,
                    'estacion_id' => $telemetria->estacion_id,
                    'transaccion_id' => $telemetria->transaccion_id,
                    'created_at' => $telemetria->created_at
                ]
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error procesando payload UDP', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error procesando datos UDP: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene los datos UDP por estación
     */
    public function obtenerDatosUdp($estacion_id)
    {
        try {
            $datos = TelemetriaUdp::where('estacion_id', $estacion_id)
                ->orderBy('created_at', 'desc')
                ->limit(100)
                ->get();

            return response()->json([
                'success' => true,
                'data' => $datos,
                'total' => $datos->count()
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error obteniendo datos UDP', [
                'error' => $e->getMessage(),
                'estacion_id' => $estacion_id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error obteniendo datos UDP'
            ], 500);
        }
    }
}
