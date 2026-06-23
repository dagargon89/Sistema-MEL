<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Participaciones nominales (doc 03 §3.2). `$allowedFields` solo expone los datos
 * que captura el cliente; `id_persona`, `id_datosbeneficiario`, `alerta_duplicado`,
 * `control_registro`, etc. los calcula el servidor en la deduplicación (RF-PART-041/042).
 */
class ParticipacionModel extends Model
{
    protected $table         = 'participaciones';
    protected $primaryKey    = 'id_participacion';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'id_ejecucion', 'nombres', 'apellido_paterno', 'apellido_materno',
        'anio_nacimiento', 'sexo', 'telefono', 'correo', 'colonia_persona',
    ];
}
