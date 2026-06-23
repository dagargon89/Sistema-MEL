<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/** Catálogo central de actividades (doc 03 §3.1). PK string (no autoincrement). */
class ActividadModel extends Model
{
    protected $table            = 'actividades';
    protected $primaryKey       = 'id_actividad';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $useTimestamps    = false;

    protected $allowedFields = [
        'id_actividad', 'num_actividad', 'nombre',
        'id_eje', 'id_linea', 'id_componente', 'id_institucion',
        'tipo_registro', 'caso_excepcional',
    ];
}
