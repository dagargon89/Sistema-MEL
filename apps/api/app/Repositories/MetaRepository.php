<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * Lectura de metas y datos de avance, acotada al ámbito (ADR-004). La institución se
 * hereda de la actividad por JOIN; el avance se calcula sobre `control_registro = OK`.
 */
class MetaRepository extends BaseScopedRepository
{
    /**
     * @param 'ALL'|list<string> $ambito
     *
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    public function listarPaginado(array|string $ambito, ?string $idActividad, int $page, int $limit): array
    {
        $builder = db_connect()->table('metas m')
            ->select('m.*')
            ->join('actividades a', 'a.id_actividad = m.id_actividad');
        if ($idActividad !== null) {
            $builder->where('m.id_actividad', $idActividad);
        }
        $builder = $this->aplicarAmbito($builder, $ambito, 'a.id_institucion')->orderBy('m.id_meta', 'ASC');

        $total  = (int) $builder->countAllResults(false);
        $result = $builder->get($limit, ($page - 1) * $limit);

        /** @var list<array<string, mixed>> $rows */
        $rows = $result === false ? [] : $result->getResultArray();

        return ['rows' => $rows, 'total' => $total];
    }

    /**
     * Metas con su actividad (tipo/caso/eje/institución) para el seguimiento, acotadas.
     *
     * @param 'ALL'|list<string>                          $ambito
     * @param array{eje?:string|null,institucion?:string|null} $filtros
     *
     * @return list<array<string, mixed>>
     */
    public function metasParaSeguimiento(array|string $ambito, array $filtros): array
    {
        $builder = db_connect()->table('metas m')
            ->select('m.id_meta, m.id_actividad, a.tipo_registro, a.caso_excepcional, a.id_eje, a.id_institucion')
            ->join('actividades a', 'a.id_actividad = m.id_actividad');
        if (! empty($filtros['eje'])) {
            $builder->where('a.id_eje', $filtros['eje']);
        }
        if (! empty($filtros['institucion'])) {
            $builder->where('a.id_institucion', $filtros['institucion']);
        }
        $builder = $this->aplicarAmbito($builder, $ambito, 'a.id_institucion')->orderBy('m.id_meta', 'ASC');

        $result = $builder->get();

        /** @var list<array<string, mixed>> $rows */
        $rows = $result === false ? [] : $result->getResultArray();

        return $rows;
    }

    /** @return list<array<string, mixed>> */
    public function mensualesDe(int $idMeta, ?string $periodo): array
    {
        $builder = db_connect()->table('metas_mensuales')->where('id_meta', $idMeta);
        if ($periodo !== null) {
            $builder->where('mes', $periodo);
        }
        $result = $builder->orderBy('mes', 'ASC')->get();

        /** @var list<array<string, mixed>> $rows */
        $rows = $result === false ? [] : $result->getResultArray();

        return $rows;
    }

    /**
     * Fechas de participación validadas (OK) de una actividad, vía la cadena.
     *
     * @return list<string>
     */
    public function fechasParticipacionOk(string $idActividad): array
    {
        $result = db_connect()->table('participaciones par')
            ->select('par.fecha_participacion')
            ->join('ejecuciones e', 'e.id_ejecucion = par.id_ejecucion')
            ->join('eventos_programados ev', 'ev.id_evento_programado = e.id_evento_programado')
            ->where('ev.id_actividad', $idActividad)
            ->where('par.control_registro', 'OK')
            ->where('par.fecha_participacion is not null', null, false)
            ->get();

        /** @var list<array<string, mixed>> $rows */
        $rows = $result === false ? [] : $result->getResultArray();

        return array_values(array_filter(array_map(
            static fn (array $r): string => is_string($r['fecha_participacion'] ?? null) ? $r['fecha_participacion'] : '',
            $rows,
        ), static fn (string $f): bool => $f !== ''));
    }

    /**
     * Conteos agregados de una actividad por periodo de corte, vía la cadena.
     *
     * @return list<array{periodo_corte:string|null, cantidad:int}>
     */
    public function agregadasDe(string $idActividad): array
    {
        $result = db_connect()->table('participaciones_agregadas a')
            ->select('a.periodo_corte, a.cantidad_participantes')
            ->join('ejecuciones e', 'e.id_ejecucion = a.id_ejecucion')
            ->join('eventos_programados ev', 'ev.id_evento_programado = e.id_evento_programado')
            ->where('ev.id_actividad', $idActividad)
            ->get();

        /** @var list<array<string, mixed>> $rows */
        $rows = $result === false ? [] : $result->getResultArray();

        return array_map(static fn (array $r): array => [
            'periodo_corte' => is_string($r['periodo_corte'] ?? null) ? $r['periodo_corte'] : null,
            'cantidad'      => is_numeric($r['cantidad_participantes'] ?? null) ? (int) $r['cantidad_participantes'] : 0,
        ], $rows);
    }
}
