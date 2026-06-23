<?php

declare(strict_types=1);

namespace App\Repositories;

use CodeIgniter\Database\BaseBuilder;

/**
 * Lectura de la cadena MEL (procesos → eventos → ejecuciones → participaciones)
 * acotada al ámbito de institución (ADR-004). La institución se hereda de la
 * actividad por JOIN (doc 03 §2); el filtro vive aquí, no en los controladores.
 */
class CadenaRepository extends BaseScopedRepository
{
    /** Institución heredada de una actividad (base del ámbito). */
    public function institucionDeActividad(string $idActividad): ?string
    {
        $row = db_connect()->table('actividades')
            ->select('id_institucion')
            ->where('id_actividad', $idActividad)
            ->get(1);

        $r = $row === false ? null : $row->getRowArray();

        return is_array($r) && is_string($r['id_institucion'] ?? null) ? $r['id_institucion'] : null;
    }

    /** Institución heredada de un evento programado vía su actividad. */
    public function institucionDeEvento(int $idEvento): ?string
    {
        $row = db_connect()->table('eventos_programados e')
            ->select('a.id_institucion')
            ->join('actividades a', 'a.id_actividad = e.id_actividad')
            ->where('e.id_evento_programado', $idEvento)
            ->get(1);

        $r = $row === false ? null : $row->getRowArray();

        return is_array($r) && is_string($r['id_institucion'] ?? null) ? $r['id_institucion'] : null;
    }

    /** Institución heredada de una ejecución vía evento → actividad. */
    public function institucionDeEjecucion(int $idEjecucion): ?string
    {
        $row = db_connect()->table('ejecuciones ej')
            ->select('a.id_institucion')
            ->join('eventos_programados e', 'e.id_evento_programado = ej.id_evento_programado')
            ->join('actividades a', 'a.id_actividad = e.id_actividad')
            ->where('ej.id_ejecucion', $idEjecucion)
            ->get(1);

        $r = $row === false ? null : $row->getRowArray();

        return is_array($r) && is_string($r['id_institucion'] ?? null) ? $r['id_institucion'] : null;
    }

    /**
     * @param 'ALL'|list<string> $ambito
     *
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    public function listarProcesos(array|string $ambito, ?string $idActividad, int $page, int $limit): array
    {
        $builder = db_connect()->table('procesos p')
            ->select('p.*')
            ->join('actividades a', 'a.id_actividad = p.id_actividad');
        if ($idActividad !== null) {
            $builder->where('p.id_actividad', $idActividad);
        }
        $builder = $this->aplicarAmbito($builder, $ambito, 'a.id_institucion')->orderBy('p.id_proceso', 'ASC');

        return $this->paginar($builder, $page, $limit);
    }

    /**
     * @param 'ALL'|list<string>                                                                          $ambito
     * @param array{id_actividad?:string|null,institucion?:string|null,responsable?:string|null,estatus?:string|null} $filtros
     *
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    public function listarEventos(array|string $ambito, array $filtros, int $page, int $limit): array
    {
        $builder = db_connect()->table('eventos_programados e')
            ->select('e.*')
            ->join('actividades a', 'a.id_actividad = e.id_actividad');
        if (! empty($filtros['id_actividad'])) {
            $builder->where('e.id_actividad', $filtros['id_actividad']);
        }
        if (! empty($filtros['institucion'])) {
            $builder->where('a.id_institucion', $filtros['institucion']);
        }
        if (! empty($filtros['responsable'])) {
            $builder->like('e.responsable', (string) $filtros['responsable']);
        }
        if (! empty($filtros['estatus'])) {
            $builder->where('e.estatus', $filtros['estatus']);
        }
        $builder = $this->aplicarAmbito($builder, $ambito, 'a.id_institucion')->orderBy('e.id_evento_programado', 'ASC');

        return $this->paginar($builder, $page, $limit);
    }

    /**
     * @param 'ALL'|list<string>                                              $ambito
     * @param array{id_evento_programado?:int|null,control?:string|null}      $filtros
     *
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    public function listarEjecuciones(array|string $ambito, array $filtros, int $page, int $limit): array
    {
        $builder = db_connect()->table('ejecuciones ej')
            ->select('ej.*')
            ->join('eventos_programados e', 'e.id_evento_programado = ej.id_evento_programado')
            ->join('actividades a', 'a.id_actividad = e.id_actividad');
        if (! empty($filtros['id_evento_programado'])) {
            $builder->where('ej.id_evento_programado', $filtros['id_evento_programado']);
        }
        if (! empty($filtros['control'])) {
            $builder->where('ej.control_registro', $filtros['control']);
        }
        $builder = $this->aplicarAmbito($builder, $ambito, 'a.id_institucion')->orderBy('ej.id_ejecucion', 'ASC');

        return $this->paginar($builder, $page, $limit);
    }

    /**
     * Participaciones de una ejecución. El control de ámbito ya se hizo sobre la
     * ejecución en el controlador, por eso aquí solo se filtra por `id_ejecucion`.
     *
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    public function listarParticipacionesDeEjecucion(int $idEjecucion, int $page, int $limit): array
    {
        $builder = db_connect()->table('participaciones')
            ->where('id_ejecucion', $idEjecucion)
            ->orderBy('id_participacion', 'ASC');

        return $this->paginar($builder, $page, $limit);
    }

    /** @return array<string, mixed>|null */
    public function findEjecucion(int $id): ?array
    {
        $result = db_connect()->table('ejecuciones')->where('id_ejecucion', $id)->get(1);
        if ($result === false) {
            return null;
        }

        /** @var array<string, mixed>|null $row */
        $row = $result->getRowArray();

        return $row;
    }

    /**
     * Cuenta + página un builder ya filtrado/ordenado, como ActividadRepository.
     *
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    private function paginar(BaseBuilder $builder, int $page, int $limit): array
    {
        $total  = (int) $builder->countAllResults(false);
        $offset = ($page - 1) * $limit;
        $result = $builder->get($limit, $offset);

        /** @var list<array<string, mixed>> $rows */
        $rows = $result === false ? [] : $result->getResultArray();

        return ['rows' => $rows, 'total' => $total];
    }
}
