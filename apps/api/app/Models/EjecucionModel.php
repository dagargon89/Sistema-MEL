<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Ejecuciones reales (doc 03 §3.2). `$allowedFields` excluye `control_registro`,
 * `nombre_archivo_evidencia` y `total_participantes`: los calcula el servidor (SRS §4).
 */
class EjecucionModel extends Model
{
    protected $table         = 'ejecuciones';
    protected $primaryKey    = 'id_ejecucion';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'id_evento_programado', 'fecha_ejecucion_real',
        'hora_inicio_real', 'hora_finalizacion_real',
        'lugar_real', 'colonia_real', 'responsable_real',
        'estatus_ejecucion', 'tipo_registro_participacion',
        'evidencia_url', 'resumen_narrativo', 'observaciones',
    ];
}
