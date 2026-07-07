-- sql/plan_invitaciones.sql
CREATE TABLE IF NOT EXISTS `san_plan_invitaciones` (
  `id_invitacion` INT(11) NOT NULL AUTO_INCREMENT,
  `id_socio_titular` INT(11) NOT NULL,
  `token_unico` VARCHAR(64) NOT NULL,
  `status` ENUM('pendiente', 'aceptado', 'expirado', 'cancelado') NOT NULL DEFAULT 'pendiente',
  `fecha_creacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_expiracion` DATETIME NOT NULL,
  PRIMARY KEY (`id_invitacion`),
  UNIQUE KEY `token_unico` (`token_unico`),
  KEY `id_socio_titular` (`id_socio_titular`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE san_socios 
ADD COLUMN soc_id_titular_grupo INT DEFAULT 0 
AFTER soc_id_referido_por;
