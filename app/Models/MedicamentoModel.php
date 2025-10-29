<?php

namespace App\Models;

use CodeIgniter\Model;

class MedicamentoModel extends Model
{
    protected $table            = 'medicamento';
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
}