<?php

declare(strict_types=1);

namespace App\Repositories;

/** Lectura de verticales (shelter / sostenibilidad) acotada al ámbito (ADR-004). */
class VerticalRepository extends BaseScopedRepository
{
    /**
     * @param 'ALL'|list<string> $ambito
     *
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    public function listarOcupacion(array|string $ambito, int $page, int $limit): array
    {
        $builder = db_connect()->table('ocupacion_shelter o')->select('o.*')
            ->join('actividades a', 'a.id_actividad = o.id_actividad');

        return $this->paginarScoped($builder, $ambito, 'a.id_institucion', 'o.id_ocupacion', $page, $limit);
    }

    /**
     * @param 'ALL'|list<string> $ambito
     *
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    public function listarSostenibilidad(array|string $ambito, int $page, int $limit): array
    {
        $builder = db_connect()->table('sostenibilidad_financiera s')->select('s.*')
            ->join('actividades a', 'a.id_actividad = s.id_actividad');

        return $this->paginarScoped($builder, $ambito, 'a.id_institucion', 's.id_registro', $page, $limit);
    }
}
