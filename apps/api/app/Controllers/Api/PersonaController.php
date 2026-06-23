<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Services\DeduplicacionService;
use App\Support\Shape;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Personas consolidadas y cola de deduplicación (doc 05 §5). Solo coordinación/admin
 * (RBAC en la ruta). No existe alta manual de personas: nacen de las participaciones.
 */
class PersonaController extends BaseApiController
{
    public function index(): ResponseInterface
    {
        $page  = $this->pageParam();
        $limit = $this->limitParam();
        $res   = (new DeduplicacionService())->listarPersonas($this->queryStr('control'), $page, $limit);

        $data = array_map(Shape::persona(...), $res['rows']);

        return $this->ok($data, 200, $this->pager($page, $limit, $res['total']));
    }

    public function duplicados(): ResponseInterface
    {
        $page  = $this->pageParam();
        $limit = $this->limitParam();
        $res   = (new DeduplicacionService())->colaDuplicados($page, $limit);

        return $this->ok($res['rows'], 200, $this->pager($page, $limit, $res['total']));
    }

    public function resolver(string $id): ResponseInterface
    {
        $rules = [
            'accion'             => 'required|in_list[fusionar,confirmar_nueva]',
            'id_persona_destino' => 'permit_empty|string|max_length[10]',
            'motivo'             => 'required|string|min_length[3]',
        ];
        if (! $this->validate($rules)) {
            return $this->err(422, 'Datos inválidos.', $this->validator?->getErrors() ?? []);
        }

        $d = $this->validator?->getValidated() ?? [];

        return $this->attempt(fn (): ResponseInterface => $this->ok(Shape::participacion((new DeduplicacionService())->resolverDuplicado((int) $id, $d))));
    }
}
