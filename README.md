
script base de datos:
CREATE DATABASE IF NOT EXISTS healthtrackerV1;
USE healthtrackerV1;

-- 1. Tablas de catálogo
CREATE TABLE roles (
    nombre VARCHAR(100) NOT NULL PRIMARY KEY
);

CREATE TABLE diagnosticos (
    nombre VARCHAR(255) NOT NULL PRIMARY KEY,
    descripcion TEXT
);

CREATE TABLE medicamento (
    nombre VARCHAR(255) NOT NULL PRIMARY KEY
);

CREATE TABLE tipos_tarea (
    id_tipo_tarea INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL UNIQUE
);

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
);

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
);

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
);









Historias de usuario:

HU: CU-01 — Registro de usuario
Descripción: Como Usuario (Administrador/Profesional/Paciente) quiero registrarme para crear mi cuenta y acceder según mi rol.
Criterios de aceptación:
Dado un formulario con campos obligatorios, cuando ingreso datos válidos entonces se crea la cuenta y quedo habilitado por rol
Debe validar campos requeridos y formatos (ej.: email)
Debe poder registrarse cualquiera de los tres perfiles previstos.
Mostrar mensajes claros de éxito/errores.
Puntos: (promedio de 1, 3, 5)
Valor: Onboarding autónomo que facilita adopción y reduce carga administrativa
Riesgo: Bajo (flujo estándar de alta con validaciones).

HU: CU-02 — Login/Logout
Descripción: Como Usuario quiero iniciar/cerrar sesión (login/logout) para acceder de forma segura a mi dashboard y permisos.
Criterios de aceptación:
Autenticación por email/clave; al fallar muestra error sin revelar detalles.
Sesión mantiene el rol y permisos; logout invalida sesión.
Validaciones de formulario activas.
Puntos: ( promedio 2, 5, 8)
Valor:  Acceso controlado y personalizado que garantiza seguridad y experiencia contextual.
Riesgo: Bajo (seguridad básica y manejo de sesión).

HU: CU-03 — ABMC Usuarios (Admin)
Descripción: Como Administrador quiero crear, editar, eliminar y consultar (ABMC) usuarios para gestionar cuentas y permisos.
Criterios de aceptación:
Alta/edición con asignación de rol (Administrador/Profesional/Paciente).
Baja lógica y búsqueda por filtros.
Validaciones de campos y mensajes de confirmación.
Puntos:  2, 3, 3
Valor: Control centralizado de usuarios que mejora gobernanza y cumplimiento institucional.
Riesgo: Medio (consistencia y gobernanza de permisos).
HU: CU-04 — Restablecer contraseña
Descripción: Como Usuario quiero restablecer mi contraseña de forma segura sin asistencia manual.
Criterios de aceptación:
Solicitud por email y envío de enlace temporal con expiración.
Cambio exitoso invalida sesiones previas.
Validaciones y feedback de estado.
Puntos: ( ?, ?, ?)
Valor: Reduce fricción y tickets de soporte, mejora seguridad con enlaces temporales
Riesgo: Medio (gestión segura de tokens y expiraciones).

HU: CU-05 — Crear Plan de Cuidado (Profesional)
Descripción: Como Profesional quiero crear un plan para un paciente, vinculado a un diagnóstico, con fechas y metas/tareas.
Criterios de aceptación:
El plan se asocia a paciente y a un “Tipo de Diagnóstico” existente; fechas inicio/fin son obligatorias.
Permite definir metas/tareas iniciales del plan.
(Restricción del curso) La lista de tipos de diagnóstico está limitada (máx. 5).
Validaciones y confirmación de creación.
Puntos: (5, 8, 8)
Valor: Permite intervenciones individualizadas y registro clínico estructurado.
Riesgo: Medio (modelado de dominio y reglas de negocio).

HU: CU-05a — ABMC de tareas/metas del plan (Profesional)
 Descripción: Como Profesional quiero crear/editar/eliminar/consultar metas/tareas del plan para detallar acciones del paciente.
 Criterios de aceptación:
Alta con nombre, frecuencia/periodicidad, notas y objetivo.
Edición y baja lógica; historial visible.
Debe validar campos requeridos.
 Puntos: (5,8,5)
 Valor: Granularidad en el seguimiento que habilita trazabilidad y métricas precisas
 Riesgo: Medio (trazabilidad de cambios en componentes del plan).

HU: CU-06 — ABMC de Planes estandarizados (Admin)
Descripción: Como Administrador quiero gestionar plantillas de planes de cuidado para uso homogéneo.
Criterios de aceptación:
Crear plantilla con metas/tareas predefinidas y tipo de diagnóstico asignable.
Editar/bajar/consultar plantillas; clonarlas a un paciente (cuando el Profesional las use).
Validaciones de formularios
Puntos: (???)
Valor: : Facilita capacitación, control de calidad y uso homogéneo entre profesionales
Riesgo: Medio (versionado/impacto de cambios en plantillas).

HU: CU-07 — Consultar planes (Profesional/Paciente)
Descripción: Como Profesional/Paciente quiero ver planes activos y pasados para conocer estado e historial.
Criterios de aceptación:
Listado filtrable (activo/finalizado) con paginación.
Detalle muestra metas, progreso y estado.
Acceso al historial de tratamientos del paciente.
Puntos: 1,3,3
Valor: Transparencia y flexibilidad en la evolución del tratamiento.
Riesgo: Bajo (consultas y vistas).

HU: CU-08 — Registrar progreso (Paciente)
Descripción: Como Paciente quiero marcar tareas como cumplidas y agregar fecha/duración/comentarios.
Criterios de aceptación:
Registro por tarea con fecha requerida; opcional duración/comentarios.
Solo sobre tareas vigentes del plan activo.
Validaciones y feedback de guardado.
Puntos: 3,1,3
Valor:  Empoderamiento del paciente y datos directos para evaluación clínica
Riesgo: Bajo (captura simple con validaciones).


HU: CU-09 — Validar cumplimiento (Profesional)
Descripción: Como Profesional quiero validar/aprobar y/o puntuar los reportes del paciente y, de ser necesario, actualizar el estado del plan.
Criterios de aceptación:
Vista de pendientes de validación con filtros.
Acción de validar/rechazar con nota y puntuación opcional.
Puede cambiar el estado del plan según criterio clínico.
Puntos: ???
Valor: Control clínico sobre la veracidad del cumplimiento y mejora de la toma de decisiones
Riesgo: Medio (reglas de estado y auditoría).

HU: CU-10 — Métricas y estadísticas
Descripción: Como Profesional/Paciente/Administrador quiero ver métricas de cumplimiento y efectividad para analizar progreso y tendencias.
Criterios de aceptación:
Panel con KPIs (p. ej., % cumplimiento, racha, tareas/semana) e históricos.
Gráficos de tendencia y comparativas por plan/diagnóstico.
Filtros por fecha/estado/rol
Puntos: ???
Valor: Inteligencia operacional que permite mejorar protocolos y medir resultados poblacionales e individuales
Riesgo: Alto (agregaciones, rendimiento y diseño de métricas).

HU: CU-11 — Documentación médica (Paciente)
Descripción: Como Paciente quiero subir y organizar estudios/recetas/informes para centralizar mi historial y compartirlo.
Criterios de aceptación:
Subir archivos (tipos permitidos), ver/descargar, categorizar por tipo.
Asociar documentos a planes/diagnósticos opcionalmente.
Validaciones de tamaño/formatos y mensajes claros.
Puntos: ???
Valor: Digitalización del expediente que agiliza consultas y reduce pérdida de información
Riesgo: Medio (manejo de archivos y permisos).

HU: CU-12 — Vista global de planes (Admin)
Descripción: Como Administrador quiero ver el total de planes por estado (activos, finalizados, pendientes) para monitorear uso y carga.
Criterios de aceptación:
Tablero con conteos por estado y tendencia semanal/mensual.
Filtros por entidad/médico/diagnóstico.
Exportar tabla/resumen.
Puntos: 3
Valor: 8,5,3
Riesgo: Bajo (consultas agregadas simples).


HU: ID
Descripción:
Criterios de aceptación:
Puntos:
Valor:
Riesgo:
