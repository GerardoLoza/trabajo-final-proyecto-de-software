<?php
namespace App\Controllers;

use App\Models\UsuarioModel;

class Usuarios extends BaseController
{
    public function index()
    {
        $model = new UsuarioModel();
        $data['usuarios'] = $model->getUsuarioActivo();
        
        return view('usuarios/lista', $data);
    }
}