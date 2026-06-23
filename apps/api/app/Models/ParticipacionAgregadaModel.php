<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Conteos agregados (doc 03 §3.2). `tipo_registro_participacion` y `control_registro`
 * los fija el servidor (AGREGADO); el cliente solo aporta el conteo y su contexto.
 */
class ParticipacionAgregadaModel extends Model
{
    protected $table         = 'participaciones_agregadas';
    protected $primaryKey    = 'id_participacion_agregada';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'id_ejecucion', 'cantidad_participantes', 'sexo_grupo', 'grupo_edad_aprox',
        'motivo_no_nominal', 'fuente_conteo', 'periodo_corte', 'evidencia_url',
    ];
}
