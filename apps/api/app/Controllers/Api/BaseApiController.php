<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

/** Base de los controladores de la API: envelope estándar del doc 05 §1.4. */
abstract class BaseApiController extends ResourceController
{
    /**
     * Respuesta de éxito: { success: true, data, pager? }.
     *
     * @param mixed                     $data
     * @param array<string, mixed>|null $pager
     */
    protected function ok($data = null, int $status = 200, ?array $pager = null): ResponseInterface
    {
        $body = ['success' => true, 'data' => $data];
        if ($pager !== null) {
            $body['pager'] = $pager;
        }

        return $this->response->setStatusCode($status)->setJSON($body);
    }

    /**
     * Respuesta de error: { success: false, message, errors? }.
     *
     * @param array<string, string> $errors
     */
    protected function err(int $status, string $message, array $errors = []): ResponseInterface
    {
        $body = ['success' => false, 'message' => $message];
        if ($errors !== []) {
            $body['errors'] = $errors;
        }

        return $this->response->setStatusCode($status)->setJSON($body);
    }

    /**
     * Lee un campo string de un arreglo de datos validados (null si ausente/vacío).
     *
     * @param array<string, mixed> $src
     */
    protected function campoStr(array $src, string $key): ?string
    {
        $v = $src[$key] ?? null;
        if (is_string($v)) {
            return $v === '' ? null : $v;
        }

        return is_int($v) || is_float($v) ? (string) $v : null;
    }

    /**
     * Lee un campo entero de un arreglo de datos validados (null si no numérico).
     *
     * @param array<string, mixed> $src
     */
    protected function campoInt(array $src, string $key): ?int
    {
        $v = $src[$key] ?? null;

        return is_numeric($v) ? (int) $v : null;
    }
}
