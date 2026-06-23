<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\UsuarioInstitucionModel;
use App\Models\UsuarioModel;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Shield\Entities\User;

/**
 * Autenticación de la SPA con access tokens de Shield (ADR-002, doc 05 §2).
 * Envelope del doc 05 §1.4. login es público (throttle); logout/me exigen token.
 */
class AuthController extends BaseApiController
{
    public function login(): ResponseInterface
    {
        if (! $this->validate(['email' => 'required|valid_email', 'password' => 'required|string'])) {
            return $this->err(422, 'Datos inválidos.', $this->validator?->getErrors() ?? []);
        }

        $email    = $this->request->getPost('email');
        $password = $this->request->getPost('password');
        if (! is_string($email) || ! is_string($password)) {
            return $this->err(422, 'Datos inválidos.');
        }

        // Autenticador 'session' explícito: valida credenciales SIN abrir sesión.
        // (auth() a secas resolvería a 'tokens' si algún filtro llamó auth('tokens') antes.)
        $result = auth('session')->getAuthenticator()->check(['email' => $email, 'password' => $password]);
        if (! $result->isOK()) {
            return $this->err(401, 'Credenciales inválidas.');
        }

        $user = $result->extraInfo();
        if (! $user instanceof User) {
            return $this->err(401, 'Credenciales inválidas.');
        }
        if (! $user->isActivated()) {
            return $this->err(403, 'Cuenta inactiva.');
        }

        $token = $user->generateAccessToken('spa');
        /** @var array<string, mixed> $tk */
        $tk       = $token->toArray();
        $rawToken = is_string($tk['raw_token'] ?? null) ? $tk['raw_token'] : '';

        return $this->ok([
            'token'  => $rawToken,
            'user'   => $this->perfil($user),
            'ambito' => $this->ambito($user),
        ]);
    }

    public function logout(): ResponseInterface
    {
        $user = auth('tokens')->user();
        if ($user instanceof User) {
            $user->revokeAllAccessTokens();
        }

        return $this->response->setStatusCode(204);
    }

    public function me(): ResponseInterface
    {
        $user = auth('tokens')->user();
        if (! $user instanceof User) {
            return $this->err(401, 'No autenticado.');
        }

        return $this->ok([
            'user'   => $this->perfil($user),
            'rol'    => $user->getGroups()[0] ?? null,
            'ambito' => $this->ambito($user),
        ]);
    }

    /** @return array{id:int,nombre:string,rol:string|null} */
    private function perfil(User $user): array
    {
        $dom    = (new UsuarioModel())->find($user->id);
        $nombre = (is_array($dom) && is_string($dom['nombre'] ?? null))
            ? $dom['nombre']
            : (string) ($user->username ?? '');

        return [
            'id'     => (int) $user->id,
            'nombre' => $nombre,
            'rol'    => $user->getGroups()[0] ?? null,
        ];
    }

    /** @return list<string> */
    private function ambito(User $user): array
    {
        return (new UsuarioInstitucionModel())->institucionesDe((int) $user->id);
    }
}
