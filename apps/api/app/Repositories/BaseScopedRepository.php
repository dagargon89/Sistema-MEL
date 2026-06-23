<?php

declare(strict_types=1);

namespace App\Repositories;

use CodeIgniter\Database\BaseBuilder;

/**
 * Base de los Repositories con filtrado por institución (ADR-004). El filtro vive
 * aquí (capa única), no en los controladores, para que sea estructural y testeable.
 */
abstract class BaseScopedRepository
{
    /**
     * Aplica el ámbito a una consulta:
     *  - 'ALL'  ⇒ sin filtro (rol global con permiso `data.viewAll`);
     *  - []     ⇒ denegación por defecto (no devuelve filas);
     *  - lista  ⇒ WHERE columna IN (instituciones).
     *
     * @param 'ALL'|list<string> $ambito
     */
    protected function aplicarAmbito(BaseBuilder $builder, array|string $ambito, string $columna): BaseBuilder
    {
        if ($ambito === 'ALL') {
            return $builder;
        }

        if ($ambito === []) {
            // Centinela imposible: garantiza cero resultados sin SQL crudo.
            return $builder->whereIn($columna, ['__DENY__']);
        }

        return $builder->whereIn($columna, $ambito);
    }

    /**
     * Aplica ámbito + orden y devuelve una página (cuenta total sin paginar). Reutilizable
     * por los Repositories de listados acotados (doc 05 §1.6).
     *
     * @param 'ALL'|list<string> $ambito
     *
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    protected function paginarScoped(BaseBuilder $builder, array|string $ambito, string $columna, string $orderBy, int $page, int $limit): array
    {
        $builder = $this->aplicarAmbito($builder, $ambito, $columna)->orderBy($orderBy, 'ASC');
        $total   = (int) $builder->countAllResults(false);
        $result  = $builder->get($limit, ($page - 1) * $limit);

        /** @var list<array<string, mixed>> $rows */
        $rows = $result === false ? [] : $result->getResultArray();

        return ['rows' => $rows, 'total' => $total];
    }
}
