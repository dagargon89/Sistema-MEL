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
}
