<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\PlanEstandarModel;
use App\Models\TareaEstandarModel;

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