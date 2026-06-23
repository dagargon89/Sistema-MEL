<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Services\SolicitudService;
use App\Support\Shape;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Gobernanza: solicitudes (doc 05 §11). Cualquier usuario registra; solo
 * coordinación resuelve (RBAC en la ruta).
 */
class SolicitudController extends BaseApiController
{
    public function index(): ResponseInterface
    {
        $page  = $this->pageParam();
        $limit = $this->limitParam();
        $res   = (new SolicitudService())->listar($this->queryStr('estado'), $page, $limit);

        return $this->ok(array_map(Shape::solicitud(...), $res['rows']), 200, $this->pager($page, $limit, $res['total']));
    }

    public function create(): ResponseInterface
    {
        $rules = [
            'descripcion'      => 'required|string',
            'tipo_solicitud'   => 'required|in_list[correccion,mejora,ajuste]',
            'entidad_afectada' => 'permit_empty|string|max_length[60]',
            'nivel_criticidad' => 'permit_empty|in_list[BAJA,MEDIA,ALTA]',
            'impacto'          => 'permit_empty|string|max_length[250]',
        ];
        if (! $this->validate($rules)) {
            return $this->err(422, 'Datos inválidos.', $this->validator?->getErrors() ?? []);
        }
        $d = $this->validator?->getValidated() ?? [];

        return $this->attempt(fn (): ResponseInterface => $this->ok(Shape::solicitud((new SolicitudService())->crearSolicitud($d)), 201));
    }

    public function resolver(string $id): ResponseInterface
    {
        $rules = [
            'estado'               => 'required|in_list[en_revision,en_proceso,resuelta,descartada]',
            'responsable_atencion' => 'permit_empty|is_natural',
            'comentarios'          => 'permit_empty|string',
        ];
        if (! $this->validate($rules)) {
            return $this->err(422, 'Datos inválidos.', $this->validator?->getErrors() ?? []);
        }
        $d = $this->validator?->getValidated() ?? [];

        return $this->attempt(fn (): ResponseInterface => $this->ok(Shape::solicitud((new SolicitudService())->resolverSolicitud((int) $id, $d))));
    }
}
