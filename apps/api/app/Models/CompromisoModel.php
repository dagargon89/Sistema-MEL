<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/** Compromisos de incidencia (doc 03 §3.5, RN-004). */
class CompromisoModel extends Model
{
    protected $table         = 'compromisos';
    protected $primaryKey    = 'id_compromiso';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['id_proceso_incidencia', 'identificacion', 'seguimiento_documentado', 'criterios_elegibilidad'];
}
