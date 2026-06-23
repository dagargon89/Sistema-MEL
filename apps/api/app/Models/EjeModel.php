<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/** Dimensión: ejes estratégicos (doc 03 §3.1). */
class EjeModel extends Model
{
    protected $table            = 'ejes';
    protected $primaryKey       = 'id_eje';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $useTimestamps    = false;
    protected $allowedFields    = ['id_eje', 'num_eje_original', 'clave_eje_corto', 'nombre', 'orden_visualizacion'];
}
