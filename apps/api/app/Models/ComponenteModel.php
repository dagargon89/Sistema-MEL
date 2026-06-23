<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/** Dimensión: componentes (doc 03 §3.1). */
class ComponenteModel extends Model
{
    protected $table            = 'componentes';
    protected $primaryKey       = 'id_componente';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $useTimestamps    = false;
    protected $allowedFields    = ['id_componente', 'num_componente', 'clave_componente', 'nombre', 'id_institucion', 'orden_visualizacion', 'estatus'];
}
