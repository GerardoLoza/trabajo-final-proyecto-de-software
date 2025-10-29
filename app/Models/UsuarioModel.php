<?php

namespace App\Models;

use CodeIgniter\Model;

class UsuarioModel extends Model
{
    protected $table            = 'usuarios';
    protected $primaryKey       = 'id_usuario';
    protected $returnType       = 'object';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = ['email', 'nombre', 'apellido', 'password', 'nombre_rol', 'descripcion_perfil'];
    protected $useTimestamps    = false;

    // Validación
    protected $validationRules = [
        'email'     => 'required|valid_email|is_unique[usuarios.email]',
        'nombre'    => 'required|min_length[2]|max_length[100]',
        'apellido'  => 'required|min_length[2]|max_length[100]',
        'password'  => 'required|min_length[6]',
        'nombre_rol'=> 'permit_empty'
    ];

    protected $validationMessages = [
        'email' => [
            'is_unique' => 'Este email ya está registrado.'
        ]
    ];

    // Métodos personalizados
    public function getPacientes()
    {
        return $this->where('nombre_rol', 'paciente')->findAll();
    }

    public function getProfesionales()
    {
        return $this->where('nombre_rol', 'profesional')->findAll();
    }

    public function getPlanesComoProfesional($idProfesional)
    {
        $planModel = new PlanModel();
        return $planModel->where('id_profesional', $idProfesional)->findAll();
    }

    public function getPlanesComoPaciente($idPaciente)
    {
        $planModel = new PlanModel();
        return $planModel->where('id_paciente', $idPaciente)->findAll();
    }

    public function verificarCredenciales($email, $password)
    {
        $usuario = $this->where('email', $email)->first();
        
        if ($usuario && password_verify($password, $usuario->password)) {
            return $usuario;
        }
        
        return false;
    }
}