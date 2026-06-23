<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\MetaModel;
use App\Repositories\MetaRepository;
use App\Support\FieldCast;
use Config\Services;

/**
 * Metas POA y seguimiento con semáforo (doc 05 §7, RF-META-070..072). El avance se
 * calcula en vivo sobre `control_registro = OK` (espejo de `vw_seguimiento_metas` y del
 * mock): nominales por mes de `fecha_participacion` + agregadas por `periodo_corte`.
 */
class MetaService
{
    use FieldCast;

    private const MESES = ['M01', 'M02', 'M03', 'M04', 'M05', 'M06', 'M07', 'M08', 'M09', 'M10', 'M11', 'M12', 'M13', 'M14', 'M15', 'M16', 'M17', 'M18'];

    private MetaRepository $repo;
    private AuditoriaService $auditoria;

    public function __construct()
    {
        $this->repo      = new MetaRepository();
        $this->auditoria = new AuditoriaService();
    }

    /**
     * Crea la meta anual + sus mensuales (transaccional). Solo coordinación (RBAC en ruta).
     *
     * @param array<string, mixed>      $d
     * @param array<int|string, mixed>  $mensuales
     *
     * @return array<string, mixed>
     */
    public function crearMeta(array $d, array $mensuales): array
    {
        $idActividad = $this->str($d, 'id_actividad') ?? '';
        if (! $this->actividadExiste($idActividad)) {
            throw ApiException::unprocessable('Datos inválidos.', ['id_actividad' => 'Actividad inexistente.']);
        }
        if ($this->metaExiste($idActividad)) {
            throw ApiException::unprocessable('Datos inválidos.', ['id_actividad' => 'La actividad ya tiene una meta.']);
        }

        $limpios = [];
        foreach ($mensuales as $mm) {
            if (! is_array($mm)) {
                continue;
            }
            $mes   = is_string($mm['mes'] ?? null) ? $mm['mes'] : '';
            $valor = is_numeric($mm['valor'] ?? null) ? (float) $mm['valor'] : null;
            if (! in_array($mes, self::MESES, true) || $valor === null || $valor < 0) {
                throw ApiException::unprocessable('Datos inválidos.', ['mensuales' => 'Cada mensual requiere mes M01–M18 y valor ≥ 0.']);
            }
            $limpios[] = ['mes' => $mes, 'valor' => $valor];
        }

        $row = [
            'id_actividad'      => $idActividad,
            'unidad_meta'       => $this->str($d, 'unidad_meta'),
            'unidad_especifica' => $this->str($d, 'unidad_especifica'),
            'meta_anual_total'  => $this->str($d, 'meta_anual_total'),
            'observaciones'     => $this->str($d, 'observaciones'),
        ];

        $db = db_connect();
        $db->transStart();
        $model = new MetaModel();
        $model->insert($row);
        $idMeta = $model->getInsertID();
        foreach ($limpios as $mm) {
            $db->table('metas_mensuales')->insert(['id_meta' => $idMeta, 'mes' => $mm['mes'], 'valor' => $mm['valor']]);
        }
        $this->auditoria->registrar('metas', (string) $idMeta, 'alta', null, $row);
        $db->transComplete();

        /** @var array<string, mixed>|null $created */
        $created = $model->find($idMeta);
        if (is_array($created)) {
            return $created;
        }
        $row['id_meta'] = $idMeta;

        return $row;
    }

    /**
     * Seguimiento con semáforo, acotado al ámbito (doc 05 §7).
     *
     * @param array{periodo?:string|null, institucion?:string|null, eje?:string|null} $filtros
     *
     * @return list<array<string, mixed>>
     */
    public function seguimiento(array $filtros): array
    {
        $ambito  = Services::currentScope()->ambitoRepositorio();
        $periodo = $filtros['periodo'] ?? null;
        $out     = [];

        foreach ($this->repo->metasParaSeguimiento($ambito, $filtros) as $m) {
            $idActividad = is_string($m['id_actividad'] ?? null) ? $m['id_actividad'] : '';
            $tipo        = is_string($m['tipo_registro'] ?? null) ? $m['tipo_registro'] : '';
            $caso        = is_string($m['caso_excepcional'] ?? null) ? $m['caso_excepcional'] : null;
            $idMeta      = is_numeric($m['id_meta'] ?? null) ? (int) $m['id_meta'] : 0;

            $fechas    = $this->repo->fechasParticipacionOk($idActividad);
            $agregadas = $this->repo->agregadasDe($idActividad);

            foreach ($this->repo->mensualesDe($idMeta, $periodo) as $mm) {
                $mes     = is_string($mm['mes'] ?? null) ? $mm['mes'] : '';
                $metaMes = is_numeric($mm['valor'] ?? null) ? (float) $mm['valor'] : 0.0;
                $avance  = $this->avanceMes($fechas, $agregadas, $mes);

                $out[] = [
                    'id_actividad'     => $idActividad,
                    'tipo_registro'    => $tipo,
                    'caso_excepcional' => $caso,
                    'mes'              => $mes,
                    'meta_mes'         => $metaMes,
                    'avance_mes'       => $avance,
                    'porcentaje'       => $metaMes === 0.0 ? null : round($avance / $metaMes * 1000) / 10,
                    'semaforo'         => $this->semaforo($tipo, $caso, $metaMes, $avance),
                ];
            }
        }

        return $out;
    }

    /**
     * Avance real de un mes POA: nominales (OK) cuyo mes de participación coincide +
     * agregadas con ese `periodo_corte` (espejo de avanceRealActividadMes del mock).
     *
     * @param list<string>                                          $fechas
     * @param list<array{periodo_corte:string|null, cantidad:int}>  $agregadas
     */
    private function avanceMes(array $fechas, array $agregadas, string $mes): int
    {
        $mesNum  = (int) substr($mes, 1);
        $nominal = 0;
        foreach ($fechas as $f) {
            $ts = strtotime($f);
            if ($ts !== false && (int) date('n', $ts) === $mesNum) {
                $nominal++;
            }
        }
        $agg = 0;
        foreach ($agregadas as $a) {
            if ($a['periodo_corte'] === $mes) {
                $agg += $a['cantidad'];
            }
        }

        return $nominal + $agg;
    }

    /** Semáforo (espejo del CASE de vw_seguimiento_metas y de calcularSemaforo del mock). */
    private function semaforo(string $tipo, ?string $caso, float $metaMes, int $avance): string
    {
        if ($tipo === 'R') {
            return 'FASE_3';
        }
        if ($metaMes === 0.0) {
            return 'SIN_META';
        }
        if (($caso === 'C' || $caso === 'D') && $avance === 0) {
            return 'CORTE_AL_CIERRE';
        }
        $ratio = $avance / $metaMes;
        if ($ratio >= 0.9) {
            return 'VERDE';
        }
        if ($ratio >= 0.75) {
            return 'AMARILLO';
        }

        return 'ROJO';
    }

    private function actividadExiste(string $id): bool
    {
        return db_connect()->table('actividades')->where('id_actividad', $id)->countAllResults() > 0;
    }

    private function metaExiste(string $idActividad): bool
    {
        return db_connect()->table('metas')->where('id_actividad', $idActividad)->countAllResults() > 0;
    }
}
