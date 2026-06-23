<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Services\AuditoriaService;
use App\Support\Shape;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Historial de auditoría (doc 05 §11, RF-GOB-112). Solo coordinación/admin/dirección
 * (RBAC en la ruta). No existe endpoint de borrado (append-only, RNF-021).
 */
class AuditoriaController extends BaseApiController
{
    public function index(): ResponseInterface
    {
        $page  = $this->pageParam();
        $limit = $this->limitParam();
        $res   = (new AuditoriaService())->listar($this->queryStr('entidad'), $page, $limit);

        return $this->ok(array_map(Shape::auditoria(...), $res['rows']), 200, $this->pager($page, $limit, $res['total']));
    }
}
