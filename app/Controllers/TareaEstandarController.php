<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\TareaEstandarModel;

class TareaEstandarController extends BaseController
{
    // GET /profesional/planes-estandar/(:num)/tareas
    public function indexPorPlan($idPlan)
    {
        $model = new TareaEstandarModel();
        $tareas = $model->select('tareas_estandar.*, tipos_tarea.nombre as nombre_tipo')
                        ->join('tipos_tarea', 'tipos_tarea.id_tipo_tarea = tareas_estandar.id_tipo_tarea')
                        ->where('id_plan_estandar', $idPlan)
                        ->orderBy('dia_relativo', 'ASC')
                        ->findAll();
                        
        return $this->response->setJSON(['success' => true, 'data' => $tareas]);
    }

    // POST /profesional/tareas-estandar
    public function create()
    {
        $model = new TareaEstandarModel();
        
        $med = $this->request->getPost('nombre_medicamento');
        
        $data = [
            'id_plan_estandar'   => $this->request->getPost('id_plan_estandar'),
            'descripcion'        => $this->request->getPost('descripcion'),
            'id_tipo_tarea'      => $this->request->getPost('id_tipo_tarea'),
            'dia_relativo'       => $this->request->getPost('dia_relativo'),
            'nombre_medicamento' => empty($med) ? null : $med
        ];

        if ($model->insert($data)) {
            return $this->response->setJSON(['success' => true]);
        }
        return $this->response->setJSON(['success' => false]);
    }

    // DELETE /profesional/tareas-estandar/(:num)
    public function delete($id = null)
    {
        $model = new TareaEstandarModel();
        if ($model->delete($id)) {
            return $this->response->setJSON(['success' => true]);
        }
        return $this->response->setJSON(['success' => false]);
    }
}