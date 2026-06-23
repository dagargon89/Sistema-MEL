<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/** Metas mensuales normalizadas M01–M18 (doc 03 §3.3). */
class MetaMensualModel extends Model
{
    protected $table         = 'metas_mensuales';
    protected $primaryKey    = 'id_meta_mensual';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = ['id_meta', 'mes', 'valor'];
}
