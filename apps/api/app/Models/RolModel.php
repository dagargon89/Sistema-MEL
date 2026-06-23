<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/** Catálogo de roles (espeja los grupos de Shield). doc 03 §3.7. */
class RolModel extends Model
{
    protected $table         = 'roles';
    protected $primaryKey    = 'id_rol';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['clave', 'nombre', 'descripcion'];
}
