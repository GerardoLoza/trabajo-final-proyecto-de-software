DROP DATABASE healthtrackerV1;
CREATE DATABASE IF NOT EXISTS healthtrackerV1;
USE healthtrackerV1;

-- 1. Tablas de catálogo
CREATE TABLE roles (
    nombre VARCHAR(100) NOT NULL PRIMARY KEY
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE diagnosticos (
    nombre VARCHAR(255) NOT NULL PRIMARY KEY,
    descripcion TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE medicamento (
    nombre VARCHAR(255) NOT NULL PRIMARY KEY
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE tipos_tarea (
    id_tipo_tarea INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- 2. Tablas con dependencias
CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    password VARCHAR(255) NOT NULL,
    nombre_rol VARCHAR(100),
    descripcion_perfil TEXT,
    FOREIGN KEY (nombre_rol) REFERENCES roles(nombre)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE planes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255) NOT NULL,
    descripcion TEXT,
    id_profesional INT NOT NULL,
    id_paciente INT NOT NULL,
    nombre_diagnostico VARCHAR(255),
    fecha_inicio DATE,
    fecha_fin DATE,
    FOREIGN KEY (id_profesional) REFERENCES usuarios(id_usuario),
    FOREIGN KEY (id_paciente) REFERENCES usuarios(id_usuario),
    FOREIGN KEY (nombre_diagnostico) REFERENCES diagnosticos(nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE tareas (
    id_tarea INT AUTO_INCREMENT PRIMARY KEY,
    id_plan INT NOT NULL,
    id_tipo_tarea INT NOT NULL,
    num_tarea INT,
    descripcion TEXT NOT NULL,
    fecha_programada DATETIME,
    fecha_fin_programada DATETIME,
    estado VARCHAR(50) DEFAULT 'Pendiente',
    comentarios_paciente TEXT,
    fecha_realizacion DATETIME,
    FOREIGN KEY (id_plan) REFERENCES planes(id) ON DELETE CASCADE,
    FOREIGN KEY (id_tipo_tarea) REFERENCES tipos_tarea(id_tipo_tarea)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

COMMIT;


