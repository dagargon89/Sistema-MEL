<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * RBAC por grupo de Shield. Uso: `rbac:coordinacion` o `rbac:capturista,coordinacion`.
 * 403 JSON si el usuario (autenticado por token) no pertenece a ninguno de los grupos.
 * (No usamos el filtro `group` de Shield porque redirige; la API responde JSON.)
 */
class Rbac implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $user = auth('tokens')->user();
        if ($user === null) {
            return response()
                ->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED)
                ->setJSON(['success' => false, 'message' => 'No autenticado.']);
        }

        $permitidos = array_map(static fn ($g): string => (string) $g, $arguments ?? []);
        if ($permitidos !== [] && ! $user->inGroup(...$permitidos)) {
            return response()
                ->setStatusCode(ResponseInterface::HTTP_FORBIDDEN)
                ->setJSON(['success' => false, 'message' => 'No tiene permiso para esta acción.']);
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): ?ResponseInterface
    {
        return null;
    }
}
