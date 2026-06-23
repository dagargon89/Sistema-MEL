<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/** Dimensión: líneas de acción (doc 03 §3.1). */
class LineaModel extends Model
{
    protected $table            = 'lineas';
    protected $primaryKey       = 'id_linea';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $useTimestamps    = false;
    protected $allowedFields    = ['id_linea', 'num_linea', 'clave_linea_corta', 'nombre', 'id_eje', 'orden_visualizacion', 'estatus'];
}
