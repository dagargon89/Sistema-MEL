<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/** Procesos/grupos de la cadena MEL (doc 03 §3.2). */
class ProcesoModel extends Model
{
    protected $table         = 'procesos';
    protected $primaryKey    = 'id_proceso';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'nombre', 'tipo_programacion', 'id_actividad',
        'fecha_inicio', 'fecha_fin', 'total_sesiones_programadas',
        'responsable', 'contacto', 'estatus', 'observaciones',
    ];
}
