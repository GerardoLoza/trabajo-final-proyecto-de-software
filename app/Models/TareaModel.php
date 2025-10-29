<?php

namespace App\Models;

use CodeIgniter\Model;

class TareaModel extends Model
{
    protected $table            = 'tareas';
    protected $primaryKey       = 'id_tarea';
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = ['id_plan', 'id_tipo_tarea', 'num_tarea', 'descripcion', 'fecha_programada', 'fecha_fin_programada', 'estado', 'comentarios_paciente', 'fecha_realizacion'];
    protected $useTimestamps    = false;

    protected $validationRules = [
        'id_plan'           => 'required|numeric',
        'id_tipo_tarea'     => 'required|numeric',
        'descripcion'       => 'required',
        'fecha_programada'  => 'required|valid_date'
    ];

    // Métodos personalizados
    public function getTareaCompleta($idTarea)
    {
        return $this->select('tareas.*, 
                             tipos_tarea.nombre as tipo_tarea,
                             planes.nombre as nombre_plan')
                    ->join('tipos_tarea', 'tipos_tarea.id_tipo_tarea = tareas.id_tipo_tarea')
                    ->join('planes', 'planes.id = tareas.id_plan')
                    ->where('tareas.id_tarea', $idTarea)
                    ->first();
    }

    public function getTareasPendientes($idPlan = null)
    {
        $builder = $this->where('estado', 'Pendiente');
        
        if ($idPlan) {
            $builder->where('id_plan', $idPlan);
        }
        
        return $builder->findAll();
    }

    public function getTareasCompletadas($idPlan = null)
    {
        $builder = $this->where('estado', 'Completada');
        
        if ($idPlan) {
            $builder->where('id_plan', $idPlan);
        }
        
        return $builder->findAll();
    }

    public function marcarComoCompletada($idTarea, $comentarios = '')
    {
        $data = [
            'estado' => 'Completada',
            'fecha_realizacion' => date('Y-m-d H:i:s'),
            'comentarios_paciente' => $comentarios
        ];
        try {
            return $this->update($idTarea, $data);
        } catch (\Exception $e) {
            // Registro sencillo del error; si el proyecto usa log_message, preferirlo
            error_log('TareaModel::marcarComoCompletada error: ' . $e->getMessage());
            return false;
        }
    }

    public function getTareasPorFecha($fecha, $idPlan = null)
    {
        $builder = $this->where('DATE(fecha_programada)', $fecha);
        
        if ($idPlan) {
            $builder->where('id_plan', $idPlan);
        }
        
        return $builder->findAll();
    }
}