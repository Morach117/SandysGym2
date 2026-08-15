-- ==========================================================
-- SandysGym 2 - Performance Index Optimization
-- Script generado automáticamente por el agente Code Optimizer
-- ==========================================================

-- Índices recomendados para la tabla de Socios (frecuentemente consultada por correo e ID)
ALTER TABLE `san_socios` ADD INDEX `idx_soc_correo` (`soc_correo`);
ALTER TABLE `san_socios` ADD INDEX `idx_soc_empresa` (`soc_id_empresa`);

-- Índices recomendados para la tabla de Pagos (búsqueda por ID de socio y ordenamiento por fecha)
ALTER TABLE `san_pagos` ADD INDEX `idx_pag_id_socio` (`pag_id_socio`);
ALTER TABLE `san_pagos` ADD INDEX `idx_pag_fecha_pago` (`pag_fecha_pago`);

-- Índice para validar referencias externas y buscar membresías pendientes en Mercado Pago
ALTER TABLE `san_mp_pref` ADD INDEX `idx_external_reference` (`external_reference`);

-- Índice para acelerar búsquedas de códigos promocionales activos
ALTER TABLE `san_codigos` ADD INDEX `idx_codigo_generado` (`codigo_generado`, `status`);

-- Estos índices mejorarán la velocidad de respuesta de las consultas 
-- en el Panel de Usuario y el Procesamiento de Pagos evitando "Table Scans" completos.
