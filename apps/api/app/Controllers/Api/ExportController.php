<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Services\ReporteService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Exportación FECHAC (doc 05 §12, RF-TAB-122). Agrega los KPIs reales del ámbito.
 * Solo coordinación/dirección (RBAC en la ruta).
 */
class ExportController extends BaseApiController
{
    public function fechac(): ResponseInterface
    {
        return $this->ok((new ReporteService())->fechac($this->queryStr('periodo')));
    }
}
