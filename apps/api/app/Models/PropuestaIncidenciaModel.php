<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/** Propuestas de incidencia asesoradas (doc 03 §3.5). */
class PropuestaIncidenciaModel extends Model
{
    protected $table         = 'propuestas_incidencia';
    protected $primaryKey    = 'id_propuesta';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'nombre_propuesta', 'promotor_colectivo', 'tipo_actor', 'fecha_inicio_asesoria',
        'responsable_equipo', 'sesiones_documentadas', 'mejora_documentada', 'cambios_resultado_asesoria',
        'evidencia_principal', 'alineada_proyectos_estrategicos', 'criterios_alineacion_nota',
        'estatus', 'elegible_reporte', 'id_actividad', 'periodo_reporte',
    ];
}
