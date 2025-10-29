<?php

namespace App\Models;

use CodeIgniter\Model;

class RolModel extends Model
{
    protected $table            = 'roles';
    protected $primaryKey       = 'nombre';
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = ['nombre'];
    protected $useTimestamps    = false;
    protected $useAutoIncrement = false;

    // Validación
    protected $validationRules = [
        'nombre' => 'required|min_length[2]|max_length[191]'
    ];

    public function getUsuariosPorRol($rol)
    {
        $usuarioModel = new UsuarioModel();
        return $usuarioModel->where('nombre_rol', $rol)->findAll();
    }
}