<?php

namespace App\Controllers;

use App\Models\UsuarioModel;
use App\Models\PlanModel;
use App\Models\MedicamentoModel;
use App\Models\DiagnosticoModel;
use App\Models\TareaModel;
use App\Models\TipoTareaModel;
use App\Models\RolModel;

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
        
        $idProfesional = $this->session->get('id_usuario');

        // 2. Obtener datos
        $misPacientes = $usuarioModel->getPacientesPorProfesional($idProfesional);
        $misPlanes    = $planModel->getPlanesPorProfesional($idProfesional);

        // --- NUEVO: soporte de filtro por paciente (GET ?paciente=ID) ---
        $selectedPaciente = $this->request->getGet('paciente') ? (int) $this->request->getGet('paciente') : null;

        // Helper local para calcular KPIs a partir de una lista de planes
        $calcKpis = function(array $planes) use ($tareaModel) {
            $planIds = array_map(function($p){ return is_object($p) ? $p->id : $p['id']; }, $planes);
            $planIds = array_filter($planIds);
            $k = [
                'porcentaje_completado' => 0,
                'tareas_completadas'    => 0,
                'tareas_pendientes'     => 0,
                'tareas_por_semana'     => 0,
                'racha_dias'            => 0,
                'total_tareas'          => 0
            ];

            if (empty($planIds)) return $k;

            $tasks = $tareaModel->whereIn('id_plan', $planIds)->findAll();
            $totalTasks = count($tasks);
            $completedTasks = 0;
            $completedDates = [];
            $sinceDate = date('Y-m-d', strtotime('-28 days'));
            $tasksLast28 = 0;

            foreach ($tasks as $t) {
                $estado = is_object($t) ? $t->estado : ($t['estado'] ?? null);
                $fecha_real = is_object($t) ? ($t->fecha_realizacion ?? null) : ($t['fecha_realizacion'] ?? null);
                if ($estado === 'Completada' || !empty($fecha_real)) {
                    $completedTasks++;
                    $d = $fecha_real ? substr($fecha_real,0,10) : null;
                    if ($d) $completedDates[$d] = true;
                    if ($d && $d >= $sinceDate) $tasksLast28++;
                } else {
                    if ($fecha_real && substr($fecha_real,0,10) >= $sinceDate) $tasksLast28++;
                }
            }

            $k['total_tareas'] = $totalTasks;
            $k['tareas_completadas'] = $completedTasks;
            $k['tareas_pendientes'] = max(0, $totalTasks - $completedTasks);
            $k['porcentaje_completado'] = $totalTasks ? round(($completedTasks / $totalTasks) * 100, 1) : 0;
            $k['tareas_por_semana'] = round($tasksLast28 / 4, 1);

            // Calcular racha en días consecutivos
            $streak = 0;
            $today = new \DateTimeImmutable(date('Y-m-d'));
            for ($i=0;$i<60;$i++) {
                $d = $today->sub(new \DateInterval("P{$i}D"))->format('Y-m-d');
                if (isset($completedDates[$d])) $streak++; else break;
            }
            $k['racha_dias'] = $streak;

            return $k;
        };

        // KPIs generales (todos los planes del profesional)
        $kpis_general = $calcKpis(is_array($misPlanes) ? $misPlanes : []);

        // KPIs filtrados por paciente (si aplica)
        $kpis_filtrado = null;
        if ($selectedPaciente) {
            $planesFiltrados = array_filter($misPlanes, function($p) use ($selectedPaciente) {
                $id = is_object($p) ? $p->id_paciente : ($p['id_paciente'] ?? null);
                return intval($id) === $selectedPaciente;
            });
            $kpis_filtrado = $calcKpis(array_values($planesFiltrados));
        }

        // --- NUEVO: preparar datos para gráficas ---
        // 1) Serie diaria de completadas últimos 28 días
        $dailyLabels = [];
        $dailyCounts = [];
        for ($i = 27; $i >= 0; $i--) {
            $d = (new \DateTimeImmutable('today'))->sub(new \DateInterval("P{$i}D"))->format('Y-m-d');
            $dailyLabels[] = $d;
            $dailyCounts[$d] = 0;
        }

        // 2) Agrupar completadas por tipo de tarea
        $types = $tipoTareaModel->findAll();
        $typeMap = [];
        foreach ($types as $t) {
            $id = is_object($t) ? $t->id_tipo_tarea : ($t['id_tipo_tarea'] ?? null);
            $name = is_object($t) ? $t->nombre : ($t['nombre'] ?? '');
            if ($id !== null) $typeMap[$id] = $name;
        }
        $typeCounts = array_fill_keys(array_keys($typeMap), 0);

        // 3) Conteo por semana (últimas 4 semanas)
        $completedWeekCounts = []; // key: ISO week (o-\WW) => count

        // Determinar qué planes usar para las gráficas: si hay filtro por paciente usar esos planes, sino usar todos los planes del profesional
        $plansForCharts = [];
        if ($selectedPaciente) {
            $plansForCharts = isset($planesFiltrados) ? array_values($planesFiltrados) : array_filter($misPlanes, function($p) use ($selectedPaciente) {
                $id = is_object($p) ? $p->id_paciente : ($p['id_paciente'] ?? null);
                return intval($id) === $selectedPaciente;
            });
        } else {
            $plansForCharts = is_array($misPlanes) ? $misPlanes : [];
        }

        $planIds = array_map(function($p){ return is_object($p) ? $p->id : ($p['id'] ?? null); }, $plansForCharts);
        $planIds = array_filter($planIds);

        $tasks = [];
        if (!empty($planIds)) {
            $tasks = $tareaModel->whereIn('id_plan', $planIds)->findAll();
            foreach ($tasks as $t) {
                $fecha = is_object($t) ? ($t->fecha_realizacion ?? $t->fecha_programada ?? null) : ($t['fecha_realizacion'] ?? $t['fecha_programada'] ?? null);
                if (!$fecha) continue;

                $fechaDia = substr($fecha, 0, 10);

                // daily counts
                if (isset($dailyCounts[$fechaDia])) {
                    $dailyCounts[$fechaDia]++;
                }

                // week key
                try {
                    $dt = new \DateTimeImmutable($fechaDia);
                    $weekKey = $dt->format('o-\WW');
                    if (!isset($completedWeekCounts[$weekKey])) $completedWeekCounts[$weekKey] = 0;
                    // consideramos completadas si el estado es 'Completada' o hay fecha_realizacion
                    $estado = is_object($t) ? $t->estado : ($t['estado'] ?? null);
                    $fechaRealVal = is_object($t) ? ($t->fecha_realizacion ?? null) : ($t['fecha_realizacion'] ?? null);
                    if ($estado === 'Completada' || !empty($fechaRealVal)) {
                        $completedWeekCounts[$weekKey]++;
                    }
                } catch (\Exception $e) {
                    // ignorar fechas inválidas
                }

                // tipo de tarea
                $typeId = is_object($t) ? ($t->id_tipo_tarea ?? null) : ($t['id_tipo_tarea'] ?? null);
                if ($typeId !== null && isset($typeCounts[$typeId])) {
                    $typeCounts[$typeId]++;
                }
            }
        }

        // Preparar arrays para la vista: dailyLabels / dailyData
        $dailyData = [];
        foreach ($dailyLabels as $lbl) {
            $dailyData[] = $dailyCounts[$lbl] ?? 0;
        }

        // tipos: labels y data
        $typeLabels = [];
        $typeData = [];
        foreach ($typeMap as $id => $name) {
            $typeLabels[] = $name;
            $typeData[] = $typeCounts[$id] ?? 0;
        }

        // Semanas: elegir las últimas 4 semanas (ordenadas de antigua a reciente)
        $weeklyLabels = [];
        $weeklyData = [];
        for ($i = 3; $i >= 0; $i--) {
            $dt = (new \DateTimeImmutable('today'))->sub(new \DateInterval("P" . ($i*7) . "D"));
            $weekKey = $dt->format('o-\WW');
            $label = 'Sem ' . $dt->format('W') . ' ' . $dt->format('Y');
            $weeklyLabels[] = $label;
            $weeklyData[] = $completedWeekCounts[$weekKey] ?? 0;
        }

        // --- FIX: definir $charts antes de incluirlo en $data ---
        $charts = [
            'daily'  => ['labels' => $dailyLabels, 'data' => $dailyData ?? array_values($dailyCounts)],
            'byType' => ['labels' => $typeLabels,  'data' => $typeData],
            'weekly' => ['labels' => $weeklyLabels,'data' => $weeklyData],
        ];

        // 3. Preparar data para la vista (mantener lo existente + kpis)
        $data = [
            'totalPacientes'    => count($misPacientes),
            'planesActivos'     => count($misPlanes),
            'listaPlanes'       => $misPlanes,
            'listaPacientes'    => $misPacientes,
            'todosLosPacientes' => $usuarioModel->getPacientes(),
            'listaDiagnosticos' => $diagnosticoModel->findAll(),
            'listaTiposTarea'   => $tipoTareaModel->findAll(),
            'listaMedicamentos' => $medicamentoModel->findAll(),
            'soloLectura'       => false,
 
            // KPIs: generales y filtrados (HU-10)
            'kpis_general'  => $kpis_general,
            'kpis_filtrado' => $kpis_filtrado,
            'selected_paciente' => $selectedPaciente,
 
            // Datos para gráficas (nombre coherente)
            'charts' => $charts,
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

    /**
     * Endpoint JSON: devuelve kpis y charts según ?paciente=ID (para actualización AJAX)
     * Ruta: /profesional/kpis
     */
    public function kpis()
    {
        $usuarioModel = new \App\Models\UsuarioModel();
        $planModel = new \App\Models\PlanModel();
        $tareaModel = new \App\Models\TareaModel();
        $tipoTareaModel = new \App\Models\TipoTareaModel();

        $idProfesional = $this->session->get('id_usuario');
        $selectedPaciente = $this->request->getGet('paciente') ? (int)$this->request->getGet('paciente') : null;

        $misPlanes = $planModel->getPlanesPorProfesional($idProfesional) ?: [];

        // Filtrar planes para charts / KPIs (según paciente)
        $plansForCharts = [];
        if ($selectedPaciente) {
            foreach ($misPlanes as $p) {
                $pid = is_object($p) ? $p->id_paciente : ($p['id_paciente'] ?? null);
                if (intval($pid) === $selectedPaciente) $plansForCharts[] = $p;
            }
        } else {
            $plansForCharts = $misPlanes;
        }

        $planIds = array_map(function($p){ return is_object($p) ? $p->id : ($p['id'] ?? null); }, $plansForCharts);
        $planIds = array_filter($planIds);

        // Inicializa estructuras
        $dailyLabels = []; $dailyCounts = [];
        for ($i = 27; $i >= 0; $i--) {
            $d = (new \DateTimeImmutable('today'))->sub(new \DateInterval("P{$i}D"))->format('Y-m-d');
            $dailyLabels[] = $d;
            $dailyCounts[$d] = 0;
        }

        $types = $tipoTareaModel->findAll();
        $typeMap = []; foreach ($types as $t) { $id = is_object($t)?$t->id_tipo_tarea:($t['id_tipo_tarea']??null); $name = is_object($t)?$t->nombre:($t['nombre']??''); if ($id!==null) $typeMap[$id]=$name; }
        $typeCounts = array_fill_keys(array_keys($typeMap), 0);

        $completedWeekCounts = [];
        // preparar week keys para 4 semanas (en build later)

        // Recolectar tareas si hay planes
        $tasks = [];
        if (!empty($planIds)) {
            $tasks = $tareaModel->whereIn('id_plan', $planIds)->findAll();
        }

        // Recorrer tareas para poblar dailyCounts, typeCounts y weekCounts
        foreach ($tasks as $t) {
            $fecha = is_object($t) ? ($t->fecha_realizacion ?? $t->fecha_programada ?? null) : ($t['fecha_realizacion'] ?? $t['fecha_programada'] ?? null);
            if (!$fecha) continue;
            $fechaDia = substr($fecha, 0, 10);
            if (isset($dailyCounts[$fechaDia])) $dailyCounts[$fechaDia]++;

            // semana ISO
            try {
                $dt = new \DateTimeImmutable($fechaDia);
                $weekKey = $dt->format('o-\WW');
                if (!isset($completedWeekCounts[$weekKey])) $completedWeekCounts[$weekKey] = 0;
                $estado = is_object($t) ? $t->estado : ($t['estado'] ?? null);
                $fechaRealVal = is_object($t) ? ($t->fecha_realizacion ?? null) : ($t['fecha_realizacion'] ?? null);
                if ($estado === 'Completada' || !empty($fechaRealVal)) {
                    $completedWeekCounts[$weekKey]++;
                }
            } catch (\Exception $e) { /* ignore */ }

            $typeId = is_object($t) ? ($t->id_tipo_tarea ?? null) : ($t['id_tipo_tarea'] ?? null);
            if ($typeId !== null && isset($typeCounts[$typeId])) $typeCounts[$typeId]++;
        }

        // preparar weekly labels (últimas 4 semanas)
        $weeklyLabels = []; $weeklyData = [];
        for ($i = 3; $i >= 0; $i--) {
            $dt = (new \DateTimeImmutable('today'))->sub(new \DateInterval("P" . ($i*7) . "D"));
            $weekKey = $dt->format('o-\WW');
            $label = 'Sem ' . $dt->format('W') . ' ' . $dt->format('Y');
            $weeklyLabels[] = $label;
            $weeklyData[] = $completedWeekCounts[$weekKey] ?? 0;
        }

        // type arrays
        $typeLabels=[]; $typeData=[];
        foreach ($typeMap as $id=>$name) { $typeLabels[]=$name; $typeData[]=$typeCounts[$id] ?? 0; }

        // KPIs (global sobre plansForCharts)
        $k = [
            'porcentaje_completado' => 0, 'tareas_completadas' => 0, 'tareas_pendientes' => 0, 'racha_dias' => 0, 'total_tareas' => 0
        ];
        $totalTasks = count($tasks);
        $completedTasks = 0;
        $completedDates = [];
        $sinceDate = date('Y-m-d', strtotime('-28 days'));
        $tasksLast28 = 0;
        foreach ($tasks as $t) {
            $estado = is_object($t) ? $t->estado : ($t['estado'] ?? null);
            $fecha_real = is_object($t) ? ($t->fecha_realizacion ?? null) : ($t['fecha_realizacion'] ?? null);
            if ($estado === 'Completada' || !empty($fecha_real)) {
                $completedTasks++;
                $d = $fecha_real ? substr($fecha_real,0,10) : null;
                if ($d) $completedDates[$d] = true;
                if ($d && $d >= $sinceDate) $tasksLast28++;
            } else {
                if ($fecha_real && substr($fecha_real,0,10) >= $sinceDate) $tasksLast28++;
            }
        }
        $k['total_tareas'] = $totalTasks;
        $k['tareas_completadas'] = $completedTasks;
        $k['tareas_pendientes'] = max(0, $totalTasks - $completedTasks);
        $k['porcentaje_completado'] = $totalTasks ? round(($completedTasks / $totalTasks) * 100, 1) : 0;
        $k['tareas_por_semana'] = round($tasksLast28 / 4, 1);
        // racha
        $streak = 0; $today = new \DateTimeImmutable(date('Y-m-d'));
        for ($i=0;$i<60;$i++) {
            $d = $today->sub(new \DateInterval("P{$i}D"))->format('Y-m-d');
            if (isset($completedDates[$d])) $streak++; else break;
        }
        $k['racha_dias'] = $streak;

        $response = [
            'success' => true,
            'selected_paciente' => $selectedPaciente,
            'kpis_general' => $k, // simplificado: usamos mismo k para general (si quisieras separar, calcular ambos)
            'kpis_filtrado' => ($selectedPaciente ? $k : null),
            'charts' => [
                'daily' => ['labels' => $dailyLabels, 'data' => array_values($dailyCounts)],
                'byType' => ['labels' => $typeLabels, 'data' => $typeData],
                'weekly' => ['labels' => $weeklyLabels, 'data' => $weeklyData]
            ]
        ];

        return $this->response->setJSON($response);
    }
}
