<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\EjecucionModel;
use App\Models\ParticipacionModel;
use App\Repositories\CadenaRepository;
use App\Support\FieldCast;
use Config\Services;
use Normalizer;

/**
 * Deduplicación del lado servidor (ADR-003, RF-PART-041..044). Reproduce la semántica
 * de `calcularClaveDedup()`/`norm()` del mock: clave determinista acento/espacio-insensible,
 * asignación de `id_persona`, consolidación de la misma persona y **cola de revisión** para
 * sospechosos (nunca autofusión). `personas` se puebla aquí, jamás por alta manual.
 */
class DeduplicacionService
{
    use FieldCast;

    private CadenaRepository $repo;
    private AuditoriaService $auditoria;

    public function __construct()
    {
        $this->repo      = new CadenaRepository();
        $this->auditoria = new AuditoriaService();
    }

    /**
     * Clave de dedup determinista, rellena a CHAR(40) como el DDL (RN-060/061).
     * Debe producir el MISMO valor que `calcularClaveDedup()` del mock.
     */
    public static function calcularClave(?string $apPaterno, ?string $apMaterno, ?string $nombres, ?int $anio, ?string $telefono): string
    {
        $base = self::norm($apPaterno) . self::norm($apMaterno) . self::norm($nombres)
            . ($anio !== null ? (string) $anio : '') . '_'
            . (preg_replace('/\D/', '', $telefono ?? '') ?? '');

        return substr($base . str_repeat('_', 40), 0, 40);
    }

    /**
     * Registra una participación nominal con deduplicación (POST /participaciones).
     *
     * @param array<string, mixed> $d
     *
     * @return array{id_participacion:int, id_persona:string|null, control_registro:string, alerta_duplicado:string}
     */
    public function crearParticipacion(array $d): array
    {
        $idEjec = $this->intOrNull($d, 'id_ejecucion');
        $ej     = $idEjec === null ? null : (new EjecucionModel())->find($idEjec);
        if (! is_array($ej) || $idEjec === null) {
            throw ApiException::unprocessable('Datos inválidos.', ['id_ejecucion' => 'La ejecución no existe.']);
        }
        if (! Services::currentScope()->cubre($this->repo->institucionDeEjecucion($idEjec))) {
            throw ApiException::forbidden('Fuera de su ámbito de institución.');
        }

        $nombres = $this->str($d, 'nombres') ?? '';
        $apPat   = $this->str($d, 'apellido_paterno') ?? '';
        $apMat   = $this->str($d, 'apellido_materno');
        $anio    = $this->intOrNull($d, 'anio_nacimiento');
        $sexo    = $this->str($d, 'sexo') ?? '';
        $tel     = $this->str($d, 'telefono') ?? '';
        $correo  = $this->str($d, 'correo');
        $colonia = $this->str($d, 'colonia_persona') ?? '';
        $fechaEj = is_string($ej['fecha_ejecucion_real'] ?? null) ? $ej['fecha_ejecucion_real'] : null;

        $db = db_connect();
        $db->transStart();
        $r = $this->resolverPersona([
            'nombres'             => $nombres,
            'apellido_paterno'    => $apPat,
            'apellido_materno'    => $apMat,
            'anio_nacimiento'     => $anio,
            'sexo'                => $sexo,
            'telefono'            => $tel,
            'correo'              => $correo,
            'colonia'             => $colonia,
            'fecha_participacion' => $fechaEj,
        ]);
        $clave     = $r['clave'];
        $idPersona = $r['id_persona'];
        $alerta    = $r['alerta'];
        $control   = $r['control'];
        $detalle   = $r['detalle'];

        $controlAuto = $control === 'REVISAR' ? 'REVISAR' : 'OK';
        $db->table('participaciones')->insert([
            'id_ejecucion'          => $idEjec,
            'id_persona'            => $idPersona,
            'nombres'               => $nombres,
            'apellido_paterno'      => $apPat,
            'apellido_materno'      => $apMat,
            'anio_nacimiento'       => $anio,
            'sexo'                  => $sexo,
            'telefono'              => $tel,
            'correo'                => $correo,
            'colonia_persona'       => $colonia,
            'id_datosbeneficiario'  => $clave,
            'alerta_duplicado'      => $alerta,
            'fecha_participacion'   => $fechaEj,
            'control_registro'      => $control,
            'control_automatico'    => $controlAuto,
            'decision_coordinacion' => null,
            'detalle_validacion'    => $detalle,
        ]);
        $idPar = (int) $db->insertID();
        $this->auditoria->registrar('participaciones', (string) $idPar, 'alta', null, [
            'id_persona'       => $idPersona,
            'control_registro' => $control,
            'alerta_duplicado' => $alerta,
        ]);
        $db->transComplete();

        return ['id_participacion' => $idPar, 'id_persona' => $idPersona, 'control_registro' => $control, 'alerta_duplicado' => $alerta];
    }

    /**
     * Núcleo de deduplicación compartido por la captura nominal y la migración (ADR-003):
     * decide la persona (consolidar misma clave / cola por choque de teléfono / alta nueva)
     * y refleja el efecto en `personas`. Debe ejecutarse dentro de una transacción del llamador.
     *
     * @param array{nombres:string, apellido_paterno:string, apellido_materno:string|null, anio_nacimiento:int|null, sexo:string, telefono:string, correo:string|null, colonia:string|null, fecha_participacion:string|null} $p
     *
     * @return array{clave:string, id_persona:string|null, control:string, alerta:string, detalle:string|null}
     */
    public function resolverPersona(array $p): array
    {
        $clave     = self::calcularClave($p['apellido_paterno'], $p['apellido_materno'], $p['nombres'], $p['anio_nacimiento'], $p['telefono']);
        $existente = $this->personaPorClave($clave);
        $db        = db_connect();

        if ($existente !== null) {
            // Misma persona (misma clave): consolida y suma participación.
            $idPersona = is_string($existente['id_persona']) ? $existente['id_persona'] : null;
            if ($idPersona !== null) {
                $db->table('personas')->where('id_persona', $idPersona)
                    ->set('total_participaciones', 'total_participaciones + 1', false)->update();
            }

            return ['clave' => $clave, 'id_persona' => $idPersona, 'control' => 'OK', 'alerta' => 'OK', 'detalle' => null];
        }

        $choque = $this->choquePorTelefono($p['telefono'], $clave);
        if ($choque !== null) {
            // Mismo teléfono, clave distinta -> sospecha; va a la cola, no se fusiona (RN-063).
            return [
                'clave'      => $clave,
                'id_persona' => null,
                'control'    => 'REVISAR',
                'alerta'     => 'DUPLICADO_EN_CAPTURA',
                'detalle'    => "Mismo teléfono que {$choque['id_persona']} ({$choque['nombre_completo']}). Revisar.",
            ];
        }

        // Clave nueva -> nace una persona única (regenerada, nunca copiada del Excel).
        $idPersona = $this->siguienteIdPersona();
        $db->table('personas')->insert([
            'id_persona'            => $idPersona,
            'nombres'               => $p['nombres'],
            'apellido_paterno'      => $p['apellido_paterno'],
            'apellido_materno'      => $p['apellido_materno'],
            'nombre_completo'       => trim("{$p['nombres']} {$p['apellido_paterno']} " . ($p['apellido_materno'] ?? '')),
            'anio_nacimiento'       => $p['anio_nacimiento'],
            'sexo'                  => $p['sexo'],
            'telefono'              => $p['telefono'],
            'correo'                => $p['correo'],
            'colonia'               => $p['colonia'],
            'id_datosbeneficiario'  => $clave,
            'primera_participacion' => $p['fecha_participacion'],
            'total_participaciones' => 1,
            'control_registro'      => 'OK',
            'decision_coordinacion' => null,
        ]);

        return ['clave' => $clave, 'id_persona' => $idPersona, 'control' => 'OK', 'alerta' => 'OK', 'detalle' => null];
    }

    /**
     * Conteo agregado no nominal (POST /participaciones-agregadas).
     *
     * @param array<string, mixed> $d
     *
     * @return array<string, mixed>
     */
    public function crearAgregada(array $d): array
    {
        $idEjec = $this->intOrNull($d, 'id_ejecucion');
        $ctx    = $idEjec === null ? null : $this->contextoEjecucion($idEjec);
        if ($ctx === null) {
            throw ApiException::unprocessable('Datos inválidos.', ['id_ejecucion' => 'La ejecución no existe.']);
        }
        if (! Services::currentScope()->cubre($ctx['id_institucion'])) {
            throw ApiException::forbidden('Fuera de su ámbito de institución.');
        }

        $periodo = $this->str($d, 'periodo_corte');
        if (in_array($ctx['caso_excepcional'], ['A', 'B'], true) && $periodo === null) {
            throw ApiException::unprocessable('Datos inválidos.', ['periodo_corte' => 'El periodo de corte es obligatorio en casos A/B.']);
        }
        $cantidad = $this->intOrNull($d, 'cantidad_participantes') ?? 0;
        if ($cantidad < 0) {
            throw ApiException::unprocessable('Datos inválidos.', ['cantidad_participantes' => 'La cantidad no puede ser negativa.']);
        }

        $row = [
            'id_ejecucion'                => $idEjec,
            'tipo_registro_participacion' => 'Agregado',
            'sexo_grupo'                  => $this->str($d, 'sexo_grupo'),
            'grupo_edad_aprox'            => $this->str($d, 'grupo_edad_aprox'),
            'cantidad_participantes'      => $cantidad,
            'motivo_no_nominal'           => $this->str($d, 'motivo_no_nominal'),
            'fuente_conteo'               => $this->str($d, 'fuente_conteo'),
            'periodo_corte'               => $periodo,
            'evidencia_url'               => null,
            'control_registro'            => 'AGREGADO',
        ];

        $db = db_connect();
        $db->transStart();
        $db->table('participaciones_agregadas')->insert($row);
        $id = (int) $db->insertID();
        $this->auditoria->registrar('participaciones_agregadas', (string) $id, 'alta', null, ['cantidad' => $cantidad]);
        $db->transComplete();

        return array_merge(['id_participacion_agregada' => $id], $row);
    }

    /**
     * Personas consolidadas (GET /personas, solo coordinación/admin).
     *
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    public function listarPersonas(?string $control, int $page, int $limit): array
    {
        $builder = db_connect()->table('personas');
        if ($control !== null) {
            $builder->where('control_registro', $control);
        }
        $total  = (int) $builder->countAllResults(false);
        $result = $builder->orderBy('id_persona', 'ASC')->get($limit, ($page - 1) * $limit);

        /** @var list<array<string, mixed>> $rows */
        $rows = $result === false ? [] : $result->getResultArray();

        return ['rows' => $rows, 'total' => $total];
    }

    /**
     * Cola de revisión de duplicados (GET /personas/duplicados, solo coordinación).
     * Ordena por score de similitud descendente (RF-PART-043).
     *
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    public function colaDuplicados(int $page, int $limit): array
    {
        $result = db_connect()->table('participaciones')
            ->groupStart()->where('alerta_duplicado', 'DUPLICADO_EN_CAPTURA')->orWhere('control_registro', 'REVISAR')->groupEnd()
            ->orderBy('id_participacion', 'ASC')
            ->get();

        /** @var list<array<string, mixed>> $pars */
        $pars = $result === false ? [] : $result->getResultArray();

        $items = [];
        foreach ($pars as $p) {
            $tel = is_string($p['telefono'] ?? null) ? $p['telefono'] : '';
            $sug = $this->personaPorTelefono($tel);
            $items[] = [
                'id_participacion'    => (int) $p['id_participacion'],
                'nombres'             => is_string($p['nombres'] ?? null) ? $p['nombres'] : '',
                'apellido_paterno'    => is_string($p['apellido_paterno'] ?? null) ? $p['apellido_paterno'] : '',
                'id_persona_sugerida' => $sug,
                'score_similitud'     => $sug !== null ? 0.94 : 0.6,
                'motivo'              => is_string($p['detalle_validacion'] ?? null) && $p['detalle_validacion'] !== ''
                    ? $p['detalle_validacion']
                    : 'Posible duplicado en captura.',
            ];
        }

        usort($items, static fn (array $a, array $b): int => $b['score_similitud'] <=> $a['score_similitud']);

        $total = count($items);
        $rows  = array_values(array_slice($items, ($page - 1) * $limit, $limit));

        return ['rows' => $rows, 'total' => $total];
    }

    /**
     * Resuelve un duplicado: fusionar con persona existente o confirmar como nueva
     * (PATCH /personas/duplicados/{id}). Decisión trazable de coordinación (RN-065).
     *
     * @param array<string, mixed> $d
     *
     * @return array<string, mixed>
     */
    public function resolverDuplicado(int $idParticipacion, array $d): array
    {
        $model = new ParticipacionModel();
        /** @var array<string, mixed>|null $par */
        $par = $model->find($idParticipacion);
        if (! is_array($par)) {
            throw ApiException::notFound('Participación inexistente.');
        }

        $accion = $this->str($d, 'accion');
        $motivo = $this->str($d, 'motivo') ?? '';
        $db     = db_connect();
        $db->transStart();

        if ($accion === 'fusionar') {
            $destino = $this->str($d, 'id_persona_destino');
            if ($destino === null || $this->personaPorId($destino) === null) {
                $db->transComplete();

                throw ApiException::conflict('La persona destino no existe.');
            }
            $idPersona = $destino;
        } else {
            $idPersona = $this->siguienteIdPersona();
            $pNombres  = is_string($par['nombres'] ?? null) ? $par['nombres'] : '';
            $pApPat    = is_string($par['apellido_paterno'] ?? null) ? $par['apellido_paterno'] : '';
            $db->table('personas')->insert([
                'id_persona'            => $idPersona,
                'nombres'               => $par['nombres'] ?? null,
                'apellido_paterno'      => $par['apellido_paterno'] ?? null,
                'apellido_materno'      => $par['apellido_materno'] ?? null,
                'nombre_completo'       => trim("{$pNombres} {$pApPat}"),
                'anio_nacimiento'       => $par['anio_nacimiento'] ?? null,
                'sexo'                  => $par['sexo'] ?? null,
                'telefono'              => $par['telefono'] ?? null,
                'correo'                => $par['correo'] ?? null,
                'colonia'               => $par['colonia_persona'] ?? null,
                'id_datosbeneficiario'  => $par['id_datosbeneficiario'] ?? '',
                'primera_participacion' => $par['fecha_participacion'] ?? null,
                'total_participaciones' => 1,
                'control_registro'      => 'OK',
                'decision_coordinacion' => $motivo,
            ]);
        }

        $antes = ['control_registro' => $par['control_registro'] ?? null, 'alerta_duplicado' => $par['alerta_duplicado'] ?? null];
        $db->table('participaciones')->where('id_participacion', $idParticipacion)->update([
            'id_persona'            => $idPersona,
            'alerta_duplicado'      => 'OK',
            'control_registro'      => 'OK',
            'decision_coordinacion' => 'OK',
            'detalle_validacion'    => $motivo,
        ]);
        $this->auditoria->registrar('participaciones', (string) $idParticipacion, 'validacion', $antes, [
            'accion'           => $accion,
            'id_persona'       => $idPersona,
            'control_registro' => 'OK',
        ]);
        $db->transComplete();

        /** @var array<string, mixed>|null $actualizada */
        $actualizada = $model->find($idParticipacion);

        return is_array($actualizada) ? $actualizada : $par;
    }

    /** Normaliza acentos y caja (espejo de utf8mb4_0900_ai_ci y de norm() del mock). */
    private static function norm(?string $s): string
    {
        if ($s === null) {
            return '';
        }
        $s = trim($s);
        if ($s === '') {
            return '';
        }
        if (class_exists(Normalizer::class)) {
            $d = Normalizer::normalize($s, Normalizer::FORM_D);
            if (is_string($d)) {
                $s = preg_replace('/\p{Mn}+/u', '', $d) ?? $s;
            }
        }

        return mb_strtoupper($s, 'UTF-8');
    }

    /** @return array<string, mixed>|null */
    private function personaPorClave(string $clave): ?array
    {
        $r = db_connect()->table('personas')->where('id_datosbeneficiario', $clave)->get(1);
        $row = $r === false ? null : $r->getRowArray();

        return is_array($row) ? $row : null;
    }

    /** @return array<string, mixed>|null */
    private function personaPorId(string $id): ?array
    {
        $r = db_connect()->table('personas')->where('id_persona', $id)->get(1);
        $row = $r === false ? null : $r->getRowArray();

        return is_array($row) ? $row : null;
    }

    /** Primera persona cuyo teléfono coincide en dígitos (sugerencia de la cola). */
    private function personaPorTelefono(string $telefono): ?string
    {
        $digits = preg_replace('/\D/', '', $telefono) ?? '';
        if ($digits === '') {
            return null;
        }
        $r = db_connect()->table('personas')->select('id_persona, telefono')->where('telefono is not null', null, false)->get();
        /** @var list<array<string, mixed>> $rows */
        $rows = $r === false ? [] : $r->getResultArray();
        foreach ($rows as $row) {
            $tel = is_string($row['telefono'] ?? null) ? $row['telefono'] : '';
            if ((preg_replace('/\D/', '', $tel) ?? '') === $digits) {
                return is_string($row['id_persona'] ?? null) ? $row['id_persona'] : null;
            }
        }

        return null;
    }

    /**
     * Persona con el mismo teléfono (en dígitos) pero clave distinta: sospecha de
     * duplicado (RN-063). Solo se consulta cuando no hubo match exacto por clave.
     *
     * @return array{id_persona:string, nombre_completo:string}|null
     */
    private function choquePorTelefono(string $telefono, string $clave): ?array
    {
        $digits = preg_replace('/\D/', '', $telefono) ?? '';
        if ($digits === '') {
            return null;
        }
        $r = db_connect()->table('personas')
            ->select('id_persona, nombre_completo, telefono, id_datosbeneficiario')
            ->where('id_datosbeneficiario !=', $clave)
            ->get();
        /** @var list<array<string, mixed>> $rows */
        $rows = $r === false ? [] : $r->getResultArray();
        foreach ($rows as $row) {
            $tel = is_string($row['telefono'] ?? null) ? $row['telefono'] : '';
            if ((preg_replace('/\D/', '', $tel) ?? '') === $digits) {
                return [
                    'id_persona'      => is_string($row['id_persona'] ?? null) ? $row['id_persona'] : '',
                    'nombre_completo' => is_string($row['nombre_completo'] ?? null) ? $row['nombre_completo'] : '',
                ];
            }
        }

        return null;
    }

    /** Próximo id de persona `PER_NNNNN` (lexicográfico = numérico por el zero-pad). */
    private function siguienteIdPersona(): string
    {
        $r   = db_connect()->table('personas')->select('id_persona')->orderBy('id_persona', 'DESC')->get(1);
        $row = $r === false ? null : $r->getRowArray();
        $n   = (is_array($row) && is_string($row['id_persona'] ?? null)) ? ((int) substr($row['id_persona'], 4)) + 1 : 1;

        return 'PER_' . str_pad((string) $n, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Caso excepcional + institución heredados de la ejecución (para agregadas).
     *
     * @return array{caso_excepcional:string|null, id_institucion:string|null}|null
     */
    private function contextoEjecucion(int $idEjecucion): ?array
    {
        $r = db_connect()->table('ejecuciones ej')
            ->select('a.caso_excepcional, a.id_institucion')
            ->join('eventos_programados e', 'e.id_evento_programado = ej.id_evento_programado')
            ->join('actividades a', 'a.id_actividad = e.id_actividad')
            ->where('ej.id_ejecucion', $idEjecucion)
            ->get(1);
        $row = $r === false ? null : $r->getRowArray();
        if (! is_array($row)) {
            return null;
        }

        return [
            'caso_excepcional' => is_string($row['caso_excepcional'] ?? null) ? $row['caso_excepcional'] : null,
            'id_institucion'   => is_string($row['id_institucion'] ?? null) ? $row['id_institucion'] : null,
        ];
    }
}
