<?php

declare(strict_types=1);

use App\Database\Seeds\Sprint3Seeder;
use CodeIgniter\Shield\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Cadena MEL núcleo + deduplicación (Sprint 3, doc 05 §4–§5, doc 06 QA1–QA7).
 * Shield + dominio + cadena de muestra en SQLite :memory:.
 *
 * @internal
 */
final class CadenaMelTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = null;
    protected $refresh   = true;
    protected $seed      = Sprint3Seeder::class;

    protected function setUp(): void
    {
        parent::setUp();
        cache()->clean();
    }

    /* ===================== Cadena referencial (QA1, RN-001/002) ===================== */

    public function testCrearCadenaCompletaProcesoEventoEjecucion(): void
    {
        $proc = $this->json('coordinacion@demo.test', 'post', 'api/v1/procesos', [
            'nombre'            => 'Proceso nuevo de prueba',
            'tipo_programacion' => 'SESION_UNICA',
            'id_actividad'      => 'ACT_030',
        ]);
        $this->assertSame(201, $proc['status']);

        $evento = $this->json('coordinacion@demo.test', 'post', 'api/v1/eventos-programados', [
            'id_actividad'       => 'ACT_030',
            'tipo_programacion'  => 'SESION_UNICA',
            'fecha_inicio'       => '2026-07-01',
            'fecha_finalizacion' => '2026-07-01',
        ]);
        $this->assertSame(201, $evento['status']);
        $idEvento = (int) $evento['body']['data']['id_evento_programado'];

        $ejec = $this->json('coordinacion@demo.test', 'post', 'api/v1/ejecuciones', [
            'id_evento_programado'        => $idEvento,
            'fecha_ejecucion_real'        => '2026-07-01',
            'tipo_registro_participacion' => 'Nominal',
            'resumen_narrativo'           => 'Sesión realizada con asistencia y seguimiento completo.',
            'evidencia_url'               => 'https://drive.google.com/file/d/zzz/view',
        ]);
        $this->assertSame(201, $ejec['status']);
        $this->assertSame('OK', $ejec['body']['data']['control_registro']);
        // El evento programado pasó a 'ejecutado'.
        $this->seeInDatabase('eventos_programados', ['id_evento_programado' => $idEvento, 'estatus' => 'ejecutado']);
    }

    public function testEjecucionSinEventoDevuelve422(): void
    {
        $r = $this->json('coordinacion@demo.test', 'post', 'api/v1/ejecuciones', [
            'id_evento_programado'        => 999999,
            'tipo_registro_participacion' => 'Nominal',
        ]);
        $this->assertSame(422, $r['status']);
    }

    public function testParticipacionSinEjecucionDevuelve422(): void
    {
        $r = $this->json('coordinacion@demo.test', 'post', 'api/v1/participaciones', [
            'id_ejecucion'    => 999999,
            'nombres'         => 'Test',
            'apellido_paterno' => 'Prueba',
            'sexo'            => 'M',
            'telefono'        => '6560000000',
            'colonia_persona' => 'Centro',
        ]);
        $this->assertSame(422, $r['status']);
    }

    /* ===================== Estado de ejecución (QA4, RF-EJEC-031) ===================== */

    public function testEjecucionIncompletaSinResumenNiEvidencia(): void
    {
        // Evento 90 (ACT_030, INS_00002) sin resumen/evidencia -> INCOMPLETO.
        $r = $this->json('coordinacion@demo.test', 'post', 'api/v1/ejecuciones', [
            'id_evento_programado'        => 90,
            'fecha_ejecucion_real'        => '2026-06-15',
            'tipo_registro_participacion' => 'Nominal',
        ]);
        $this->assertSame(201, $r['status']);
        $this->assertSame('INCOMPLETO', $r['body']['data']['control_registro']);
    }

    public function testEjecucionAgregadaQuedaEnEstadoAgregado(): void
    {
        $r = $this->json('coordinacion@demo.test', 'post', 'api/v1/ejecuciones', [
            'id_evento_programado'        => 90,
            'fecha_ejecucion_real'        => '2026-06-15',
            'tipo_registro_participacion' => 'Agregado',
        ]);
        $this->assertSame(201, $r['status']);
        $this->assertSame('AGREGADO', $r['body']['data']['control_registro']);
    }

    public function testNoSePuedeEjecutarActividadTipoE(): void // QA5
    {
        // Evento sobre ACT_048 (tipo E, INS_00002), luego intentar ejecutarlo -> 422.
        $evento = $this->json('coordinacion@demo.test', 'post', 'api/v1/eventos-programados', [
            'id_actividad'       => 'ACT_048',
            'tipo_programacion'  => 'SESION_UNICA',
            'fecha_inicio'       => '2026-07-02',
            'fecha_finalizacion' => '2026-07-02',
        ]);
        $idEvento = (int) $evento['body']['data']['id_evento_programado'];

        $r = $this->json('coordinacion@demo.test', 'post', 'api/v1/ejecuciones', [
            'id_evento_programado'        => $idEvento,
            'tipo_registro_participacion' => 'Nominal',
        ]);
        $this->assertSame(422, $r['status']);
    }

    public function testEjecucionSobreEventoCanceladoDevuelve409(): void
    {
        // Evento 92 (ACT_031) está cancelado; coordinación es global.
        $r = $this->json('coordinacion@demo.test', 'post', 'api/v1/ejecuciones', [
            'id_evento_programado'        => 92,
            'tipo_registro_participacion' => 'Nominal',
        ]);
        $this->assertSame(409, $r['status']);
    }

    /* ===================== Máquina de estados (SRS §4) ===================== */

    public function testValidarIncompletoAOkComoCoordinacion(): void
    {
        $r = $this->json('coordinacion@demo.test', 'patch', 'api/v1/ejecuciones/133/validacion', [
            'control_registro' => 'OK',
            'detalle'          => 'Revisado y validado',
        ]);
        $this->assertSame(200, $r['status']);
        $this->seeInDatabase('ejecuciones', ['id_ejecucion' => 133, 'control_registro' => 'OK']);
        $this->seeInDatabase('auditoria', ['entidad' => 'ejecuciones', 'id_registro' => '133', 'accion' => 'validacion']);
    }

    public function testTransicionIlegalDevuelve409(): void
    {
        // Ejecución 132 está en OK; OK solo admite ->REVISAR. ->AGREGADO es ilegal.
        $r = $this->json('coordinacion@demo.test', 'patch', 'api/v1/ejecuciones/132/validacion', [
            'control_registro' => 'AGREGADO',
        ]);
        $this->assertSame(409, $r['status']);
    }

    public function testRevisarAOkSoloCoordinacion(): void // SRS §4.2
    {
        // Coordinación mueve 133 INCOMPLETO -> REVISAR (legal).
        $this->json('coordinacion@demo.test', 'patch', 'api/v1/ejecuciones/133/validacion', ['control_registro' => 'REVISAR']);
        // Capturista (ve INS_00002) intenta REVISAR -> OK: 403.
        $r = $this->json('capturista@demo.test', 'patch', 'api/v1/ejecuciones/133/validacion', ['control_registro' => 'OK']);
        $this->assertSame(403, $r['status']);
    }

    /* ===================== Segmentación / IDOR (QA7, doc 06 §2.7) ===================== */

    public function testCapturistaSoloVeEjecucionesDeSuInstitucion(): void
    {
        $r    = $this->json('capturista@demo.test', 'get', 'api/v1/ejecuciones');
        $rows = $r['body']['data'];
        $this->assertNotEmpty($rows);
        $ids = array_column($rows, 'id_ejecucion');
        $this->assertContains(133, $ids);  // ACT_030 -> INS_00002
        $this->assertNotContains(132, $ids); // ACT_031 -> INS_00001
    }

    public function testCapturistaNoAccedeEjecucionFueraDeAmbito404(): void
    {
        // Ejecución 132 pertenece a INS_00001; capturista (INS_00002) -> 404 (indistinguible).
        $r = $this->json('capturista@demo.test', 'get', 'api/v1/ejecuciones/132');
        $this->assertSame(404, $r['status']);
    }

    public function testCapturistaNoEjecutaEventoFueraDeAmbito403(): void
    {
        // Evento 88 (ACT_031, INS_00001) fuera del ámbito del capturista -> 403.
        $r = $this->json('capturista@demo.test', 'post', 'api/v1/ejecuciones', [
            'id_evento_programado'        => 88,
            'tipo_registro_participacion' => 'Nominal',
        ]);
        $this->assertSame(403, $r['status']);
    }

    public function testCapturistaNoCreaProcesoFueraDeAmbito403(): void
    {
        $r = $this->json('capturista@demo.test', 'post', 'api/v1/procesos', [
            'nombre'            => 'Intento fuera de ámbito',
            'tipo_programacion' => 'SESION_UNICA',
            'id_actividad'      => 'ACT_031', // INS_00001
        ]);
        $this->assertSame(403, $r['status']);
    }

    /* ===================== Deduplicación (QA2/QA3, ADR-003) ===================== */

    public function testMismaPersonaConSinAcentoConsolida(): void // QA2
    {
        $base = ['id_ejecucion' => 133, 'anio_nacimiento' => 1990, 'sexo' => 'M', 'telefono' => '6561234567', 'colonia_persona' => 'Independencia'];

        $a = $this->json('coordinacion@demo.test', 'post', 'api/v1/participaciones', $base + [
            'nombres' => 'José', 'apellido_paterno' => 'Pérez', 'apellido_materno' => 'López',
        ]);
        $this->assertSame(201, $a['status']);
        $this->assertSame('OK', $a['body']['data']['control_registro']);

        $b = $this->json('coordinacion@demo.test', 'post', 'api/v1/participaciones', $base + [
            'nombres' => 'Jose', 'apellido_paterno' => 'Perez', 'apellido_materno' => 'Lopez',
        ]);
        $this->assertSame(201, $b['status']);

        $this->assertNotNull($a['body']['data']['id_persona']);
        $this->assertSame($a['body']['data']['id_persona'], $b['body']['data']['id_persona'], 'Acento-insensible: misma clave, misma persona.');
    }

    public function testTelefonoCompartidoEntraEnCola(): void // QA3
    {
        // Teléfono de María García (PER_00120) con nombre distinto -> REVISAR + cola.
        $r = $this->json('coordinacion@demo.test', 'post', 'api/v1/participaciones', [
            'id_ejecucion' => 133, 'nombres' => 'Mario', 'apellido_paterno' => 'Lopez',
            'anio_nacimiento' => 1995, 'sexo' => 'M', 'telefono' => '6565551212', 'colonia_persona' => 'Centro',
        ]);
        $this->assertSame(201, $r['status']);
        $this->assertSame('REVISAR', $r['body']['data']['control_registro']);
        $this->assertSame('DUPLICADO_EN_CAPTURA', $r['body']['data']['alerta_duplicado']);
        $this->assertNull($r['body']['data']['id_persona']);

        $cola = $this->json('coordinacion@demo.test', 'get', 'api/v1/personas/duplicados');
        $this->assertSame(200, $cola['status']);
        $sugeridas = array_column($cola['body']['data'], 'id_persona_sugerida');
        $this->assertContains('PER_00120', $sugeridas);
    }

    public function testResolverDuplicadoFusionaYAudita(): void
    {
        $r        = $this->json('coordinacion@demo.test', 'post', 'api/v1/participaciones', [
            'id_ejecucion' => 133, 'nombres' => 'Mario', 'apellido_paterno' => 'Lopez',
            'anio_nacimiento' => 1995, 'sexo' => 'M', 'telefono' => '6565551212', 'colonia_persona' => 'Centro',
        ]);
        $idPar = (int) $r['body']['data']['id_participacion'];

        $res = $this->json('coordinacion@demo.test', 'patch', "api/v1/personas/duplicados/{$idPar}", [
            'accion' => 'fusionar', 'id_persona_destino' => 'PER_00120', 'motivo' => 'Misma persona confirmada',
        ]);
        $this->assertSame(200, $res['status']);
        $this->seeInDatabase('participaciones', ['id_participacion' => $idPar, 'id_persona' => 'PER_00120', 'control_registro' => 'OK']);
        $this->seeInDatabase('auditoria', ['entidad' => 'participaciones', 'id_registro' => (string) $idPar, 'accion' => 'validacion']);
    }

    public function testResolverDuplicadoComoCapturista403(): void
    {
        $r = $this->json('capturista@demo.test', 'patch', 'api/v1/personas/duplicados/991', [
            'accion' => 'confirmar_nueva', 'motivo' => 'intento',
        ]);
        $this->assertSame(403, $r['status']);
    }

    /* ===================== Agregadas (RF-AGRE-051) ===================== */

    public function testAgregadaCasoAExigePeriodoCorte(): void
    {
        // Ejecución 134 -> ACT_052 (caso A): sin periodo_corte -> 422.
        $sin = $this->json('coordinacion@demo.test', 'post', 'api/v1/participaciones-agregadas', [
            'id_ejecucion' => 134, 'cantidad_participantes' => 10,
        ]);
        $this->assertSame(422, $sin['status']);

        $con = $this->json('coordinacion@demo.test', 'post', 'api/v1/participaciones-agregadas', [
            'id_ejecucion' => 134, 'cantidad_participantes' => 10, 'periodo_corte' => 'M06',
        ]);
        $this->assertSame(201, $con['status']);
    }

    /* ===================== Personas (doc 05 §5) y evidencias ===================== */

    public function testPersonasSoloCoordinacion(): void
    {
        $this->assertSame(403, $this->json('capturista@demo.test', 'get', 'api/v1/personas')['status']);
        $this->assertSame(200, $this->json('coordinacion@demo.test', 'get', 'api/v1/personas')['status']);
    }

    public function testNombreEvidenciaNormalizado(): void
    {
        $r = $this->json('coordinacion@demo.test', 'get', 'api/v1/evidencias/nombre?id_evento=88&id_actividad=ACT_031&ext=pdf');
        $this->assertSame(200, $r['status']);
        $this->assertStringStartsWith('CPJ_EVID_', $r['body']['data']['nombre']);
        $this->assertStringEndsWith('_ACT031_001.pdf', $r['body']['data']['nombre']);
    }

    public function testEventoMultisesionExigeProceso(): void
    {
        $r = $this->json('coordinacion@demo.test', 'post', 'api/v1/eventos-programados', [
            'id_actividad'       => 'ACT_030',
            'tipo_programacion'  => 'MULTI_SESION_PROGRAMADA',
            'fecha_inicio'       => '2026-07-01',
            'fecha_finalizacion' => '2026-07-01',
        ]);
        $this->assertSame(422, $r['status']);
    }

    /* ===================== Helpers ===================== */

    /**
     * Ejecuta una petición autenticada con cuerpo JSON y devuelve estado + cuerpo decodificado.
     *
     * @param array<string, mixed> $body
     *
     * @return array{status:int, body:array<string, mixed>}
     */
    private function json(string $email, string $method, string $uri, array $body = []): array
    {
        $req = $this->withHeaders(['Authorization' => 'Bearer ' . $this->tokenDe($email)]);
        if ($body !== []) {
            $req = $req->withBodyFormat('json');
        }
        $res = $req->call($method, $uri, $body);

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($res->getJSON() ?? '{}', true) ?: [];

        return ['status' => $res->response()->getStatusCode(), 'body' => $decoded];
    }

    private function tokenDe(string $email): string
    {
        $user = model(UserModel::class)->findByCredentials(['email' => $email]);
        self::assertNotNull($user);

        return $user->generateAccessToken('test')->raw_token;
    }
}
