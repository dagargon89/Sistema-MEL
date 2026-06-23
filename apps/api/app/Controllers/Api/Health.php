<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

/**
 * Sonda de salud de la API (no toca base de datos).
 * Permite verificar que la API responde en staging/local (hito Sprint 0).
 */
class Health extends ResourceController
{
    public function index(): ResponseInterface
    {
        return $this->respond([
            'status'  => 'ok',
            'service' => 'sistema-mel-api',
            'version' => 'v1',
            'time'    => date('c'),
        ]);
    }
}
