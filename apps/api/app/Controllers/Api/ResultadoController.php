<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Services\ResultadoService;
use App\Support\Shape;
use CodeIgniter\HTTP\ResponseInterface;

/** Resultados tipo R (doc 05 §10). Bloquea actividades no-R; acota al ámbito. */
class ResultadoController extends BaseApiController
{
    public function create(): ResponseInterface
    {
        $rules = [
            'id_actividad'    => 'required|string|max_length[8]',
            'indicador'       => 'required|string|max_length[250]',
            'linea_base'      => 'permit_empty|numeric',
            'valor_medido'    => 'permit_empty|numeric',
            'metodo_medicion' => 'permit_empty|string|max_length[200]',
            'fecha_medicion'  => 'permit_empty|valid_date[Y-m-d]',
            'evidencia_url'   => 'permit_empty|string|max_length[500]',
        ];
        if (! $this->validate($rules)) {
            return $this->err(422, 'Datos inválidos.', $this->validator?->getErrors() ?? []);
        }
        $d = $this->validator?->getValidated() ?? [];

        return $this->attempt(fn (): ResponseInterface => $this->ok(Shape::resultado((new ResultadoService())->crearResultado($d)), 201));
    }
}
