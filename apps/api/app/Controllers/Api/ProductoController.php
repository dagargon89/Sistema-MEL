<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Services\ProductoService;
use App\Support\Shape;
use CodeIgniter\HTTP\ResponseInterface;

/** Productos/entregables tipo E (doc 05 §6). Bloquea actividades no-E; acota al ámbito. */
class ProductoController extends BaseApiController
{
    public function create(): ResponseInterface
    {
        $rules = [
            'id_actividad'    => 'required|string|max_length[8]',
            'nombre_producto' => 'required|string|max_length[250]',
            'tipo_producto'   => 'permit_empty|string|max_length[80]',
            'fecha_inicio'    => 'permit_empty|valid_date[Y-m-d]',
            'fecha_entrega'   => 'permit_empty|valid_date[Y-m-d]',
            'responsable'     => 'permit_empty|string|max_length[150]',
            'cantidad'        => 'permit_empty|is_natural',
            'unidad_medida'   => 'permit_empty|string|max_length[60]',
            'estatus'         => 'permit_empty|in_list[en_proceso,entregado,cancelado]',
            'descripcion'     => 'permit_empty|string',
            'evidencia_url'   => 'permit_empty|string|max_length[500]',
        ];
        if (! $this->validate($rules)) {
            return $this->err(422, 'Datos inválidos.', $this->validator?->getErrors() ?? []);
        }

        $d = $this->validator?->getValidated() ?? [];

        return $this->attempt(fn (): ResponseInterface => $this->ok(Shape::producto((new ProductoService())->crearProducto($d)), 201));
    }
}
