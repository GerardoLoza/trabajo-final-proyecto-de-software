<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\DocumentoModel;
use App\Models\PlanModel;

class DocumentoController extends BaseController
{
    public function index()
    {
        $docModel  = new DocumentoModel();
        $planModel = new PlanModel();

        $userId = $this->session->get('id_usuario');

        // Traer documentos + nombre del plan (si lo tiene asociado)
        $docs = $docModel
            ->select('documentos.*, planes.nombre AS nombre_plan')
            ->join('planes', 'planes.id = documentos.id_plan', 'left')
            ->where('documentos.id_paciente', $userId)
            ->orderBy('documentos.created_at', 'DESC')
            ->findAll();

        // Traer planes del paciente para el combo
        $planes = $planModel
            ->where('id_paciente', $userId)
            ->orderBy('fecha_inicio', 'DESC')
            ->findAll();

        return view('paciente/documentos', [
            'docs'   => $docs,
            'planes' => $planes,
        ]);
    }

    public function new()
    {
        return redirect()->to(base_url('paciente/documentos'));
    }

    public function create()
    {
        $userId = $this->session->get('id_usuario');
        $file   = $this->request->getFile('archivo');

        if (! $file || ! $file->isValid()) {
            return redirect()->back()->with('error', 'Archivo inválido.');
        }

        // Validaciones de campos simples
        $rules = [
            'tipo'   => 'required|in_list[receta,estudio,informe,otro]',
            'titulo' => 'required|min_length[3]|max_length[255]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        // Validar archivo
        $maxSize = 5 * 1024 * 1024; // 5 MB
        $allowed = ['pdf','jpg','jpeg','png','heic'];
        if ($file->getSize() > $maxSize || ! in_array(strtolower($file->getExtension()), $allowed)) {
            return redirect()->back()->with('error', 'Formato o tamaño no permitido.');
        }

        try {
            // Traducción de selección del combo: viene el ID, mostramos nombre
            $idPlan = $this->request->getPost('id_plan');
            if (!empty($idPlan) && is_numeric($idPlan)) {
                $planModel = new PlanModel();
                // Solo aceptamos planes que realmente pertenezcan a este paciente
                $plan = $planModel
                    ->where('id_paciente', $userId)
                    ->find($idPlan);

                if (! $plan) {
                    // Si el plan no existe o no es suyo, lo ignoramos
                    $idPlan = null;
                }
            } else {
                $idPlan = null;
            }

            // Guardar archivo físico
            $newName = $file->getRandomName();
            $path    = WRITEPATH . 'uploads/documentos';

            if (! is_dir($path)) {
                mkdir($path, 0755, true);
            }

            if (! $file->move($path, $newName)) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Error al guardar el archivo en el servidor.');
            }

            // Insertar registro en DB
            $docModel = new DocumentoModel();
            $docModel->insert([
                'id_paciente' => $userId,
                'id_plan'     => $idPlan,
                'tipo'        => $this->request->getPost('tipo'),
                'titulo'      => $this->request->getPost('titulo'),
                'archivo'     => 'documentos/' . $newName,
                'mime'        => $file->getClientMimeType(),
                'tamano'      => $file->getSize(),
            ]);

            return redirect()
                ->to(base_url('paciente/documentos'))
                ->with('success', 'Documento subido correctamente.');
        } catch (\Throwable $e) {
            log_message('error', 'Error al subir documento: ' . $e->getMessage());

            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al procesar el documento. Probá otra vez o dejá el plan vacío.');
        }
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
