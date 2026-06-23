<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/** Resultados tipo R (doc 03 §3.4). */
class ResultadoModel extends Model
{
    protected $table         = 'resultados';
    protected $primaryKey    = 'id_resultado';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = ['id_actividad', 'indicador', 'linea_base', 'valor_medido', 'metodo_medicion', 'fecha_medicion', 'evidencia_url'];
}
