<?php

declare(strict_types=1);

namespace App\Filters;

use App\Models\UsuarioInstitucionModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

/**
 * Carga el ámbito (instituciones permitidas) del usuario autenticado en el servicio
 * CurrentScope, tras el filtro `tokens`. El filtrado por institución lo aplican los
 * Repositories usando este ámbito (ADR-004). Denegación por defecto.
 */
class ScopeInstitucion implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $user = auth('tokens')->user();
        if ($user === null) {
            return response()
                ->setStatusCode(ResponseInterface::HTTP_UNAUTHORIZED)
                ->setJSON(['success' => false, 'message' => 'No autenticado.']);
        }

        $rol           = $user->getGroups()[0] ?? null;
        $global        = $user->can('data.viewAll');
        $instituciones = (new UsuarioInstitucionModel())->institucionesDe((int) $user->id);

        Services::currentScope()->set((int) $user->id, $rol, $instituciones, $global);

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): ?ResponseInterface
    {
        return null;
    }
}
