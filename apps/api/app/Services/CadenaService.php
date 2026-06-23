<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\ActividadModel;
use App\Models\EventoProgramadoModel;
use App\Models\ProcesoModel;
use App\Repositories\CadenaRepository;
use App\Support\FieldCast;
use Config\Services;

/**
 * Núcleo de la cadena MEL (doc 05 §4, SRS §4): alta de procesos/eventos/ejecuciones
 * y la máquina de estados de `control_registro`. Reproduce la semántica del mock
 * (api.mock.ts) con las reglas de cadena (RN-001/021), el cálculo de estado en
 * servidor y la auditoría de cada escritura. Acota toda operación al ámbito (ADR-004).
 */
class CadenaService
{
    use FieldCast;

    /** Transiciones legales de la máquina de estados (SRS §4.1, espejo de api.mock.ts). */
    private const TRANSICIONES = [
        'CAPTURADO'  => ['INCOMPLETO', 'REVISAR', 'OK', 'AGREGADO'],
        'INCOMPLETO' => ['OK', 'REVISAR'],
        'REVISAR'    => ['OK', 'INCOMPLETO'],
        'OK'         => ['REVISAR'],
        'AGREGADO'   => ['AGREGADO'],
    ];

    private CadenaRepository $repo;
    private AuditoriaService $auditoria;

    public function __construct()
    {
        $this->repo      = new CadenaRepository();
        $this->auditoria = new AuditoriaService();
    }

    /**
     * @param array<string, mixed> $d
     *
     * @return array<string, mixed>
     */
    public function crearProceso(array $d): array
    {
        $idActividad = $this->str($d, 'id_actividad') ?? '';
        $this->exigirActividadEnAmbito($idActividad);

        $row = [
            'nombre'                     => $this->str($d, 'nombre'),
            'tipo_programacion'          => $this->str($d, 'tipo_programacion'),
            'id_actividad'               => $idActividad,
            'fecha_inicio'               => $this->str($d, 'fecha_inicio'),
            'fecha_fin'                  => $this->str($d, 'fecha_fin'),
            'total_sesiones_programadas' => $this->intOrNull($d, 'total_sesiones_programadas'),
            'responsable'                => $this->str($d, 'responsable'),
            'contacto'                   => $this->str($d, 'contacto'),
            'estatus'                    => 'activo',
            'observaciones'              => $this->str($d, 'observaciones'),
        ];

        $model = new ProcesoModel();
        $model->insert($row);
        $id = $model->getInsertID();
        $this->auditoria->registrar('procesos', (string) $id, 'alta', null, $row);

        /** @var array<string, mixed>|null $created */
        $created = $model->find($id);
        if (is_array($created)) {
            return $created;
        }
        $row['id_proceso'] = $id;

        return $row;
    }

    /**
     * @param array<string, mixed> $d
     *
     * @return array<string, mixed>
     */
    public function crearEvento(array $d): array
    {
        $idActividad = $this->str($d, 'id_actividad') ?? '';
        $this->exigirActividadEnAmbito($idActividad);

        $tipo      = $this->str($d, 'tipo_programacion');
        $idProceso = $this->intOrNull($d, 'id_proceso');
        if ($tipo !== 'SESION_UNICA' && $idProceso === null) {
            throw ApiException::unprocessable('Datos inválidos.', ['id_proceso' => 'El proceso es obligatorio en programación multisesión.']);
        }

        $inicio = $this->str($d, 'fecha_inicio') ?? '';
        $fin    = $this->str($d, 'fecha_finalizacion') ?? '';
        if ($fin < $inicio) {
            throw ApiException::unprocessable('Datos inválidos.', ['fecha_finalizacion' => 'La fecha de finalización no puede ser anterior a la de inicio.']);
        }

        $row = [
            'id_actividad'       => $idActividad,
            'id_proceso'         => $idProceso,
            'tipo_programacion'  => $tipo,
            'fecha_inicio'       => $inicio,
            'fecha_finalizacion' => $fin,
            'hora_inicio'        => $this->str($d, 'hora_inicio'),
            'hora_finalizacion'  => $this->str($d, 'hora_finalizacion'),
            'modalidad'          => $this->str($d, 'modalidad'),
            'lugar'              => $this->str($d, 'lugar'),
            'calle_y_numero'     => $this->str($d, 'calle_y_numero'),
            'colonia'            => $this->str($d, 'colonia'),
            'responsable'        => $this->str($d, 'responsable'),
            'contacto'           => $this->str($d, 'contacto'),
            'estatus'            => 'programado',
            'num_sesion'         => null,
            'total_sesiones'     => null,
            'observaciones'      => null,
        ];

        $model = new EventoProgramadoModel();
        $model->insert($row);
        $id = $model->getInsertID();
        $this->auditoria->registrar('eventos_programados', (string) $id, 'alta', null, $row);

        /** @var array<string, mixed>|null $created */
        $created = $model->find($id);
        if (is_array($created)) {
            return $created;
        }
        $row['id_evento_programado'] = $id;

        return $row;
    }

    /**
     * Registra una ejecución real. Valida la cadena (RN-001), bloquea actividades
     * tipo E (RN-021) y calcula `control_registro` en servidor (RF-EJEC-031).
     *
     * @param array<string, mixed> $d
     *
     * @return array{id_ejecucion:int, control_registro:string, nombre_archivo_evidencia:string|null}
     */
    public function crearEjecucion(array $d): array
    {
        $idEvento = $this->intOrNull($d, 'id_evento_programado');
        $evento   = $idEvento === null ? null : (new EventoProgramadoModel())->find($idEvento);
        if (! is_array($evento)) {
            throw ApiException::unprocessable('Datos inválidos.', ['id_evento_programado' => 'El evento programado no existe.']);
        }

        $idActividad = is_string($evento['id_actividad'] ?? null) ? $evento['id_actividad'] : '';
        if (! Services::currentScope()->cubre($this->repo->institucionDeActividad($idActividad))) {
            throw ApiException::forbidden('Fuera de su ámbito de institución.');
        }
        if (($evento['estatus'] ?? null) === 'cancelado') {
            throw ApiException::conflict('El evento está cancelado; no admite ejecución.');
        }

        $act = (new ActividadModel())->find($idActividad);
        if (is_array($act) && ($act['tipo_registro'] ?? null) === 'E') {
            throw ApiException::unprocessable('Datos inválidos.', ['id_actividad' => 'Las actividades tipo E no se ejecutan; usa productos/entregables.']);
        }

        $fecha    = $this->str($d, 'fecha_ejecucion_real');
        $resumen  = $this->str($d, 'resumen_narrativo');
        $evidUrl  = $this->str($d, 'evidencia_url');
        $tipoPart = $this->str($d, 'tipo_registro_participacion') ?? 'Nominal';

        $tieneDatos = $fecha !== null && $resumen !== null && mb_strlen(trim($resumen)) >= 15 && $evidUrl !== null;
        $control    = $tipoPart === 'Agregado' ? 'AGREGADO' : ($tieneDatos ? 'OK' : 'INCOMPLETO');
        $nombreArch = $evidUrl !== null ? EvidenciaService::nombre((int) $idEvento, $idActividad, 'pdf') : null;

        $row = [
            'id_evento_programado'        => $idEvento,
            'fecha_ejecucion_real'        => $fecha,
            'hora_inicio_real'            => $this->str($d, 'hora_inicio_real'),
            'hora_finalizacion_real'      => $this->str($d, 'hora_finalizacion_real'),
            'lugar_real'                  => $this->str($d, 'lugar_real'),
            'colonia_real'                => $this->str($d, 'colonia_real'),
            'responsable_real'            => $this->str($d, 'responsable_real'),
            'estatus_ejecucion'           => $this->str($d, 'estatus_ejecucion') ?? 'ejecutada',
            'tipo_registro_participacion' => $tipoPart,
            'total_participantes'         => null,
            'evidencia_url'               => $evidUrl,
            'nombre_archivo_evidencia'    => $nombreArch,
            'resumen_narrativo'           => $resumen,
            'control_registro'            => $control,
            'observaciones'               => $this->str($d, 'observaciones'),
        ];

        $db = db_connect();
        $db->transStart();
        $db->table('ejecuciones')->insert($row);
        $idEjec = (int) $db->insertID();
        if (($evento['estatus'] ?? null) === 'programado') {
            $db->table('eventos_programados')->where('id_evento_programado', $idEvento)->update(['estatus' => 'ejecutado']);
        }
        $this->auditoria->registrar('ejecuciones', (string) $idEjec, 'alta', null, ['control_registro' => $control]);
        $db->transComplete();

        return ['id_ejecucion' => $idEjec, 'control_registro' => $control, 'nombre_archivo_evidencia' => $nombreArch];
    }

    /**
     * Transición de la máquina de estados (PATCH /ejecuciones/{id}/validacion).
     * `REVISAR→OK` es exclusiva de coordinación (SRS §4.2).
     *
     * @param array<string, mixed> $d
     *
     * @return array<string, mixed>
     */
    public function validarEjecucion(int $id, array $d): array
    {
        $e = $this->repo->findEjecucion($id);
        if ($e === null) {
            throw ApiException::notFound('Ejecución inexistente.');
        }
        if (! Services::currentScope()->cubre($this->repo->institucionDeEjecucion($id))) {
            throw ApiException::notFound('Ejecución inexistente.');
        }

        $origen  = is_string($e['control_registro'] ?? null) ? $e['control_registro'] : 'CAPTURADO';
        $destino = $this->str($d, 'control_registro') ?? '';
        if (! in_array($destino, self::TRANSICIONES[$origen] ?? [], true)) {
            throw ApiException::conflict("Transición ilegal: {$origen} → {$destino}.");
        }
        if ($origen === 'REVISAR' && $destino === 'OK' && Services::currentScope()->rol() !== 'coordinacion') {
            throw ApiException::forbidden('Solo coordinación puede validar un registro en REVISAR.');
        }

        $db = db_connect();
        $db->transStart();
        $db->table('ejecuciones')->where('id_ejecucion', $id)->update(['control_registro' => $destino]);
        $this->auditoria->registrar('ejecuciones', (string) $id, 'validacion', ['control_registro' => $origen], ['control_registro' => $destino, 'detalle' => $this->str($d, 'detalle')]);
        $db->transComplete();

        $actualizada = $this->repo->findEjecucion($id);

        return $actualizada ?? $e;
    }

    /** Verifica que la actividad exista y caiga en el ámbito de la petición. */
    private function exigirActividadEnAmbito(string $idActividad): void
    {
        $inst = $this->repo->institucionDeActividad($idActividad);
        if ($inst === null) {
            throw ApiException::unprocessable('Datos inválidos.', ['id_actividad' => 'Actividad inexistente.']);
        }
        if (! Services::currentScope()->cubre($inst)) {
            throw ApiException::forbidden('Fuera de su ámbito de institución.');
        }
    }
}
