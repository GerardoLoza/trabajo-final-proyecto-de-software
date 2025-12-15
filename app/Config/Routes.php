<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// ===================================================================
// 1. RUTAS PÚBLICAS (Auth y Landing)
// ===================================================================
$routes->get('/', 'Home::index'); // Landing pública

// HU-01 y HU-02: Registro y Autenticación
// (Entidad: Usuario - Manejado por AuthController para lógica de acceso)
$routes->get('login', 'AuthController::login');
$routes->post('login', 'AuthController::attemptLogin');
$routes->get('register', 'AuthController::register');
$routes->post('register', 'AuthController::attemptRegister');
$routes->get('logout', 'AuthController::logout');

// HU-04: Restablecer contraseña
$routes->get('forgot-password', 'AuthController::forgotPassword');
$routes->post('forgot-password', 'AuthController::attemptForgotPassword');

// Rutas para restablecer contraseña con token
$routes->get('reset-password', 'AuthController::resetPassword');
$routes->post('reset-password', 'AuthController::attemptResetPassword');


// ===================================================================
// 2. RUTAS COMUNES (Post-Login)
// ===================================================================
// Requieren que o usuario esté logueado, sin importar su rol.
$routes->group('', ['filter' => 'auth'], static function ($routes) {
    
    // Redirección inteligente al dashboard correspondiente según rol
    $routes->get('dashboard', 'DashboardController::index');
    
    // HU-02: Perfil de usuario (Ver mi propio perfil)
    // Entidad: Usuario
    $routes->get('perfil', 'UsuarioController::miPerfil');
    $routes->post('perfil', 'UsuarioController::actualizarPerfil');
});


// ===================================================================
// 3. RUTAS DE ADMINISTRADOR
// ===================================================================
// Filtro: auth:Administrador
$routes->group('admin', ['filter' => 'auth:Administrador'], static function ($routes) {
    
    // Dashboard Admin (HU-12 - Vista global)
    // Entidad: Metricas (o Dashboard)
    $routes->get('/', 'DashboardController::adminDashboard');

    // HU-03: ABMC Usuarios
    // Entidad: Usuario
    // Crea rutas: index, show, new, create, edit, update, delete
    $routes->resource('usuarios', [
        'controller' => 'UsuarioController',
        'placeholder' => '(:num)',
        'except' => 'show' // Si no necesitas vista detalle individual
    ]);

    // HU-06: Gestión de Plantillas de Planes (Planes estandarizados)
    // Entidad: Plan (o PlanPlantilla si creas ese modelo específico)
    // Usamos PlanController pero quizás filtrando por tipo 'plantilla' internamente
    $routes->resource('planes-plantillas', [
        'controller' => 'PlanController',
        'placeholder' => '(:num)',
    ]);

    // ABMC de Catálogos (Tablas: diagnosticos, medicamento, tipos_tarea, roles)
    // Entidad: Diagnostico
    $routes->resource('diagnosticos', ['controller' => 'DiagnosticoController']);
    // Entidad: Medicamento
    $routes->resource('medicamentos', ['controller' => 'MedicamentoController']);
    // Entidad: TipoTarea
    $routes->resource('tipos-tarea', ['controller' => 'TipoTareaController']);
    // Entidad: Rol
    $routes->resource('roles', ['controller' => 'RolController']);
    $routes->get('planes-global', 'PlanController::globalView');
    $routes->get('planes/(:num)/tareas', 'TareaController::porPlan/$1');
});


// ===================================================================
// 4. RUTAS DE PROFESIONAL
// ===================================================================
// Filtro: auth:Profesional
$routes->group('profesional', ['filter' => 'auth:Profesional'], static function ($routes) {

    // Dashboard Profesional (HU-10 - Métricas)
    $routes->get('/', 'DashboardController::profesionalDashboard');
    // Endpoint AJAX para obtener KPIs / charts filtrados por paciente
    $routes->get('kpis', 'DashboardController::kpis');

    // Gestión de Pacientes (Listado solo de mis pacientes)
    // Entidad: Usuario (El experto es UsuarioController)
    $routes->get('mis-pacientes', 'UsuarioController::listarPacientes');

    // HU-05: Crear y Gestionar Planes de Cuidado
    // Entidad: Plan
    $routes->resource('planes', [
        'controller' => 'PlanController',
        'placeholder' => '(:num)',
        // Limitamos a las acciones que el profesional puede hacer
        // index (listar mis planes), create, store, edit, update, delete (baja lógica)
    ]);
    $routes->resource('medicamentos', ['controller' => 'MedicamentoController']);
    $routes->resource('tipos-tarea', ['controller' => 'TipoTareaController']);
    $routes->resource('diagnosticos', ['controller' => 'DiagnosticoController']);

    // HU-05a: ABMC de Tareas dentro de un plan
    // Entidad: Tarea
    $routes->resource('tareas', [
        'controller' => 'TareaController',
        'placeholder' => '(:num)',
    ]);
    
    // Endpoint específico para obtener tareas de un plan (útil para AJAX/Modales)
    $routes->get('planes/(:num)/tareas', 'TareaController::porPlan/$1');

    // HU-09: Validar Cumplimiento (Profesional cambia estado del plan)
    // Entidad: Plan (Acción específica de negocio)
    $routes->post('planes/(:num)/estado', 'PlanController::cambiarEstado/$1');
    
    // HU-06a: Gestión de Plantillas Estandarizadas
    $routes->resource('planes-estandar', [
        'controller' => 'PlanEstandarController',
        'only' => ['create', 'delete'] 
    ]);

    // 2. Tareas Estandar (Crear y Borrar)
    $routes->resource('tareas-estandar', [
        'controller' => 'TareaEstandarController',
        'only' => ['create', 'delete']
    ]);

    $routes->get('planes-estandar/(:num)/tareas', 'TareaEstandarController::indexPorPlan/$1');
    $routes->post('planes-estandar/asignar', 'PlanEstandarController::assign');
    $routes->get('documentos/paciente/(:num)', 'DocumentoController::listByPatient/$1');
    $routes->post('documentos/upload', 'DocumentoController::uploadProfesional');
    $routes->delete('documentos/(:num)', 'DocumentoController::delete/$1');
    $routes->get('documentos/download/(:num)', 'DocumentoController::downloadProf/$1');
    $routes->post('planes/(:num)/finalizar', 'PlanController::finalizarPlan/$1');
});
// ===================================================================
// 5. RUTAS DE PACIENTE
// ===================================================================
// Filtro: auth:Paciente
$routes->group('paciente', ['filter' => 'auth:Paciente'], static function ($routes) {

    // Dashboard Paciente (HU-07 - Ver mis planes y progreso)
    $routes->get('/', 'DashboardController::pacienteDashboard');

    // HU-07: Consultar mis planes (Read-only para el paciente)
    // Entidad: Plan
    $routes->get('mis-planes', 'PlanController::index'); 
    $routes->get('mis-planes/(:num)', 'PlanController::show/$1');

    // HU-08: Registrar Progreso (Completar tarea)
    // Entidad: Tarea
    $routes->post('tareas/(:num)/completar', 'TareaController::registrarProgreso/$1');
    // Endpoint específico para obtener tareas de un plan
    $routes->get('planes/(:num)/tareas', 'TareaController::porPlan/$1');

    // Endpoint AJAX: Obtener adherencia de un plan específico
    $routes->get('adherencia-plan/(:num)', 'DashboardController::getAdherenciaPlan/$1');

    // HU-11: Documentación Médica
    // Entidad: Documento (Si existe tabla documentos, sino va en UsuarioController o PlanController)
    // Asumiendo un DocumentoController:
    $routes->resource('documentos', [
        'controller' => 'DocumentoController',
        'placeholder' => '(:num)',
        'only' => ['index', 'create', 'store', 'show', 'delete'] // Paciente sube y ve
    ]);
});