-- Cancha Abierta: instalación limpia para phpMyAdmin / XAMPP.
-- No importar sobre la base instalada. No contiene contraseñas ni integrantes.
-- InnoDB; MariaDB 10.4 / MySQL 8.0.16+.
CREATE DATABASE IF NOT EXISTS cancha_abierta CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE cancha_abierta;
SET NAMES utf8mb4;
SET time_zone = '-05:00';

-- MySQL 8.0.16+ / InnoDB. Importar en una base vacía seleccionada previamente.
CREATE TABLE administradores (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 nombre VARCHAR(100) NOT NULL,
 correo VARCHAR(190) NOT NULL UNIQUE,
 password_hash VARCHAR(255) NOT NULL,
 rol ENUM('administrador','asistente','jugador') NOT NULL DEFAULT 'administrador',
 version_sesion INT NOT NULL DEFAULT 1,
 debe_cambiar_password TINYINT NOT NULL DEFAULT 0,
 activo TINYINT NOT NULL DEFAULT 1 CHECK (activo IN (0,1)),
 intentos_fallidos INT UNSIGNED NOT NULL DEFAULT 0,
 bloqueado_hasta DATETIME NULL,
 creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE contenidos_institucionales (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 titulo VARCHAR(140) NOT NULL,
 contenido TEXT NOT NULL,
 estado ENUM('borrador','publicado') NOT NULL DEFAULT 'borrador',
 creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX idx_institucion_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE categorias (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 nombre VARCHAR(100) NOT NULL UNIQUE,
 descripcion TEXT NOT NULL,
 activo TINYINT NOT NULL DEFAULT 1 CHECK (activo IN (0,1)),
 creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX idx_categoria_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE horarios (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 categoria_id BIGINT UNSIGNED NOT NULL,
 dia TINYINT NOT NULL CHECK (dia BETWEEN 1 AND 7),
 hora_inicio TIME NOT NULL,
 hora_fin TIME NOT NULL,
 lugar VARCHAR(180) NOT NULL,
 vigente TINYINT NOT NULL DEFAULT 1 CHECK (vigente IN (0,1)),
 creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_horario_categoria FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE RESTRICT,
 CONSTRAINT chk_horas CHECK (hora_fin > hora_inicio),
 UNIQUE KEY uq_horario (categoria_id, dia, hora_inicio, lugar),
 INDEX idx_horario_vigente (categoria_id, vigente, dia)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE publicaciones (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 titulo VARCHAR(160) NOT NULL,
 contenido TEXT NOT NULL,
 tipo ENUM('noticia','actividad') NOT NULL,
 fecha DATE NOT NULL,
 estado ENUM('borrador','publicado') NOT NULL DEFAULT 'borrador',
 administrador_id BIGINT UNSIGNED NOT NULL,
 creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 CONSTRAINT fk_publicacion_admin FOREIGN KEY (administrador_id) REFERENCES administradores(id) ON DELETE RESTRICT,
 INDEX idx_publicacion_estado_fecha (estado, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE solicitudes (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 nombre_adulto VARCHAR(100) NOT NULL,
 contacto VARCHAR(150) NOT NULL,
 tipo ENUM('informacion','preinscripcion') NOT NULL,
 mensaje TEXT NOT NULL,
 consentimiento TINYINT NOT NULL CHECK (consentimiento = 1),
 consentimiento_version VARCHAR(20) NOT NULL DEFAULT 'contacto-v2',
 token_envio CHAR(64) NOT NULL UNIQUE,
 estado ENUM('pendiente','revisada','cerrada') NOT NULL DEFAULT 'pendiente',
 creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 actualizado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 INDEX idx_solicitud_estado_fecha (estado, creado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Integrantes, autorizaciones, salud y auditoría.
CREATE TABLE IF NOT EXISTS academia_config (
 id TINYINT PRIMARY KEY,
 nombre VARCHAR(120) NOT NULL DEFAULT 'Cancha Abierta',
 ciudad VARCHAR(120) NOT NULL DEFAULT '',
 correo VARCHAR(190) NOT NULL DEFAULT '',
 telefono VARCHAR(40) NOT NULL DEFAULT '',
 responsable VARCHAR(190) NOT NULL DEFAULT '',
 politica TEXT NOT NULL,
 inscripciones_abiertas TINYINT NOT NULL DEFAULT 0,
 actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT IGNORE INTO academia_config (id,politica) VALUES (1,'');
CREATE TABLE IF NOT EXISTS jugadores (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 usuario_id BIGINT UNSIGNED NOT NULL UNIQUE,
 fecha_nacimiento DATE NOT NULL,
 telefono VARCHAR(40) NOT NULL,
 direccion VARCHAR(240) NOT NULL DEFAULT '',
 tutor_nombre VARCHAR(120) NOT NULL DEFAULT '',
 tutor_parentesco VARCHAR(60) NOT NULL DEFAULT '',
 tutor_contacto VARCHAR(150) NOT NULL DEFAULT '',
 emergencia_nombre VARCHAR(120) NOT NULL DEFAULT '',
 emergencia_telefono VARCHAR(40) NOT NULL DEFAULT '',
 categoria_id BIGINT UNSIGNED NULL,
 posicion VARCHAR(40) NOT NULL DEFAULT '',
 pie VARCHAR(20) NOT NULL DEFAULT '',
 dorsal TINYINT UNSIGNED NULL CHECK (dorsal BETWEEN 1 AND 99),
 experiencia TEXT NULL,
 creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY (usuario_id) REFERENCES administradores(id) ON DELETE RESTRICT,
 FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS inscripciones (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 jugador_id BIGINT UNSIGNED NOT NULL UNIQUE,
 estado ENUM('pendiente','en_revision','activa','rechazada','retirada') NOT NULL DEFAULT 'pendiente',
 observaciones VARCHAR(1500) NOT NULL DEFAULT '',
 verificacion VARCHAR(240) NOT NULL DEFAULT '',
 revisado_por BIGINT UNSIGNED NULL,
 creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY (jugador_id) REFERENCES jugadores(id) ON DELETE RESTRICT,
 FOREIGN KEY (revisado_por) REFERENCES administradores(id) ON DELETE RESTRICT,
 INDEX idx_inscripcion_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS evaluaciones (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 jugador_id BIGINT UNSIGNED NOT NULL,
 autor_id BIGINT UNSIGNED NOT NULL,
 fecha DATE NOT NULL,
 altura_cm DECIMAL(5,2) NULL CHECK (altura_cm BETWEEN 50 AND 250),
 peso_kg DECIMAL(5,2) NULL CHECK (peso_kg BETWEEN 10 AND 250),
 velocidad_30m DECIMAL(5,2) NULL CHECK (velocidad_30m BETWEEN 1 AND 60),
 resistencia_min DECIMAL(5,2) NULL CHECK (resistencia_min BETWEEN 0 AND 300),
 control TINYINT NULL CHECK (control BETWEEN 1 AND 5),
 pase TINYINT NULL CHECK (pase BETWEEN 1 AND 5),
 regate TINYINT NULL CHECK (regate BETWEEN 1 AND 5),
 remate TINYINT NULL CHECK (remate BETWEEN 1 AND 5),
 trabajo_equipo TINYINT NULL CHECK (trabajo_equipo BETWEEN 1 AND 5),
 observaciones VARCHAR(2000) NOT NULL DEFAULT '',
 token CHAR(64) NOT NULL UNIQUE,
 creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (jugador_id) REFERENCES jugadores(id) ON DELETE RESTRICT,
 FOREIGN KEY (autor_id) REFERENCES administradores(id) ON DELETE RESTRICT,
 INDEX idx_evaluacion_jugador_fecha (jugador_id,fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS autorizaciones (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 jugador_id BIGINT UNSIGNED NOT NULL,
 tipo ENUM('datos','salud') NOT NULL,
 version VARCHAR(30) NOT NULL,
 otorgante VARCHAR(120) NOT NULL,
 calidad VARCHAR(30) NOT NULL,
 referencia VARCHAR(240) NOT NULL DEFAULT '',
 politica_snapshot TEXT NOT NULL,
 registrado_por BIGINT UNSIGNED NOT NULL,
 creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 revocado_en DATETIME NULL,
 FOREIGN KEY (jugador_id) REFERENCES jugadores(id) ON DELETE RESTRICT,
 FOREIGN KEY (registrado_por) REFERENCES administradores(id) ON DELETE RESTRICT,
 INDEX idx_autorizacion (jugador_id,tipo,revocado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS fichas_salud (
 jugador_id BIGINT UNSIGNED PRIMARY KEY,
 contenido_cifrado MEDIUMTEXT NOT NULL,
 actualizado_por BIGINT UNSIGNED NOT NULL,
 actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 FOREIGN KEY (jugador_id) REFERENCES jugadores(id) ON DELETE RESTRICT,
 FOREIGN KEY (actualizado_por) REFERENCES administradores(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS auditoria (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 actor_id BIGINT UNSIGNED NOT NULL,
 jugador_id BIGINT UNSIGNED NULL,
 accion VARCHAR(80) NOT NULL,
 creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 FOREIGN KEY (actor_id) REFERENCES administradores(id) ON DELETE RESTRICT,
 FOREIGN KEY (jugador_id) REFERENCES jugadores(id) ON DELETE RESTRICT,
 INDEX idx_auditoria_fecha (creado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Contenido inicial sin categorías, horarios, noticias ni personas inventadas.
INSERT INTO contenidos_institucionales (titulo,contenido,estado) VALUES
('Información de la academia','Consulta la información del equipo, los horarios publicados y las novedades. Desde tu cuenta puedes revisar tu inscripción y el seguimiento deportivo.','publicado');

CREATE TABLE IF NOT EXISTS migraciones (version VARCHAR(80) PRIMARY KEY, aplicada_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB;
INSERT INTO migraciones (version) VALUES ('002_members'),('003_retire_seed');
