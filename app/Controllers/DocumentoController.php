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

    public function listByPatient($idPaciente)
    {
        // Verificación de seguridad básica (idealmente verificar si es paciente del profesional)
        if ($this->session->get('nombre_rol') !== 'Profesional') {
            return $this->response->setStatusCode(403)->setJSON(['error' => 'No autorizado']);
        }

        $docModel = new DocumentoModel();
        $docs = $docModel
            ->select('documentos.*, planes.nombre AS nombre_plan')
            ->join('planes', 'planes.id = documentos.id_plan', 'left')
            ->where('documentos.id_paciente', $idPaciente)
            ->orderBy('documentos.created_at', 'DESC')
            ->findAll();

        return $this->response->setJSON(['success' => true, 'data' => $docs]);
    }

    /**
     * POST: Subida de documentos por parte del profesional
     */
    public function uploadProfesional()
    {
        if ($this->session->get('nombre_rol') !== 'Profesional') {
             return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'No autorizado']);
        }

        $file = $this->request->getFile('archivo');
        $idPaciente = $this->request->getPost('id_paciente');

        if (!$file || !$file->isValid() || !$idPaciente) {
            return $this->response->setJSON(['success' => false, 'message' => 'Datos inválidos o archivo faltante.']);
        }

        // Validaciones
        $maxSize = 10 * 1024 * 1024; // 10 MB para profesionales
        $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx'];
        
        if ($file->getSize() > $maxSize || !in_array(strtolower($file->getExtension()), $allowed)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Formato o tamaño no permitido.']);
        }

        try {
            $newName = $file->getRandomName();
            $path = WRITEPATH . 'uploads/documentos';
            
            if (!is_dir($path)) mkdir($path, 0755, true);
            
            if (!$file->move($path, $newName)) {
                throw new \Exception('Fallo al mover el archivo');
            }

            $docModel = new DocumentoModel();
            $data = [
                'id_paciente' => $idPaciente,
                'id_plan'     => $this->request->getPost('id_plan') ?: null,
                'tipo'        => $this->request->getPost('tipo'),
                'titulo'      => $this->request->getPost('titulo'),
                'archivo'     => 'documentos/' . $newName,
                'mime'        => $file->getClientMimeType(),
                'tamano'      => $file->getSize(),
            ];

            $docModel->insert($data);

            return $this->response->setJSON(['success' => true, 'message' => 'Documento cargado correctamente.']);

        } catch (\Throwable $e) {
            return $this->response->setJSON(['success' => false, 'message' => 'Error del servidor: ' . $e->getMessage()]);
        }
    }

    public function downloadProf($id = null)
    {
        $docModel = new DocumentoModel();
        $doc = $docModel->find($id);

        if (!$doc) {
            return $this->response->setStatusCode(404)->setBody('Documento no encontrado');
        }

        // Seguridad: Verificar si el usuario actual (Profesional) tiene acceso a este paciente
        // (Aquí podrías agregar una validación más estricta consultando si el paciente pertenece al profesional)
        if ($this->session->get('nombre_rol') !== 'Profesional') {
            return $this->response->setStatusCode(403)->setBody('No autorizado');
        }

        $path = WRITEPATH . 'uploads/' . $doc->archivo;
        
        // Verificar existencia física
        if (!is_file($path)) {
            return $this->response->setStatusCode(404)->setBody('El archivo físico no existe en el servidor.');
        }

        return $this->response->download($path, null);
    }

    // Actualizar el método delete para permitir al profesional borrar
    public function delete($id = null)
    {
        $docModel = new DocumentoModel();
        $doc = $docModel->find($id);
        $userId = $this->session->get('id_usuario');
        $rol = $this->session->get('nombre_rol');

        if (!$doc) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'No encontrado']);
        }

        // Permitir si es el dueño O si es Profesional
        if ($doc->id_paciente != $userId && $rol !== 'Profesional') {
            return $this->response->setJSON(['status' => 'error', 'message' => 'No autorizado']);
        }

        $fullPath = WRITEPATH . 'uploads/' . $doc->archivo;
        if (is_file($fullPath)) { @unlink($fullPath); }
        $docModel->delete($id);

        return $this->response->setJSON(['status' => 'success', 'message' => 'Documento eliminado']);
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

}
