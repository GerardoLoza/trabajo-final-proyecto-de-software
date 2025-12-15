<?php

namespace App\Models;

use CodeIgniter\Model;

class TipoDocumentoModel extends Model
{
    protected $table = 'tipo_documentos';
    protected $primaryKey = 'nombre_tipo';
    protected $returnType = 'object';
    protected $allowedFields = ['nombre_tipo'];
}