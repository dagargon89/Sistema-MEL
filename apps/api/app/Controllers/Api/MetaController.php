<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Repositories\MetaRepository;
use App\Services\MetaService;
use App\Support\Shape;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

/**
 * Metas POA y seguimiento con semáforo (doc 05 §7). Lectura acotada al ámbito;
 * el alta exige rol coordinación (RBAC en la ruta).
 */
class MetaController extends BaseApiController
{
    public function index(): ResponseInterface
    {
        $page  = $this->pageParam();
        $limit = $this->limitParam();

        $ambito = Services::currentScope()->ambitoRepositorio();
        $res    = (new MetaRepository())->listarPaginado($ambito, $this->queryStr('id_actividad'), $page, $limit);

        $data = array_map(Shape::meta(...), $res['rows']);

        return $this->ok($data, 200, $this->pager($page, $limit, $res['total']));
    }

    public function seguimiento(): ResponseInterface
    {
        $filtros = [
            'periodo'     => $this->queryStr('periodo'),
            'institucion' => $this->queryStr('institucion'),
            'eje'         => $this->queryStr('eje'),
        ];

        return $this->ok((new MetaService())->seguimiento($filtros));
    }

    public function create(): ResponseInterface
    {
        $rules = [
            'id_actividad'      => 'required|string|max_length[8]',
            'meta_anual_total'  => 'permit_empty|numeric',
            'unidad_meta'       => 'permit_empty|string|max_length[80]',
            'unidad_especifica' => 'permit_empty|string|max_length[120]',
            'observaciones'     => 'permit_empty|string',
        ];
        if (! $this->validate($rules)) {
            return $this->err(422, 'Datos inválidos.', $this->validator?->getErrors() ?? []);
        }

        $d         = $this->validator?->getValidated() ?? [];
        $mensuales = [];
        if ($this->request instanceof IncomingRequest) {
            $body = $this->request->getJSON(true);
            if (is_array($body) && is_array($body['mensuales'] ?? null)) {
                $mensuales = $body['mensuales'];
            }
        }

        return $this->attempt(fn (): ResponseInterface => $this->ok(Shape::meta((new MetaService())->crearMeta($d, $mensuales)), 201));
    }
}
