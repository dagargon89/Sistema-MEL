<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Lectura de incidencia acotada al ámbito (ADR-004). La institución se hereda de la
 * actividad; en compromisos/hitos, vía el proceso de incidencia → actividad.
 */
class IncidenciaRepository extends BaseScopedRepository
{
    /**
     * @param 'ALL'|list<string> $ambito
     *
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    public function listarPropuestas(array|string $ambito, int $page, int $limit): array
    {
        $builder = db_connect()->table('propuestas_incidencia p')->select('p.*')
            ->join('actividades a', 'a.id_actividad = p.id_actividad');

        return $this->paginarScoped($builder, $ambito, 'a.id_institucion', 'p.id_propuesta', $page, $limit);
    }

    /**
     * @param 'ALL'|list<string> $ambito
     *
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    public function listarProcesos(array|string $ambito, int $page, int $limit): array
    {
        $builder = db_connect()->table('procesos_incidencia pi')->select('pi.*')
            ->join('actividades a', 'a.id_actividad = pi.id_actividad');

        return $this->paginarScoped($builder, $ambito, 'a.id_institucion', 'pi.id_proceso_incidencia', $page, $limit);
    }

    /**
     * @param 'ALL'|list<string> $ambito
     *
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    public function listarCompromisos(array|string $ambito, int $page, int $limit): array
    {
        $builder = db_connect()->table('compromisos c')->select('c.*')
            ->join('procesos_incidencia pi', 'pi.id_proceso_incidencia = c.id_proceso_incidencia')
            ->join('actividades a', 'a.id_actividad = pi.id_actividad');

        return $this->paginarScoped($builder, $ambito, 'a.id_institucion', 'c.id_compromiso', $page, $limit);
    }

    /**
     * @param 'ALL'|list<string> $ambito
     *
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    public function listarAlianzas(array|string $ambito, int $page, int $limit): array
    {
        $builder = db_connect()->table('alianzas al')->select('al.*')
            ->join('actividades a', 'a.id_actividad = al.id_actividad');

        return $this->paginarScoped($builder, $ambito, 'a.id_institucion', 'al.id_alianza', $page, $limit);
    }

    /**
     * @param 'ALL'|list<string> $ambito
     *
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    public function listarHitos(array|string $ambito, int $page, int $limit): array
    {
        $builder = db_connect()->table('hitos_incidencia h')->select('h.*')
            ->join('procesos_incidencia pi', 'pi.id_proceso_incidencia = h.id_proceso_incidencia')
            ->join('actividades a', 'a.id_actividad = pi.id_actividad');

        return $this->paginarScoped($builder, $ambito, 'a.id_institucion', 'h.id_hito', $page, $limit);
    }

    /** Institución heredada de un proceso de incidencia (para acotar compromisos/hitos). */
    public function institucionDeProcesoIncidencia(int $idProceso): ?string
    {
        $row = db_connect()->table('procesos_incidencia pi')
            ->select('a.id_institucion')
            ->join('actividades a', 'a.id_actividad = pi.id_actividad')
            ->where('pi.id_proceso_incidencia', $idProceso)
            ->get(1);
        $r = $row === false ? null : $row->getRowArray();

        return is_array($r) && is_string($r['id_institucion'] ?? null) ? $r['id_institucion'] : null;
    }
}
