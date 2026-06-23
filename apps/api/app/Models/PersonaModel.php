<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Personas únicas — DERIVADA (doc 03 §3.2, ADR-003). PK string (CHAR), sin
 * autoincrement. No existe alta manual: solo la escribe el DeduplicacionService.
 */
class PersonaModel extends Model
{
    protected $table            = 'personas';
    protected $primaryKey       = 'id_persona';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $useTimestamps    = false;

    protected $allowedFields = [
        'id_persona', 'nombres', 'apellido_paterno', 'apellido_materno', 'nombre_completo',
        'anio_nacimiento', 'sexo', 'telefono', 'correo', 'colonia',
        'id_datosbeneficiario', 'primera_participacion', 'total_participaciones',
        'control_registro', 'decision_coordinacion',
    ];
}
