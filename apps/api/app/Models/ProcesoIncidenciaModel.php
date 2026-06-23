<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/** Procesos de incidencia (doc 03 §3.5). */
class ProcesoIncidenciaModel extends Model
{
    protected $table         = 'procesos_incidencia';
    protected $primaryKey    = 'id_proceso_incidencia';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['nombre', 'criterios_elegibilidad', 'ultimo_hito_resumen', 'id_actividad'];
}
