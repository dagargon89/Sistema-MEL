<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ApiException;
use App\Repositories\CadenaRepository;
use App\Repositories\IncidenciaRepository;
use App\Support\FieldCast;
use Config\Services;

/**
 * Incidencia (doc 05 §8, doc 03 §3.5). Propuestas/procesos/alianzas cuelgan de una
 * actividad; compromisos e hitos exigen un proceso de incidencia válido (RN-004 → 422).
 * Todo acotado al ámbito (ADR-004) y auditado.
 */
class IncidenciaService
{
    use FieldCast;

    private IncidenciaRepository $repo;
    private CadenaRepository $cadena;
    private AuditoriaService $auditoria;

    public function __construct()
    {
        $this->repo      = new IncidenciaRepository();
        $this->cadena    = new CadenaRepository();
        $this->auditoria = new AuditoriaService();
    }

    /**
     * @param array<string, mixed> $d
     *
     * @return array<string, mixed>
     */
    public function crearPropuesta(array $d): array
    {
        $idActividad = $this->str($d, 'id_actividad') ?? '';
        $this->exigirActividad($idActividad);

        $row = [
            'nombre_propuesta'                => $this->str($d, 'nombre_propuesta'),
            'promotor_colectivo'              => $this->str($d, 'promotor_colectivo'),
            'tipo_actor'                      => $this->str($d, 'tipo_actor'),
            'fecha_inicio_asesoria'           => $this->str($d, 'fecha_inicio_asesoria'),
            'responsable_equipo'              => $this->str($d, 'responsable_equipo'),
            'sesiones_documentadas'           => $this->intOrNull($d, 'sesiones_documentadas'),
            'mejora_documentada'              => $this->bool01($d, 'mejora_documentada'),
            'cambios_resultado_asesoria'      => $this->str($d, 'cambios_resultado_asesoria'),
            'evidencia_principal'             => $this->str($d, 'evidencia_principal'),
            'alineada_proyectos_estrategicos' => $this->bool01($d, 'alineada_proyectos_estrategicos'),
            'criterios_alineacion_nota'       => $this->str($d, 'criterios_alineacion_nota'),
            'estatus'                         => $this->str($d, 'estatus') ?? 'activo',
            'elegible_reporte'                => $this->bool01($d, 'elegible_reporte'),
            'id_actividad'                    => $idActividad,
            'periodo_reporte'                 => $this->str($d, 'periodo_reporte'),
            'control_registro'                => 'CAPTURADO',
        ];

        return $this->insertar('propuestas_incidencia', $row, 'id_propuesta');
    }

    /**
     * @param array<string, mixed> $d
     *
     * @return array<string, mixed>
     */
    public function crearProceso(array $d): array
    {
        $idActividad = $this->str($d, 'id_actividad') ?? '';
        $this->exigirActividad($idActividad);

        $row = [
            'nombre'                 => $this->str($d, 'nombre'),
            'criterios_elegibilidad' => $this->str($d, 'criterios_elegibilidad'),
            'ultimo_hito_resumen'    => $this->str($d, 'ultimo_hito_resumen'),
            'control_registro'       => 'CAPTURADO',
            'id_actividad'           => $idActividad,
        ];

        return $this->insertar('procesos_incidencia', $row, 'id_proceso_incidencia');
    }

    /**
     * @param array<string, mixed> $d
     *
     * @return array<string, mixed>
     */
    public function crearCompromiso(array $d): array
    {
        $idProceso = $this->exigirProcesoEnAmbito($d);

        $row = [
            'id_proceso_incidencia'   => $idProceso,
            'identificacion'          => $this->str($d, 'identificacion'),
            'seguimiento_documentado' => $this->str($d, 'seguimiento_documentado'),
            'criterios_elegibilidad'  => $this->str($d, 'criterios_elegibilidad'),
            'control_registro'        => 'CAPTURADO',
        ];

        return $this->insertar('compromisos', $row, 'id_compromiso');
    }

    /**
     * @param array<string, mixed> $d
     *
     * @return array<string, mixed>
     */
    public function crearAlianza(array $d): array
    {
        $idActividad = $this->str($d, 'id_actividad') ?? '';
        $this->exigirActividad($idActividad);

        $row = [
            'nombre_alianza'         => $this->str($d, 'nombre_alianza'),
            'datos_alianza'          => $this->str($d, 'datos_alianza'),
            'criterios_elegibilidad' => $this->str($d, 'criterios_elegibilidad'),
            'id_actividad'           => $idActividad,
            'control_registro'       => 'CAPTURADO',
        ];

        return $this->insertar('alianzas', $row, 'id_alianza');
    }

    /**
     * @param array<string, mixed> $d
     *
     * @return array<string, mixed>
     */
    public function crearHito(array $d): array
    {
        $idProceso = $this->exigirProcesoEnAmbito($d);
        $uid       = auth('tokens')->id();

        $row = [
            'id_proceso_incidencia'   => $idProceso,
            'fecha_hito'              => $this->str($d, 'fecha_hito'),
            'tipo_hito'               => $this->str($d, 'tipo_hito'),
            'descripcion_hito'        => $this->str($d, 'descripcion_hito'),
            'evidencia_nombre_o_nota' => $this->str($d, 'evidencia_nombre_o_nota'),
            'registrado_por'          => is_int($uid) || is_string($uid) ? $uid : null,
            'observaciones'           => $this->str($d, 'observaciones'),
        ];

        return $this->insertar('hitos_incidencia', $row, 'id_hito');
    }

    /**
     * Inserta una fila de incidencia, la audita y devuelve la fila completa.
     *
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function insertar(string $tabla, array $row, string $pk): array
    {
        $db = db_connect();
        $db->transStart();
        $db->table($tabla)->insert($row);
        $id = (int) $db->insertID();
        $this->auditoria->registrar($tabla, (string) $id, 'alta', null, ['control_registro' => $row['control_registro'] ?? null]);
        $db->transComplete();

        $row[$pk] = $id;

        return $row;
    }

    /** Valida que la actividad exista y esté en el ámbito (422 / 403). */
    private function exigirActividad(string $idActividad): void
    {
        $inst = $this->cadena->institucionDeActividad($idActividad);
        if ($inst === null) {
            throw ApiException::unprocessable('Datos inválidos.', ['id_actividad' => 'Actividad inexistente.']);
        }
        if (! Services::currentScope()->cubre($inst)) {
            throw ApiException::forbidden('Fuera de su ámbito de institución.');
        }
    }

    /**
     * Valida el proceso de incidencia (RN-004) y el ámbito; devuelve su id.
     *
     * @param array<string, mixed> $d
     */
    private function exigirProcesoEnAmbito(array $d): int
    {
        $idProceso = $this->intOrNull($d, 'id_proceso_incidencia');
        $inst      = $idProceso === null ? null : $this->repo->institucionDeProcesoIncidencia($idProceso);
        if ($idProceso === null || $inst === null) {
            throw ApiException::unprocessable('Datos inválidos.', ['id_proceso_incidencia' => 'El proceso de incidencia no existe.']);
        }
        if (! Services::currentScope()->cubre($inst)) {
            throw ApiException::forbidden('Fuera de su ámbito de institución.');
        }

        return $idProceso;
    }

    /** @param array<string, mixed> $d */
    private function bool01(array $d, string $key): int
    {
        $v = $d[$key] ?? null;

        return $v === true || $v === 1 || $v === '1' || $v === 'true' ? 1 : 0;
    }
}
