<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/** Eventos programados — universo planeado de la cadena MEL (doc 03 §3.2). */
class EventoProgramadoModel extends Model
{
    protected $table         = 'eventos_programados';
    protected $primaryKey    = 'id_evento_programado';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'id_actividad', 'id_proceso', 'tipo_programacion',
        'fecha_inicio', 'fecha_finalizacion', 'hora_inicio', 'hora_finalizacion',
        'modalidad', 'lugar', 'calle_y_numero', 'colonia',
        'responsable', 'contacto', 'estatus', 'num_sesion', 'total_sesiones', 'observaciones',
    ];
}
