<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Services\TableroService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Tableros con KPIs reales sobre `control_registro = OK` (doc 05 §12). Acotado al ámbito.
 * Los tipos comparten el set de KPIs ejecutivos (espejo del mock); el detalle por tablero
 * se amplía en fases posteriores sin romper el contrato.
 */
class TableroController extends BaseApiController
{
    private const TIPOS = ['operativo', 'coordinacion', 'ejecutivo', 'analitico', 'shelter'];

    public function ver(string $tipo): ResponseInterface
    {
        if (! in_array($tipo, self::TIPOS, true)) {
            return $this->err(404, 'Tablero desconocido.');
        }

        return $this->ok((new TableroService())->tablero());
    }
}
