<?php

namespace App\Models;

use CodeIgniter\Model;

class PlanModel extends Model
{
    protected $table            = 'planes';
    protected $primaryKey       = 'id';
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = ['nombre', 'descripcion', 'id_profesional', 'id_paciente', 'nombre_diagnostico', 'fecha_inicio', 'fecha_fin'];
    protected $useTimestamps    = false;

    protected $validationRules = [
        'nombre'        => 'required|min_length[3]',
        'id_profesional'=> 'required|numeric',
        'id_paciente'   => 'required|numeric',
        'fecha_inicio'  => 'required|valid_date',
        'fecha_fin'     => 'required|valid_date'
    ];

    // Métodos personalizados
    public function getPlanCompleto($idPlan)
    {
        return $this->select('planes.*, 
                             prof.nombre as nombre_profesional, 
                             prof.apellido as apellido_profesional,
                             pac.nombre as nombre_paciente, 
                             pac.apellido as apellido_paciente,
                             diagnosticos.descripcion as descripcion_diagnostico')
                    ->join('usuarios as prof', 'prof.id_usuario = planes.id_profesional')
                    ->join('usuarios as pac', 'pac.id_usuario = planes.id_paciente')
                    ->join('diagnosticos', 'diagnosticos.nombre = planes.nombre_diagnostico', 'left')
                    ->where('planes.id', $idPlan)
                    ->first();
    }

    public function getTareasDelPlan($idPlan)
    {
        $tareaModel = new TareaModel();
        return $tareaModel->where('id_plan', $idPlan)->findAll();
    }

    public function getPlanesActivos()
    {
        $hoy = date('Y-m-d');
        return $this->where('fecha_inicio <=', $hoy)
                    ->where('fecha_fin >=', $hoy)
                    ->findAll();
    }

    public function getPlanesPorProfesional($idProfesional)
    {
        return $this->where('id_profesional', $idProfesional)->findAll();
    }

    public function getPlanesPorPaciente($idPaciente)
    {
        return $this->where('id_paciente', $idPaciente)->findAll();
    }
}