<?php

namespace App\Models;

use CodeIgniter\Model;

class TipoTareaModel extends Model
{
    protected $table            = 'tipos_tarea';
    protected $primaryKey       = 'id_tipo_tarea';
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = ['nombre'];
    protected $useTimestamps    = false;

    // Validación
    protected $validationRules = [
        'nombre' => 'required|min_length[2]|max_length[191]'
    ];

    public function getTareasPorTipo($idTipoTarea)
    {
        $tareaModel = new TareaModel();
        return $tareaModel->where('id_tipo_tarea', $idTipoTarea)->findAll();
    }
}