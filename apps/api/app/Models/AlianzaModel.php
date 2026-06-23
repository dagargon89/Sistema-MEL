<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/** Alianzas de incidencia (doc 03 §3.5). */
class AlianzaModel extends Model
{
    protected $table         = 'alianzas';
    protected $primaryKey    = 'id_alianza';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['nombre_alianza', 'datos_alianza', 'criterios_elegibilidad', 'id_actividad'];
}
