<?php
namespace App\Controllers;

use App\Models\PlanModel;
use App\Models\UsuarioModel;
use App\Models\DiagnosticoModel;
use App\Models\TipoTareaModel;
use App\Models\TareaModel;
use App\Models\DocumentoModel;

class PlanController extends BaseController
{
    public function index()
    {
        $planModel = new PlanModel();
        $usuarioModel = new UsuarioModel();
        $diagnosticoModel = new DiagnosticoModel();
        $tipoTareaModel = new TipoTareaModel();

        $rol = $this->session->get('nombre_rol');
        $userId = $this->session->get('id_usuario');

        if ($rol === 'Paciente') {
            $listaPlanes = $planModel->getPlanesPorPaciente($userId);
            $misPacientes = [];
        } elseif ($rol === 'Profesional') {
            $listaPlanes = $planModel->getPlanesPorProfesional($userId);
            $misPacientes = $usuarioModel->getPacientesPorProfesional($userId);
        } else {
            $listaPlanes = $planModel->findAll();
            $misPacientes = $usuarioModel->getPacientes();
        }

        $data = [
            // Corrección en conteo para evitar errores si es null
            'totalPacientes'    => is_array($misPacientes) ? count($misPacientes) : 0,
            'planesActivos'     => is_array($listaPlanes) ? count($listaPlanes) : 0,
            'listaPlanes'       => $listaPlanes,
            'listaPacientes'    => $misPacientes,
            'todosLosPacientes' => $usuarioModel->getPacientes(),
            'listaDiagnosticos' => $diagnosticoModel->findAll(),
            'listaTiposTarea'   => $tipoTareaModel->findAll(),
            'soloLectura'       => ($rol === 'Paciente'),
        ];

        return view('dashboard_profesional', $data);
    }

    public function finalizarPlan($idPlan)
    {
        $planModel = new PlanModel();
        $docModel = new DocumentoModel();
        $db = \Config\Database::connect();

        $plan = $planModel->find($idPlan);
        if (!$plan) {
            return $this->response->setJSON(['success' => false, 'message' => 'Plan no encontrado']);
        }

        // Validar inputs
        $comentario = $this->request->getPost('comentario');
        $file = $this->request->getFile('archivo_epicrisis');

        // Iniciar Transacción (para asegurar que se guarden ambos o ninguno)
        $db->transStart();

        try {
            // 1. Actualizar el Plan (Estado y Comentario)
            $planModel->update($idPlan, [
                'estado' => 'Finalizado',
                'comentario_cierre' => $comentario,
                'fecha_fin' => date('Y-m-d') 
            ]);

            // 2. Subir Archivo Epicrisis (Si se cargó uno)
            if ($file && $file->isValid()) {
                $newName = $file->getRandomName();
                $path = WRITEPATH . 'uploads/documentos';
                
                if (!is_dir($path)) mkdir($path, 0755, true);
                $file->move($path, $newName);

                $docModel->insert([
                    'id_paciente' => $plan->id_paciente,
                    'id_plan'     => $idPlan,
                    'tipo'        => 'Epicrisis',
                    'titulo'      => 'Epicrisis de Cierre - Plan #' . $idPlan,
                    'archivo'     => 'documentos/' . $newName,
                    'mime'        => $file->getClientMimeType(),
                    'tamano'      => $file->getSize(),
                ]);
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->response->setJSON(['success' => false, 'message' => 'Error en la base de datos al finalizar.']);
            }

            return $this->response->setJSON(['success' => true, 'message' => 'Plan finalizado y epicrisis cargada.']);

        } catch (\Throwable $e) {
            return $this->response->setJSON(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function gestionPlanes()
    {
        return $this->index();
    }

    /**
     * Muestra el formulario (GET).
     * En Resource Routes de CI4, esto es 'new', no 'create'.
     */
    public function new()
    {
        // Como usas un modal en el dashboard, redirigimos allí.
        return redirect()->to(base_url('profesional'));
    }

    /**
     * Guarda el plan (POST).
     * En Resource Routes de CI4, esto es 'create', no 'store'.
     */
    public function create()
    {
        // 1. Reglas de validación
        $rules = [
            'nombre'             => 'required|min_length[5]|max_length[255]',
            'fecha_inicio'       => 'required|valid_date',
            'fecha_fin'          => 'permit_empty|valid_date',
            'id_paciente'        => 'required|is_natural_no_zero',
            'nombre_diagnostico' => 'required', 
        ];

        if (! $this->validate($rules)) {
            if ($this->request->isAJAX()) {
                 return $this->response->setJSON(['status' => 'error', 'errors' => $this->validator->getErrors()]);
            }
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $usuarioModel = new UsuarioModel();
        $planModel = new PlanModel();
        $tareaModel = new TareaModel();
        $db = \Config\Database::connect();
        
        $currentUserId = session()->get('id_usuario');
        $idPacientePost = $this->request->getPost('id_paciente');

        // 3. Verificaciones
        $paciente = $usuarioModel->find($idPacientePost);
        
        if (!$paciente || $paciente->nombre_rol !== 'Paciente') {
            if ($this->request->isAJAX()) return $this->response->setJSON(['status' => 'error', 'message' => 'El usuario no es paciente.']);
            return redirect()->back()->withInput()->with('error', 'El usuario seleccionado no es válido.');
        }

        // 4. Guardado con Transacción
        try {
            $db->transStart(); // Iniciar transacción

            // A. Insertar Plan
            $dataPlan = [
                'nombre'             => $this->request->getPost('nombre'),
                'descripcion'        => $this->request->getPost('descripcion'),
                'id_profesional'     => $currentUserId,
                'id_paciente'        => $idPacientePost,
                'nombre_diagnostico' => $this->request->getPost('nombre_diagnostico'),
                'fecha_inicio'       => $this->request->getPost('fecha_inicio'),
                'fecha_fin'          => $this->request->getPost('fecha_fin'),
            ];

            $planId = $planModel->insert($dataPlan, true); // true para retornar ID insertado

            // B. Insertar Tareas Iniciales (Requerimiento 1)
            $tareasRaw = $this->request->getPost('tareas'); // Viene del JS script.js
            
            if ($tareasRaw && is_array($tareasRaw)) {
                $numTarea = 1;
                foreach ($tareasRaw as $t) {
                    // Validación mínima de la tarea
                    if (empty($t['descripcion']) || empty($t['fecha_programada'])) continue;

                    // Lógica simple para Tipo de Tarea (Asumimos ID 1 si no se especifica o si viene texto)
                    // Lo ideal es que el JS envíe el ID real desde un select.
                    $tipoTareaId = (isset($t['tipo']) && is_numeric($t['tipo'])) ? $t['tipo'] : 1; 
                    $medicamento = !empty($t['nombre_medicamento']) ? $t['nombre_medicamento'] : null;

                    $dataTarea = [
                        'id_plan'              => $planId,
                        'id_tipo_tarea'        => $tipoTareaId,
                        'num_tarea'            => $numTarea++,
                        'descripcion'          => $t['descripcion'],
                        'fecha_programada'     => $t['fecha_programada'],
                        'nombre_medicamento' => $medicamento,
                        'estado'               => 'Pendiente'
                    ];
                    
                    $tareaModel->insert($dataTarea);
                }
            }

            $db->transComplete(); // Finalizar transacción

        } catch (\Throwable $e) {
            if ($this->request->isAJAX()) return $this->response->setJSON(['status' => 'error', 'message' => 'Error interno: ' . $e->getMessage()]);
            return redirect()->back()->withInput()->with('error', 'Error del sistema al guardar.');
        }

        if ($db->transStatus() === false) {
             if ($this->request->isAJAX()) return $this->response->setJSON(['status' => 'error', 'message' => 'Falló la transacción.']);
            return redirect()->back()->withInput()->with('error', 'No se pudo guardar el plan.');
        }

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['status' => 'success', 'message' => 'Plan y tareas creados correctamente.']);
        }
        
        return redirect()->to('/profesional')->with('success', 'Plan creado correctamente.');
    }

    public function cambiarEstado($idPlan){
        $planModel = new PlanModel();
        $plan = $planModel->find($idPlan);
    
        if (!$plan) return $this->response->setJSON(['success' => false, 'message' => 'Plan no encontrado']);

        // Verificar permisos (Solo profesional dueño)
        $userId = session()->get('id_usuario');
        if (session()->get('nombre_rol') === 'Profesional' && $plan->id_profesional != $userId) {
            return $this->response->setJSON(['success' => false, 'message' => 'No autorizado']);
        }

        // Lógica de cambio (Toggle)
        $nuevoEstado = ($plan->estado === 'Vigente') ? 'Finalizado' : 'Vigente';
    
        // Opcional: Validar reglas de negocio aquí (ej: chequear si fecha fin pasó o tareas al 100%)
        // Como pediste que sea manual, simplemente lo cambiamos.

        $planModel->update($idPlan, ['estado' => $nuevoEstado]);

        return $this->response->setJSON([
            'success' => true, 
            'nuevo_estado' => $nuevoEstado,
            'message' => "Plan marcado como {$nuevoEstado}"
        ]);
    }

    public function show($id = null)
    {
        $planModel = new PlanModel();
        $plan = $planModel->getPlanCompleto($id);
        
        if (!$plan) {
            return redirect()->back()->with('error', 'Plan no encontrado');
        }

        $rol = $this->session->get('nombre_rol');
        $userId = $this->session->get('id_usuario');
        
        // CORRECCIÓN: Sintaxis de objeto
        $esPropietario = ($rol === 'Paciente' && $plan->id_paciente == $userId)
            || ($rol === 'Profesional' && $plan->id_profesional == $userId)
            || ($rol === 'Administrador');

        if (!$esPropietario) {
            return redirect()->to(base_url('paciente'))->with('error', 'No autorizado.');
        }

        $tareas = $planModel->getTareasDelPlan($id);
        
        // Retornar vista detalle o dashboard si no existe
        return view('planes/detalle', ['plan' => $plan, 'tareas' => $tareas]);
    }

    public function edit($id = null)
    {
        // Para edición usamos el modal en el dashboard, así que redirigimos
        return redirect()->to(base_url('profesional'))->with('info', 'Editar desde el listado.');
    }

    public function update($id = null)
    {
       $planModel = new PlanModel();
       $plan = $planModel->find($id);

        if (!$plan) {
            if ($this->request->isAJAX()) return $this->response->setJSON(['status'=>'error', 'message'=>'No encontrado']);
            return redirect()->back()->with('error', 'Plan no encontrado.');
        }

        $currentUserId = session()->get('id_usuario');
        $currentUserRol = session()->get('nombre_rol');

        // CORRECCIÓN: Sintaxis de objeto ($plan->id_profesional)
        if ($currentUserRol === 'Profesional' && $plan->id_profesional != $currentUserId) {
            if ($this->request->isAJAX()) return $this->response->setJSON(['status'=>'error', 'message'=>'No autorizado']);
            return redirect()->back()->with('error', 'No tienes autorización.');
        }

        $rules = [
            'nombre'         => 'required|min_length[5]',
            'fecha_inicio'   => 'required|valid_date',
            // 'nombre_diagnostico' => 'required', // Opcional si permites cambiarlo
        ];

        if (! $this->validate($rules)) {
            if ($this->request->isAJAX()) return $this->response->setJSON(['status'=>'error', 'errors'=>$this->validator->getErrors()]);
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $dataUpdate = [
            'nombre'          => $this->request->getPost('nombre'),
            'descripcion'     => $this->request->getPost('descripcion'),
            'fecha_inicio'    => $this->request->getPost('fecha_inicio'),
            'fecha_fin'       => $this->request->getPost('fecha_fin'),
            'nombre_diagnostico'  => $this->request->getPost('nombre_diagnostico'),
        ];

        if ($planModel->update($id, $dataUpdate)) {
            if ($this->request->isAJAX()) return $this->response->setJSON(['status'=>'success', 'message'=>'Plan actualizado']);
            return redirect()->to(base_url('profesional'))->with('success', 'Plan actualizado.');
        } else {
            if ($this->request->isAJAX()) return $this->response->setJSON(['status'=>'error', 'message'=>'Error al actualizar']);
            return redirect()->back()->withInput()->with('error', 'No se pudo actualizar.');
        }
    }

    public function delete($id = null)
    {
        $planModel = new PlanModel();
        $plan = $planModel->find($id);

        if (!$plan) {
            if ($this->request->isAJAX()) return $this->response->setJSON(['status' => 'error', 'message' => 'Plan no encontrado']);
            return redirect()->back()->with('error', 'Plan no encontrado.');
        }

        $currentUserId = session()->get('id_usuario');
        $currentUserRol = session()->get('nombre_rol');

        // CORRECCIÓN: Sintaxis de objeto
        if ($currentUserRol === 'Profesional' && $plan->id_profesional != $currentUserId) {
            if ($this->request->isAJAX()) return $this->response->setJSON(['status' => 'error', 'message' => 'No autorizado']);
            return redirect()->back()->with('error', 'No tienes permiso.');
        }

        if ($planModel->delete($id)) {
            if ($this->request->isAJAX()) return $this->response->setJSON(['status' => 'success', 'message' => 'Plan eliminado']);
            return redirect()->to(base_url('profesional'))->with('success', 'Plan eliminado.');
        } else {
            if ($this->request->isAJAX()) return $this->response->setJSON(['status' => 'error', 'message' => 'Error al eliminar']);
            return redirect()->back()->with('error', 'Error al eliminar.');
        }
    }

    //HU-12: Vista global de planes para Administrador

    public function globalView(){
        $planModel = new PlanModel();
        $usuarioModel = new UsuarioModel();
    
        // 1. Obtener listado completo (con joins para nombres)
        // Usamos el método que ya tenías o construimos uno similar para lista
        $todosLosPlanes = $planModel->select('planes.*, 
                                        prof.nombre as nombre_profesional, prof.apellido as apellido_profesional,
                                        pac.nombre as nombre_paciente, pac.apellido as apellido_paciente')
                                ->join('usuarios as prof', 'prof.id_usuario = planes.id_profesional')
                                ->join('usuarios as pac', 'pac.id_usuario = planes.id_paciente')
                                ->findAll();

        // 2. Calcular Estadísticas
        $hoy = date('Y-m-d');
    
        // A. Planes Activos vs Completados (Basado en fecha)
        $totalActivos = $planModel->where('fecha_fin >=', $hoy)->countAllResults();
        $totalCompletados = $planModel->where('fecha_fin <', $hoy)->countAllResults();

        // B. Profesionales (Total vs Con Planes vs Sin Planes)
        $totalProfesionales = $usuarioModel->where('nombre_rol', 'Profesional')->countAllResults();
        // Obtenemos IDs únicos de profesionales que están en la tabla planes
        $profsConPlanes = $planModel->distinct()->findColumn('id_profesional') ?? [];
        $countProfsConPlanes = count(array_unique($profsConPlanes));
        $countProfsSinPlanes = $totalProfesionales - $countProfsConPlanes;

        // C. Pacientes (Total vs Con Planes vs Sin Planes)
        $totalPacientes = $usuarioModel->where('nombre_rol', 'Paciente')->countAllResults();
        $pacsConPlanes = $planModel->distinct()->findColumn('id_paciente') ?? [];
        $countPacsConPlanes = count(array_unique($pacsConPlanes));
        $countPacsSinPlanes = $totalPacientes - $countPacsConPlanes;

        $data = [
            'listaPlanes' => $todosLosPlanes,
            'stats' => [
                'activos' => $totalActivos,
                'completados' => $totalCompletados,
                'profs_con' => $countProfsConPlanes,
                'profs_sin' => $countProfsSinPlanes,
                'pacs_con' => $countPacsConPlanes,
                'pacs_sin' => $countPacsSinPlanes
            ]
        ];

        return view('admin/planes_global_view', $data);
    }

}