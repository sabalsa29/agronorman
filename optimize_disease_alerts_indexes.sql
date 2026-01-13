-- Índices para optimizar el comando ProcessDiseaseAlertsCommand
-- Ejecutar estos comandos en la base de datos para mejorar significativamente el rendimiento

-- Índice compuesto para enfermedad_horas_condiciones (optimiza las consultas de búsqueda)
CREATE INDEX idx_enfermedad_horas_condiciones_lookup 
ON enfermedad_horas_condiciones(tipo_cultivo_id, enfermedad_id, estacion_id);

-- Índice para estacion_dato (optimiza las consultas por estación y fecha)
CREATE INDEX idx_estacion_dato_estacion_fecha 
ON estacion_dato(estacion_id, created_at);

-- Índice para enfermedad_horas_acumuladas_condiciones (optimiza las inserciones)
CREATE INDEX idx_enfermedad_horas_acumuladas_estacion_fecha 
ON enfermedad_horas_acumuladas_condiciones(estacion_id, fecha);

-- Índice para tipo_cultivos_enfermedades (optimiza la consulta de enfermedades)
CREATE INDEX idx_tipo_cultivos_enfermedades_enfermedad 
ON tipo_cultivos_enfermedades(enfermedad_id);

-- Índice para enfermedades (optimiza la consulta de enfermedades)
CREATE INDEX idx_enfermedades_id 
ON enfermedades(id);

-- Verificar que los índices se crearon correctamente
SHOW INDEX FROM enfermedad_horas_condiciones;
SHOW INDEX FROM estacion_dato;
SHOW INDEX FROM enfermedad_horas_acumuladas_condiciones;
SHOW INDEX FROM tipo_cultivos_enfermedades;
SHOW INDEX FROM enfermedades; 