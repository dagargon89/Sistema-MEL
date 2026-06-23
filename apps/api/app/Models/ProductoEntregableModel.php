<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/**
 * Productos/entregables tipo E (doc 03 §3.2). `control_registro` y
 * `nombre_archivo_evidencia` los fija el servidor; no van en `$allowedFields`.
 */
class ProductoEntregableModel extends Model
{
    protected $table         = 'productos_entregables';
    protected $primaryKey    = 'id_producto';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'id_actividad', 'nombre_producto', 'tipo_producto', 'fecha_inicio', 'fecha_entrega',
        'responsable', 'cantidad', 'unidad_medida', 'estatus', 'descripcion', 'evidencia_url',
    ];
}
