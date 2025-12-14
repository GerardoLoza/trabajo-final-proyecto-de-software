<?php namespace App\Models;
use CodeIgniter\Model;

class PlanEstandarModel extends Model {
    protected $table = 'planes_estandar';
    protected $primaryKey = 'id';
    protected $allowedFields = ['nombre', 'descripcion', 'nombre_diagnostico'];
    protected $returnType = 'object';
}