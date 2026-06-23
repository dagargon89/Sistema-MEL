<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/** Ocupación de shelter (doc 03 §3.6). `pct_ocupacion` se calcula, no se almacena. */
class OcupacionShelterModel extends Model
{
    protected $table         = 'ocupacion_shelter';
    protected $primaryKey    = 'id_ocupacion';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['id_actividad', 'mes_periodo', 'tipo_espacio', 'capacidad_instalada', 'ocupacion', 'fuente'];
}
