<?php
namespace App\Models;

use CodeIgniter\Model;

class DocumentoModel extends Model
{
    protected $table = 'documentos';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'id_paciente',
        'id_plan',
        'id_tarea', 
        'tipo',
        'titulo',
        'archivo',
        'mime',
        'tamano'
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = '';

    protected $validationRules = [
        'id_paciente' => 'required|is_natural_no_zero',
        'titulo' => 'required|min_length[3]',
        'tipo' => 'required|in_list[receta,estudio,informe,otro,Comprobante]', 
        'archivo' => 'required',
        'mime' => 'required',
        'tamano' => 'required|integer',
    ];
}