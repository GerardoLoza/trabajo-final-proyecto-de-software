<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\PlanEstandarModel;
use App\Models\TareaEstandarModel;
use App\Models\TareaModel;
use App\Models\PlanModel;

class PlanEstandarController extends BaseController
{
    /**
     * Devuelve las tareas de una plantilla específica (JSON)
     * Usado por el modal de consulta.
     */
    public function getTareas($idPlanEstandar)
    {
        $tareaModel = new TareaEstandarModel();
        
        // Obtenemos las tareas ordenadas por día relativo
        $tareas = $tareaModel->where('id_plan_estandar', $idPlanEstandar)
                             ->orderBy('dia_relativo', 'ASC')
                             ->findAll();

        if (empty($tareas)) {
            return $this->response->setJSON(['success' => false, 'message' => 'No hay tareas definidas.']);
        }

        return $this->response->setJSON(['success' => true, 'data' => $tareas]);
    }

    // Aquí irían en el futuro los métodos create, update, delete para las plantillas
    public function create()
    {
        $json = $this->request->getJSON(); // Leer JSON del fetch
        
        // Validar datos mínimos
        if (!$json || empty($json->nombre) || empty($json->nombre_diagnostico)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Faltan datos obligatorios del plan.']);
        }

        $planModel = new PlanEstandarModel();
        $tareaModel = new TareaEstandarModel();
        $db = \Config\Database::connect();

        try {
            $db->transStart(); // Iniciar transacción

            // 1. Insertar Cabecera del Plan
            $planData = [
                'nombre' => $json->nombre,
                'descripcion' => $json->descripcion ?? '',
                'nombre_diagnostico' => $json->nombre_diagnostico,
            ];
            
            $planModel->insert($planData);
            $newPlanId = $planModel->getInsertID();

            // 2. Insertar Tareas (si vienen)
            if (!empty($json->tareas) && is_array($json->tareas)) {
                foreach ($json->tareas as $t) {
                    $tareaData = [
                        'id_plan_estandar' => $newPlanId,
                        'descripcion'      => $t->descripcion,
                        'id_tipo_tarea'    => $t->id_tipo_tarea,
                        'dia_relativo'     => $t->dia_relativo,
                        'nombre_medicamento' => !empty($t->nombre_medicamento) ? $t->nombre_medicamento : null
                    ];
                    $tareaModel->insert($tareaData);
                }
            }

            $db->transComplete(); // Confirmar todo

            if ($db->transStatus() === false) {
                return $this->response->setJSON(['success' => false, 'message' => 'Error en la base de datos.']);
            }

            return $this->response->setJSON(['success' => true, 'message' => 'Plantilla creada con éxito.']);

        } catch (\Exception $e) {
            return $this->response->setJSON(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function assign()
    {
        $request = $this->request->getJSON(); // Recibimos JSON desde el modal

        // Validaciones básicas
        if (!$request || empty($request->id_plan_estandar) || empty($request->id_paciente) || empty($request->fecha_inicio)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Faltan datos requeridos (Plan, Paciente o Fecha Inicio).']);
        }

        $db = \Config\Database::connect();
        
        // Modelos necesarios
        $stdPlanModel = new PlanEstandarModel();
        $stdTareaModel = new TareaEstandarModel();
        $realPlanModel = new PlanModel();
        $realTareaModel = new TareaModel();

        // 1. Obtener datos de la plantilla
        $plantilla = $stdPlanModel->find($request->id_plan_estandar);
        if (!$plantilla) {
            return $this->response->setJSON(['success' => false, 'message' => 'Plantilla no encontrada.']);
        }

        try {
            $db->transStart();

            // 2. Crear el Plan Real (Cabecera)
            $planData = [
                'nombre'             => $plantilla->nombre, // Hereda nombre
                'descripcion'        => $plantilla->descripcion, // Hereda descripción
                'id_profesional'     => session()->get('id_usuario'),
                'id_paciente'        => $request->id_paciente,
                'nombre_diagnostico' => $plantilla->nombre_diagnostico,
                'fecha_inicio'       => $request->fecha_inicio,
                'fecha_fin'          => $request->fecha_fin ?? null, // Puede ser null o calculado
                'estado'             => 'Vigente'
            ];

            $realPlanId = $realPlanModel->insert($planData, true); // true para return ID

            // 3. Obtener tareas de la plantilla y clonarlas
            $tareasPlantilla = $stdTareaModel->where('id_plan_estandar', $plantilla->id)->findAll();

            if (!empty($tareasPlantilla)) {
                $fechaInicioBase = new \DateTime($request->fecha_inicio);
                $numTarea = 1;

                foreach ($tareasPlantilla as $tp) {
                    // CÁLCULO DE FECHA: Fecha Inicio + (Día Relativo - 1)
                    // Ej: Inicio 01/01. Día relativo 1 -> 01/01. Día relativo 3 -> 03/01.
                    $fechaTarea = clone $fechaInicioBase;
                    $diasSumar = max(0, $tp->dia_relativo - 1); // Asegurar no restar días
                    $fechaTarea->modify("+$diasSumar days");
                    
                    // Asignamos una hora por defecto (ej: 09:00 AM) para que no sea 00:00
                    $fechaTarea->setTime(9, 0, 0);

                    $tareaData = [
                        'id_plan'            => $realPlanId,
                        'id_tipo_tarea'      => $tp->id_tipo_tarea,
                        'num_tarea'          => $numTarea++,
                        'descripcion'        => $tp->descripcion,
                        'fecha_programada'   => $fechaTarea->format('Y-m-d H:i:s'),
                        'estado'             => 'Pendiente',
                        'nombre_medicamento' => $tp->nombre_medicamento
                    ];

                    $realTareaModel->insert($tareaData);
                }
            }

            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->response->setJSON(['success' => false, 'message' => 'Error al guardar en base de datos.']);
            }

            return $this->response->setJSON(['success' => true, 'message' => 'Plan asignado correctamente al paciente.']);

        } catch (\Exception $e) {
            return $this->response->setJSON(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    // DELETE /profesional/planes-estandar/(:num)
    public function delete($id = null)
    {
        $model = new PlanEstandarModel();
        if ($model->delete($id)) {
            return redirect()->back()->with('success', 'Plantilla eliminada.');
        }
        return redirect()->back()->with('error', 'No se pudo eliminar.');
    }
}