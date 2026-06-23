<?php

declare(strict_types=1);

namespace App\Repositories;

/** Lectura de actividades con herencia resuelta, acotada al ámbito (RF-CAT-011, ADR-004). */
class ActividadRepository extends BaseScopedRepository
{
    /**
     * @param 'ALL'|list<string> $ambito
     *
     * @return list<array<string, mixed>>
     */
    public function listar(array|string $ambito): array
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
            ->join('instituciones i', 'i.id_institucion = a.id_institucion')
            ->orderBy('a.id_actividad', 'ASC');

        $this->aplicarAmbito($builder, $ambito, 'a.id_institucion');

        $result = $builder->get();
        if ($result === false) {
            return [];
        }

        /** @var list<array<string, mixed>> $rows */
        $rows = $result->getResultArray();

        return $rows;
    }
}
