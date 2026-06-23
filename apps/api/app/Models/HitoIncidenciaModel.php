<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/** Hitos de incidencia (doc 03 §3.5). `registrado_por` lo fija el servidor. */
class HitoIncidenciaModel extends Model
{
    protected $table         = 'hitos_incidencia';
    protected $primaryKey    = 'id_hito';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['id_proceso_incidencia', 'fecha_hito', 'tipo_hito', 'descripcion_hito', 'evidencia_nombre_o_nota', 'observaciones'];
}
