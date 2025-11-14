<?php
// app/Controllers/UsuarioController.php
namespace App\Controllers;

use App\Models\UsuarioModel;

class UsuarioController extends BaseController
{
    // --- MÉTODOS PARA EL ADMIN (ABMC) ---
    // (ANTES: listarUsuarios)
    public function index()
    {
        $usuarioModel = new UsuarioModel();
        $data['usuarios'] = $usuarioModel->findAll();
        return view('admin/listar_usuarios', $data); // O la vista que uses
    }

    // (ANTES: verUsuario)
    public function show($id)
    {
        $usuarioModel = new UsuarioModel();
        $data['usuario'] = $usuarioModel->find($id);
        // ... (lógica de 404 si no existe)
        return view('admin/ver_usuario', $data);
    }

    public function create()
    {
        // Muestra el formulario para crear un usuario nuevo
        return view('admin/crear_usuario'); 
    }

    public function store()
    {
        // Lógica para guardar (POST) un nuevo usuario
        // ... (validar y guardar)
        return redirect()->to(base_url('admin/usuarios'));
    }

    public function edit($id)
    {
        // Muestra el formulario para editar
        $usuarioModel = new UsuarioModel();
        $data['usuario'] = $usuarioModel->find($id);
        return view('admin/editar_usuario', $data);
    }

    public function update($id)
    {
        // Lógica para actualizar (POST o PUT) un usuario
        // ... (validar y actualizar)
        return redirect()->to(base_url('admin/usuarios'));
    }

    // (ANTES: eliminarUsuario)
    public function destroy($id)
    {
        $usuarioModel = new UsuarioModel();
        // ... (lógica de borrado)
        return redirect()->to(base_url('admin/usuarios'));
    }


    // --- MÉTODOS PARA OTROS ROLES (PERSONALIZADOS) ---
    
    // (Este está perfecto como lo tenías)
    public function listarPacientes()
    {
        $usuarioModel = new UsuarioModel();
        $idProfesional = $this->session->get('id_usuario');
        $data['pacientes'] = $usuarioModel->getPacientesPorProfesional($idProfesional);
        
        // Asumo que se muestra en el dashboard del profesional
        return view('profesional/dashboard', $data); 
    }
}