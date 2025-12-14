<?php namespace App\Models;
use CodeIgniter\Model;

class TareaEstandarModel extends Model {
    protected $table = 'tareas_estandar';
    protected $primaryKey = 'id';
    protected $allowedFields = ['id_plan_estandar', 'descripcion', 'id_tipo_tarea', 'dia_relativo', 'nombre_medicamento'];
    protected $returnType = 'object';
}