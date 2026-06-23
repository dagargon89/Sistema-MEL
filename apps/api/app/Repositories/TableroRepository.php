<?php

declare(strict_types=1);

namespace App\Repositories;

/**
 * KPIs de tablero como consultas en vivo sobre `control_registro = OK` (doc 05 §12,
 * RF-TAB-120). Acotado al ámbito (ADR-004); la institución se hereda de la actividad.
 * Cuenta registros reales: nunca filas-plantilla (espejo del tablero del mock).
 */
class TableroRepository extends BaseScopedRepository
{
    /**
     * Beneficiarios únicos: personas OK referidas por participaciones OK en el ámbito.
     *
     * @param 'ALL'|list<string> $ambito
     */
    public function beneficiariosUnicos(array|string $ambito): int
    {
        $builder = db_connect()->table('participaciones par')
            ->select('par.id_persona')
            ->join('personas p', 'p.id_persona = par.id_persona')
            ->join('ejecuciones e', 'e.id_ejecucion = par.id_ejecucion')
            ->join('eventos_programados ev', 'ev.id_evento_programado = e.id_evento_programado')
            ->join('actividades a', 'a.id_actividad = ev.id_actividad')
            ->where('par.control_registro', 'OK')
            ->where('p.control_registro', 'OK')
            ->groupBy('par.id_persona');

        $result = $this->aplicarAmbito($builder, $ambito, 'a.id_institucion')->get();

        /** @var list<array<string, mixed>> $rows */
        $rows = $result === false ? [] : $result->getResultArray();

        return count($rows);
    }

    /**
     * Participaciones nominales validadas (OK) en el ámbito.
     *
     * @param 'ALL'|list<string> $ambito
     */
    public function nominales(array|string $ambito): int
    {
        $builder = db_connect()->table('participaciones par')
            ->join('ejecuciones e', 'e.id_ejecucion = par.id_ejecucion')
            ->join('eventos_programados ev', 'ev.id_evento_programado = e.id_evento_programado')
            ->join('actividades a', 'a.id_actividad = ev.id_actividad')
            ->where('par.control_registro', 'OK');

        return (int) $this->aplicarAmbito($builder, $ambito, 'a.id_institucion')->countAllResults();
    }

    /**
     * Suma de participantes agregados en el ámbito.
     *
     * @param 'ALL'|list<string> $ambito
     */
    public function agregadasSuma(array|string $ambito): int
    {
        $builder = db_connect()->table('participaciones_agregadas ag')
            ->select('COALESCE(SUM(ag.cantidad_participantes), 0) AS suma')
            ->join('ejecuciones e', 'e.id_ejecucion = ag.id_ejecucion')
            ->join('eventos_programados ev', 'ev.id_evento_programado = e.id_evento_programado')
            ->join('actividades a', 'a.id_actividad = ev.id_actividad');
        $row = $this->aplicarAmbito($builder, $ambito, 'a.id_institucion')->get();
        $r   = $row === false ? null : $row->getRowArray();

        return is_array($r) && is_numeric($r['suma'] ?? null) ? (int) $r['suma'] : 0;
    }

    /**
     * Eventos programados en el ámbito (universo planeado).
     *
     * @param 'ALL'|list<string> $ambito
     */
    public function eventosProgramados(array|string $ambito): int
    {
        $builder = db_connect()->table('eventos_programados ev')
            ->join('actividades a', 'a.id_actividad = ev.id_actividad');

        return (int) $this->aplicarAmbito($builder, $ambito, 'a.id_institucion')->countAllResults();
    }

    /**
     * Ejecuciones reales (con fecha) en el ámbito.
     *
     * @param 'ALL'|list<string> $ambito
     */
    public function ejecucionesConFecha(array|string $ambito): int
    {
        $builder = db_connect()->table('ejecuciones e')
            ->join('eventos_programados ev', 'ev.id_evento_programado = e.id_evento_programado')
            ->join('actividades a', 'a.id_actividad = ev.id_actividad')
            ->where('e.fecha_ejecucion_real is not null', null, false);

        return (int) $this->aplicarAmbito($builder, $ambito, 'a.id_institucion')->countAllResults();
    }

    /**
     * Conteo de actividades por tipo (P/E/R) en el ámbito, para el reporte FECHAC.
     *
     * @param 'ALL'|list<string> $ambito
     *
     * @return array{P:int, E:int, R:int, total:int}
     */
    public function actividadesPorTipo(array|string $ambito): array
    {
        $builder = db_connect()->table('actividades')->select('tipo_registro, COUNT(*) AS n')->groupBy('tipo_registro');
        $result  = $this->aplicarAmbito($builder, $ambito, 'id_institucion')->get();

        /** @var list<array<string, mixed>> $rows */
        $rows = $result === false ? [] : $result->getResultArray();

        $por = ['P' => 0, 'E' => 0, 'R' => 0, 'total' => 0];
        foreach ($rows as $r) {
            $tipo = is_string($r['tipo_registro'] ?? null) ? $r['tipo_registro'] : '';
            $n    = is_numeric($r['n'] ?? null) ? (int) $r['n'] : 0;
            if ($tipo === 'P' || $tipo === 'E' || $tipo === 'R') {
                $por[$tipo] = $n;
                $por['total'] += $n;
            }
        }

        return $por;
    }

    /**
     * Resultados (tipo R) reportados en el ámbito.
     *
     * @param 'ALL'|list<string> $ambito
     */
    public function resultadosReportados(array|string $ambito): int
    {
        $builder = db_connect()->table('resultados r')
            ->join('actividades a', 'a.id_actividad = r.id_actividad');

        return (int) $this->aplicarAmbito($builder, $ambito, 'a.id_institucion')->countAllResults();
    }
}
