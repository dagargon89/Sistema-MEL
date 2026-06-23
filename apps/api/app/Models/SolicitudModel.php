<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Solicitudes de corrección/mejora/ajuste (doc 03 §3.7). `id_solicitante`,
 * `fecha_solicitud`, `rol_solicitante` y `estado` los fija el servidor.
 */
class SolicitudModel extends Model
{
    protected $table         = 'solicitudes';
    protected $primaryKey    = 'id_solicitud';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = ['entidad_afectada', 'descripcion', 'tipo_solicitud', 'nivel_criticidad', 'impacto'];
}
