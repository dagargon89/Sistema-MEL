<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Exceptions\ApiException;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;

/** Base de los controladores de la API: envelope estándar del doc 05 §1.4. */
abstract class BaseApiController extends ResourceController
{
    /**
     * Ejecuta la lógica de negocio y traduce cualquier ApiException de los Services
     * al envelope de error (doc 05 §1.3/§1.4). Evita try/catch repetido en cada acción.
     *
     * @param callable(): ResponseInterface $fn
     */
    protected function attempt(callable $fn): ResponseInterface
    {
        try {
            return $fn();
        } catch (ApiException $e) {
            return $this->err($e->getStatusCode(), $e->getMessage(), $e->getErrors());
        }
    }
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

    /** `?page=N` saneado (mínimo 1). */
    protected function pageParam(): int
    {
        $v = $this->request->getGet('page');

        return max(1, is_numeric($v) ? (int) $v : 1);
    }

    /** `?limit=M` saneado (1..100, default 15) (doc 05 §1.6). */
    protected function limitParam(): int
    {
        $v = $this->request->getGet('limit');

        return min(100, max(1, is_numeric($v) ? (int) $v : 15));
    }

    /** Lee un parámetro de query como string no vacío (o null). */
    protected function queryStr(string $key): ?string
    {
        $v = $this->request->getGet($key);

        return is_string($v) && $v !== '' ? $v : null;
    }

    /** Lee un parámetro de query como entero (o null si no numérico). */
    protected function queryInt(string $key): ?int
    {
        $v = $this->request->getGet($key);

        return is_numeric($v) ? (int) $v : null;
    }

    /**
     * Arma el `pager` del doc 05 §1.6 a partir del total y la página actual.
     *
     * @return array<string, mixed>
     */
    protected function pager(int $page, int $limit, int $total): array
    {
        return [
            'currentPage' => $page,
            'perPage'     => $limit,
            'total'       => $total,
            'pageCount'   => $total > 0 ? (int) ceil($total / $limit) : 1,
        ];
    }
}
