<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

/**
 * Rate limiting (doc 05 §1.5): `throttle:login` 5/min/IP, `throttle:read` 120/min/usuario,
 * `throttle:write` 60/min/usuario. Usa el Throttler de CI4 (respaldo Redis en producción).
 */
class Throttle implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $tipo = is_string($arguments[0] ?? null) ? $arguments[0] : 'read';
        [$capacidad, $clave] = $this->limite($tipo, $request);

        $throttler = Services::throttler();
        if ($throttler->check($clave, $capacidad, MINUTE) === false) {
            return response()
                ->setStatusCode(ResponseInterface::HTTP_TOO_MANY_REQUESTS)
                ->setHeader('Retry-After', (string) $throttler->getTokenTime())
                ->setJSON(['success' => false, 'message' => 'Demasiadas solicitudes. Intenta más tarde.']);
        }

        return null;
    }

    /** @return array{0:int,1:string} */
    private function limite(string $tipo, RequestInterface $request): array
    {
        $ip = $request->getIPAddress();

        // login es público (sin token): clave por IP. No se invoca auth('tokens') aquí
        // para no alterar el autenticador compartido antes de AuthController::login.
        if ($tipo === 'login') {
            return [5, 'thr_login_' . $ip];
        }

        $uid = (string) (auth('tokens')->id() ?? $ip);

        return match ($tipo) {
            'write' => [60, 'thr_write_' . $uid],
            default => [120, 'thr_read_' . $uid],
        };
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): ?ResponseInterface
    {
        return null;
    }
}
