<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// ===================================================================
// RUTAS PÚBLICAS
// (Home público y Autenticación)
// ===================================================================
$routes->get('/', 'DashboardController::index'); // redirección al login sin sesión o al dashboard segun sesion

$routes->get('login', 'AuthController::login');
$routes->post('login', 'AuthController::attemptLogin');
$routes->get('register', 'AuthController::register');
$routes->post('register', 'AuthController::attemptRegister');
$routes->get('logout', 'AuthController::logout');

// ===================================================================
// RUTAS COMUNES (POST-LOGIN)
// ===================================================================
// (Requieren estar logueado, sin importar el rol)
$routes->group('', ['filter' => 'auth'], static function ($routes) {
    // Redirige a /admin, /profesional o /paciente según el rol
    $routes->get('dashboard', 'DashboardController::index');
});

// ===================================================================
// RUTAS DE ADMINISTRADOR
// ===================================================================
// (Requieren estar logueado como 'Administrador')
$routes->group('admin', ['filter' => 'auth:Administrador'], static function ($routes) {
    
    $routes->get('/', 'AdminController::index'); // Dashboard Admin
    $routes->get('gestion', 'AdminController::gestion');

    // --- ABMC de Usuarios (RUTAS EXPLÍCITAS) ---
    // Aquí está la magia. Usamos el filtro del grupo
    // y apuntamos a UsuarioController (el experto).
    
    // (GET) admin/usuarios  -> Muestra la lista
    $routes->get('usuarios', 'UsuarioController::index');
    
    // (GET) admin/usuarios/new -> Muestra formulario de creación
    $routes->get('usuarios/new', 'UsuarioController::create');
    
    // (POST) admin/usuarios -> Guarda el nuevo usuario
    $routes->post('usuarios', 'UsuarioController::store');
    
    // (GET) admin/usuarios/(:num) -> Muestra un usuario (si lo necesitas)
    $routes->get('usuarios/(:num)', 'UsuarioController::show/$1');
    
    // (GET) admin/usuarios/(:num)/edit -> Muestra formulario de edición
    $routes->get('usuarios/(:num)/edit', 'UsuarioController::edit/$1');
    
    // (POST) admin/usuarios/(:num) -> Actualiza el usuario
    $routes->post('usuarios/(:num)', 'UsuarioController::update/$1'); 
    // (Nota: para PUT/DELETE real necesitarías <input type="hidden" name="_method" value="PUT"> en el form)

    // (GET) admin/usuarios/(:num)/delete -> Ruta para borrar (si usas GET)
    // O (POST) admin/usuarios/(:num)/delete
    $routes->post('usuarios/(:num)/delete', 'UsuarioController::destroy/$1');


    // ABMC de Diagnósticos (sigue apuntando a AdminController por ahora)
    $routes->resource('diagnosticos', [
        'controller' => 'AdminController',
        'placeholder' => '(:num)',
        'as' => 'admin.diagnosticos'
    ]);
    
    // ... (el resto de tus rutas de admin) ...
});

// ===================================================================
// RUTAS DE PROFESIONAL
// ===================================================================
// (Requieren estar logueado como 'Profesional')
$routes->group('profesional', ['filter' => 'auth:Profesional'], static function ($routes) {
    
    $routes->get('/', 'ProfesionalController::index'); // Dashboard Profesional

    // --- RUTA PERSONALIZADA PARA USUARIOS ---
    // (GET) profesional/mis-pacientes -> Llama al método personalizado
    $routes->get('mis-pacientes', 'UsuarioController::listarPacientes');

    // ... (el resto de tus rutas de profesional) ...
    $routes->get('gestion-planes', 'PlanController::gestionPlanes');
    $routes->post('tareas/validar/(:num)', 'ProfesionalController::validarCumplimiento/$1', ['as' => 'profesional.validar']);

    // Compatibility aliases (temporal): map old URIs to new controllers/methods
    // These keep existing links and client calls working while you migrate logic.
    $routes->get('planes', 'PlanController::index'); // /profesional/planes -> listado
    $routes->post('planes/crear', 'PlanController::store', ['as' => 'profesional.planes.crear']);
    $routes->post('planes/eliminar/(:num)', 'PlanController::delete/$1', ['as' => 'profesional.planes.eliminar']);
    // Tareas por plan (alias) - implementar porPlan en TareaController cuando lo desees
    $routes->get('tareas/por_plan/(:num)', 'TareaController::porPlan/$1', ['as' => 'profesional.tareas.por_plan']);
});

// ===================================================================
// RUTAS DE PACIENTE
// ===================================================================
// (Tus rutas de paciente quedan igual)
$routes->group('paciente', ['filter' => 'auth:Paciente'], static function ($routes) {
    $routes->get('/', 'PacienteController::index');
    // ...
});