<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\DocumentoModel;
use App\Models\PlanModel;

class DocumentoController extends BaseController
{
    public function index()
    {
        $docModel = new DocumentoModel();
        $userId = $this->session->get('id_usuario');
        $docs = $docModel->where('id_paciente', $userId)->orderBy('created_at', 'DESC')->findAll();
        return view('paciente/documentos', ['docs' => $docs]);
    }

    public function new()
    {
        return redirect()->to(base_url('paciente/documentos'));
    }

    public function create()
    {
        $userId = $this->session->get('id_usuario');
        $file = $this->request->getFile('archivo');
        if (! $file || ! $file->isValid()) {
            return redirect()->back()->with('error', 'Archivo inválido.');
        }

        // Validaciones de formato/tamaño
        $rules = [
            'tipo'   => 'required|in_list[receta,estudio,informe,otro]',
            'titulo' => 'required|min_length[3]|max_length[255]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $maxSize = 5 * 1024 * 1024; // 5 MB
        $allowed = ['pdf','jpg','jpeg','png','heic'];
        if ($file->getSize() > $maxSize || ! in_array(strtolower($file->getExtension()), $allowed)) {
            return redirect()->back()->with('error', 'Formato o tamaño no permitido.');
        }

        // Guardar archivo en writable/uploads/documentos
        $newName = $file->getRandomName();
        $path = WRITEPATH . 'uploads/documentos';
        $file->move($path, $newName);

        $docModel = new DocumentoModel();
        $idPlan = $this->request->getPost('id_plan');
        // Validar que el plan exista y pertenezca al paciente (opcional pero recomendado)
        if (!empty($idPlan)) {
            $planModel = new PlanModel();
            $plan = $planModel->find($idPlan);
            // Verificar que el plan existe y pertenece al paciente
            if (!$plan || $plan->id_paciente != $userId) {
                $idPlan = null; // Si no es válido, lo dejamos como NULL
            }
        } else {
            $idPlan = null;
        }

        $docModel->insert([
            'id_paciente' => $userId,
            'id_plan'     => $idPlan,  // ✅ Ahora es seguro
            'tipo'        => $this->request->getPost('tipo'),
            'titulo'      => $this->request->getPost('titulo'),

            'archivo'     => 'documentos/' . $newName,
            'mime'        => $file->getClientMimeType(),
            'tamano'      => $file->getSize(),
        ]);

        return redirect()->to(base_url('paciente/documentos'))->with('success', 'Documento subido.');
    }

    public function show($id = null)
    {
        $docModel = new DocumentoModel();
        $doc = $docModel->find($id);
        $userId = $this->session->get('id_usuario');
        if (! $doc || $doc->id_paciente != $userId) {
            return redirect()->back()->with('error', 'No autorizado.');
        }
        return $this->response->download(WRITEPATH . 'uploads/' . $doc->archivo, null);
    }

    public function delete($id = null)
    {
        $docModel = new DocumentoModel();
        $doc = $docModel->find($id);
        $userId = $this->session->get('id_usuario');
        if (! $doc || $doc->id_paciente != $userId) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'No autorizado']);
        }

        // Borrar archivo físico si existe
        $fullPath = WRITEPATH . 'uploads/' . $doc->archivo;
        if (is_file($fullPath)) { @unlink($fullPath); }
        $docModel->delete($id);

        return $this->response->setJSON(['status' => 'success', 'message' => 'Documento eliminado']);
    }
}
