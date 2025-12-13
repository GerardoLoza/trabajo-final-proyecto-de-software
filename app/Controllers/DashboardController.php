<?php

namespace App\Controllers;

use App\Models\UsuarioModel;
use App\Models\PlanModel;
use App\Models\MedicamentoModel;
use App\Models\DiagnosticoModel;
use App\Models\TareaModel;
use App\Models\TipoTareaModel;
use App\Models\RolModel;
use App\Models\TareaEstandarModel;
use App\Models\PlanEstandarModel;

class DashboardController extends BaseController
{
    /**
     * Despachador Principal (Ruta: /dashboard)
     * Lee el rol de la sesión y redirige a la URL base de ese rol.
     */
    public function index()
    {
        $rol = $this->session->get('nombre_rol');

        switch ($rol) {
            case 'Administrador':
                // Redirige a /admin, que llama a $this->adminDashboard() según routes.php
                return redirect()->to(base_url('admin'));

            case 'Profesional':
                // Redirige a /profesional, que llama a $this->profesionalDashboard()
                return redirect()->to(base_url('profesional'));

            case 'Paciente':
                // Redirige a /paciente, que llama a $this->pacienteDashboard()
                return redirect()->to(base_url('paciente'));

            default:
                return redirect()->to(base_url('logout'));
        }
    }

    /**
     * Dashboard para el ADMINISTRADOR
     * Ruta: /admin
     */
    public function adminDashboard()
    {
        // 1. Instanciar todos los modelos necesarios
        $usuarioModel      = new UsuarioModel();
        $planModel         = new PlanModel();
        $medicamentoModel  = new MedicamentoModel();
        $diagnosticoModel  = new DiagnosticoModel();
        $tipoTareaModel    = new TipoTareaModel();
        $rolModel          = new RolModel(); // Asegúrate de tener este modelo o usar el namespace completo

        // 2. Recopilar TODOS los datos para la vista
        $data = [
            // --- A. Datos para Stats (Gráficos y Tarjetas) ---
            'totalUsuarios'     => $usuarioModel->countAllResults(),
            'totalMedicamentos' => $medicamentoModel->countAllResults(),
            'totalProfesionales'=> $usuarioModel->where('nombre_rol', 'Profesional')->countAllResults(),
            'totalPlanes'       => $planModel->countAllResults(),
            'usuariosPorRol'    => $usuarioModel->select('nombre_rol, COUNT(*) as cantidad')
                                                ->groupBy('nombre_rol')
                                                ->findAll(),
            
            // Datos Dummy para gráficas (puedes conectarlos a logica real luego)
            'actividad'         => [], 
            
            // --- B. Datos para Tablas CRUD (LO QUE FALTABA) ---
            'usuarios'          => $usuarioModel->findAll(), // Para la tabla de gestión de usuarios
            'listaMedicamentos' => $medicamentoModel->findAll(),
            'listaDiagnosticos' => $diagnosticoModel->findAll(),
            'listaTiposTarea'   => $tipoTareaModel->findAll(),
            'listaRoles'        => $rolModel->findAll(),
        ];

        // 3. Cargar vista
        return view('dashboard_admin', $data);
    }

    /**
     * Dashboard para el PROFESIONAL
     * Ruta: /profesional
     */
    public function profesionalDashboard()
    {
        // 1. Instanciar modelos
        $usuarioModel = new UsuarioModel();
        $planModel = new PlanModel();
        $diagnosticoModel = new DiagnosticoModel();
        $tareaModel = new TareaModel();
        $tipoTareaModel = new TipoTareaModel(); // Agregado
        $medicamentoModel = new MedicamentoModel();
        $planEstandarModel = new PlanEstandarModel();
        $tareaEstandarModel = new TareaEstandarModel();
        
        $idProfesional = $this->session->get('id_usuario');

        // 2. Obtener datos
        $misPacientes = $usuarioModel->getPacientesPorProfesional($idProfesional);
        $misPlanes    = $planModel->getPlanesPorProfesional($idProfesional);
        $planesActivos = $planModel->getPlanesActivosPorProfesional($idProfesional);

        // Datos para los formularios (Selects)
        $todosLosPacientes = $usuarioModel->getPacientes(); // Para poder asignar plan a cualquier paciente
        $listaDiagnosticos = $diagnosticoModel->findAll();
        $listaTiposTarea   = $tipoTareaModel->findAll();

        // Tareas (Opcional: traer todas o filtrar)
        $listaTareas = $tareaModel->findAll();

        $data = [
            // Stats
            'totalPacientes'    => count($misPacientes),
            'planesActivos'     => count($planesActivos),

            // Listas para tablas y selects
            'listaPlanes'       => $misPlanes,
            'listaPacientes'    => $misPacientes,      
            'todosLosPacientes' => $todosLosPacientes, 
            'listaDiagnosticos' => $listaDiagnosticos,
            'listaMedicamentos' => $medicamentoModel->findAll(), 
            'listaTiposTarea'   => $listaTiposTarea,
            'listaTareas'       => $listaTareas,
            'listaPlanesEstandar' => $planEstandarModel->findAll()
        ];

        return view('dashboard_profesional', $data);
    }

    /**
     * Dashboard para el PACIENTE
     * Ruta: /paciente
    **/
    public function pacienteDashboard()
    {
        $planModel = new PlanModel();
        $tareaModel = new TareaModel();
        
        $idPaciente = $this->session->get('id_usuario');

        // 1. Obtener mis planes
        $misPlanes = $planModel->getPlanesPorPaciente($idPaciente);
        
        // 2. Buscar tareas pendientes (Solo de mis planes)
        $tareasPendientes = [];
        $totalCompletadas = 0;

        // Extraemos los IDs de los planes para filtrar las tareas
        $planIds = [];
        foreach ($misPlanes as $p) {
            // Manejo robusto de objetos vs arrays
            $planIds[] = is_object($p) ? $p->id : $p['id'];
        }

        if (!empty($planIds)) {
            // Buscamos tareas asociadas a esos planes que estén pendientes
            $tareasPendientes = $tareaModel->whereIn('id_plan', $planIds)
                                        ->where('estado', 'Pendiente')
                                        ->orderBy('fecha_programada', 'ASC')
                                        ->findAll();

            // Contamos las completadas para las estadísticas
            $totalCompletadas = $tareaModel->whereIn('id_plan', $planIds)
                                        ->where('estado', 'Completada')
                                        ->countAllResults();
        }

        $data = [
            'totalPlanes'      => count($misPlanes),
            'totalPendientes'  => count($tareasPendientes),
            'totalCompletadas' => $totalCompletadas,
            'listaPlanes'      => $misPlanes,
            'listaTareas'      => $tareasPendientes // Se mostrarán en la tabla de tareas
        ];

    return view('dashboard_paciente', $data);
    } 

    
    
}
