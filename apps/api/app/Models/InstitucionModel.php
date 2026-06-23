<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/** Dimensión: instituciones (doc 03 §3.1). */
class InstitucionModel extends Model
{
    protected $table            = 'instituciones';
    protected $primaryKey       = 'id_institucion';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $useTimestamps    = false;
    protected $allowedFields    = ['id_institucion', 'num_institucion_original', 'nombre', 'estatus', 'orden_visualizacion'];
}
