<?php

declare(strict_types=1);

namespace App\Repositories;

use CodeIgniter\Database\BaseBuilder;

/** Lectura de actividades con herencia resuelta, acotada al ámbito (RF-CAT-011, ADR-004). */
class ActividadRepository extends BaseScopedRepository
{
    /**
     * Lista paginada con herencia + filtros, acotada al ámbito.
     *
     * @param 'ALL'|list<string>                                                  $ambito
     * @param array{tipo?:string|null,caso?:string|null,institucion?:string|null} $filtros
     *
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    public function listarPaginado(array|string $ambito, array $filtros, int $page, int $limit): array
    {
        $builder = $this->builder($ambito, $filtros);
        $total   = (int) $builder->countAllResults(false);

        $offset = ($page - 1) * $limit;
        $result = $builder->orderBy('a.id_actividad', 'ASC')->get($limit, $offset);

        /** @var list<array<string, mixed>> $rows */
        $rows = $result === false ? [] : $result->getResultArray();

        return ['rows' => $rows, 'total' => $total];
    }

    /** @return array<string, mixed>|null */
    public function find(string $id): ?array
    {
        $result = db_connect()->table('actividades')->where('id_actividad', $id)->get();
        if ($result === false) {
            return null;
        }

        /** @var array<string, mixed>|null $row */
        $row = $result->getRowArray();

        return $row;
    }

    /**
     * @param 'ALL'|list<string>                                                  $ambito
     * @param array{tipo?:string|null,caso?:string|null,institucion?:string|null} $filtros
     */
    private function builder(array|string $ambito, array $filtros): BaseBuilder
    {
        $builder = db_connect()->table('actividades a')
            ->select(
                'a.id_actividad, a.num_actividad, a.nombre, a.tipo_registro, a.caso_excepcional, '
                . 'a.id_eje, a.id_linea, a.id_componente, a.id_institucion, '
                . 'e.nombre AS eje, l.nombre AS linea, c.nombre AS componente, i.nombre AS institucion',
            )
            ->join('ejes e', 'e.id_eje = a.id_eje')
            ->join('lineas l', 'l.id_linea = a.id_linea')
            ->join('componentes c', 'c.id_componente = a.id_componente')
            ->join('instituciones i', 'i.id_institucion = a.id_institucion');

        if (! empty($filtros['tipo'])) {
            $builder->where('a.tipo_registro', $filtros['tipo']);
        }
        if (! empty($filtros['caso'])) {
            $builder->where('a.caso_excepcional', $filtros['caso']);
        }
        if (! empty($filtros['institucion'])) {
            $builder->where('a.id_institucion', $filtros['institucion']);
        }

        return $this->aplicarAmbito($builder, $ambito, 'a.id_institucion');
    }
}
