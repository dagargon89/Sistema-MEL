<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Ámbito de la petición autenticada. Lo puebla el filtro `scope-institucion`
 * (tras `tokens`) y lo consumen controladores y Repositories para filtrar por
 * institución (ADR-004). Singleton por petición.
 */
final class CurrentScope
{
    private ?int $userId = null;
    private ?string $rol = null;

    /** @var list<string> */
    private array $instituciones = [];

    private bool $global = false;

    /** @param list<string> $instituciones */
    public function set(int $userId, ?string $rol, array $instituciones, bool $global): void
    {
        $this->userId        = $userId;
        $this->rol           = $rol;
        $this->instituciones = $instituciones;
        $this->global        = $global;
    }

    public function userId(): ?int
    {
        return $this->userId;
    }

    public function rol(): ?string
    {
        return $this->rol;
    }

    /** @return list<string> */
    public function instituciones(): array
    {
        return $this->instituciones;
    }

    public function isGlobal(): bool
    {
        return $this->global;
    }

    /**
     * ¿El ámbito de la petición cubre esta institución? Los roles globales
     * (`data.viewAll`) cubren cualquiera; los demás, solo las suyas (ADR-004).
     */
    public function cubre(?string $institucion): bool
    {
        if ($this->global) {
            return true;
        }

        return $institucion !== null && in_array($institucion, $this->instituciones, true);
    }

    /**
     * Ámbito para los Repositories: 'ALL' si el rol es global (omite el filtro),
     * o la lista de instituciones permitidas (vacía ⇒ denegación por defecto).
     *
     * @return 'ALL'|list<string>
     */
    public function ambitoRepositorio(): array|string
    {
        return $this->global ? 'ALL' : $this->instituciones;
    }
}
