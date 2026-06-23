<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/** Metas anuales por actividad (doc 03 §3.3). */
class MetaModel extends Model
{
    protected $table         = 'metas';
    protected $primaryKey    = 'id_meta';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'id_actividad', 'unidad_meta', 'unidad_especifica', 'meta_anual_total', 'observaciones',
    ];
}
