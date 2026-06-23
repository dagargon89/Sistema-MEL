<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/** Sostenibilidad financiera (doc 03 §3.6). Utilidad/acumulados/semáforo se calculan. */
class SostenibilidadFinancieraModel extends Model
{
    protected $table         = 'sostenibilidad_financiera';
    protected $primaryKey    = 'id_registro';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'id_actividad', 'mes_periodo', 'ingresos_brutos', 'costos_directos', 'costos_indirectos',
        'recursos_efectivo', 'recursos_especie', 'fuente_datos', 'meta_anual',
    ];
}
