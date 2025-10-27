<?php
namespace App\Models;

use CodeIgniter\Model;

class UsuarioModel extends Model
{
    protected $table = 'usuarios';
    protected $primaryKey = 'id';
    protected $allowedFields = ['nombre', 'email'];
    
    // Método personalizado
    public function getUsuarioActivo() {
        return $this->where('activo', 1)
                   ->findAll();
    }
}