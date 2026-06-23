<?php

declare(strict_types=1);

use App\Database\Seeds\Sprint5Seeder;
use CodeIgniter\Shield\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Fase 2 · Sprint 5 — metas, productos y tableros (doc 05 §6/§7/§12, doc 06 QA4/QA6).
 *
 * @internal
 */
final class MetaTableroTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = null;
    protected $refresh   = true;
    protected $seed      = Sprint5Seeder::class;

    protected function setUp(): void
    {
        parent::setUp();
        cache()->clean();
    }

    /* ===================== Metas ===================== */

    public function testListarMetasAcotadoAlAmbito(): void
    {
        $coord = $this->json('coordinacion@demo.test', 'get', 'api/v1/metas');
        $this->assertSame(200, $coord['status']);
        $this->assertGreaterThanOrEqual(3, count($coord['body']['data']));

        $capt = $this->json('capturista@demo.test', 'get', 'api/v1/metas'); // ámbito INS_00002
        $acts = array_column($capt['body']['data'], 'id_actividad');
        $this->assertNotContains('ACT_031', $acts, 'ACT_031 es de INS_00001; el capturista no debe verla.');
    }

    public function testCoordinacionCreaMetaConMensuales(): void
    {
        $r = $this->json('coordinacion@demo.test', 'post', 'api/v1/metas', [
            'id_actividad'     => 'ACT_052',
            'meta_anual_total' => 50,
            'unidad_meta'      => 'personas',
            'mensuales'        => [['mes' => 'M06', 'valor' => 20], ['mes' => 'M07', 'valor' => 30]],
        ]);
        $this->assertSame(201, $r['status']);
        $idMeta = (int) $r['body']['data']['id_meta'];
        $this->seeInDatabase('metas', ['id_actividad' => 'ACT_052']);
        $this->seeInDatabase('metas_mensuales', ['id_meta' => $idMeta, 'mes' => 'M07', 'valor' => 30]);
        $this->seeInDatabase('auditoria', ['entidad' => 'metas', 'id_registro' => (string) $idMeta, 'accion' => 'alta']);
    }

    public function testCapturistaNoCreaMeta403(): void
    {
        $this->assertSame(403, $this->json('capturista@demo.test', 'post', 'api/v1/metas', [
            'id_actividad' => 'ACT_052', 'mensuales' => [['mes' => 'M06', 'valor' => 5]],
        ])['status']);
    }

    public function testMetaMensualInvalidaDevuelve422(): void
    {
        $this->assertSame(422, $this->json('coordinacion@demo.test', 'post', 'api/v1/metas', [
            'id_actividad' => 'ACT_052', 'mensuales' => [['mes' => 'M99', 'valor' => 5]],
        ])['status']);
    }

    public function testMetaDuplicadaDevuelve422(): void
    {
        // ACT_031 ya tiene meta sembrada (UNIQUE por actividad).
        $this->assertSame(422, $this->json('coordinacion@demo.test', 'post', 'api/v1/metas', [
            'id_actividad' => 'ACT_031', 'mensuales' => [['mes' => 'M06', 'valor' => 5]],
        ])['status']);
    }

    /* ===================== Seguimiento (semáforo) ===================== */

    public function testSeguimientoCasoCCorteAlCierre(): void // QA6
    {
        $rows = $this->json('coordinacion@demo.test', 'get', 'api/v1/metas/seguimiento')['body']['data'];

        $m12 = $this->fila($rows, 'ACT_094', 'M12'); // meta 30, avance 0, caso C
        $this->assertNotNull($m12);
        $this->assertSame('CORTE_AL_CIERRE', $m12['semaforo'], 'Caso C con avance 0 no debe ser ROJO.');

        $m06 = $this->fila($rows, 'ACT_094', 'M06'); // meta 0 -> SIN_META (prevalece sobre C/D)
        $this->assertNotNull($m06);
        $this->assertSame('SIN_META', $m06['semaforo']);
    }

    public function testSeguimientoAvanceRealNominal(): void
    {
        $rows = $this->json('coordinacion@demo.test', 'get', 'api/v1/metas/seguimiento')['body']['data'];

        // ACT_031 M06: 3 participaciones OK en junio sobre meta 10 -> 30% ROJO.
        $m06 = $this->fila($rows, 'ACT_031', 'M06');
        $this->assertNotNull($m06);
        $this->assertSame(3, $m06['avance_mes']);
        $this->assertEqualsWithDelta(30.0, $m06['porcentaje'], 0.01);
        $this->assertSame('ROJO', $m06['semaforo']);
    }

    public function testSeguimientoAcotadoAlAmbito(): void
    {
        $rows = $this->json('capturista@demo.test', 'get', 'api/v1/metas/seguimiento')['body']['data'];
        $acts = array_column($rows, 'id_actividad');
        $this->assertNotContains('ACT_031', $acts); // INS_00001, fuera del ámbito del capturista
    }

    /* ===================== Productos (tipo E) ===================== */

    public function testProductoSobreTipoEValido(): void
    {
        $r = $this->json('coordinacion@demo.test', 'post', 'api/v1/productos', [
            'id_actividad'    => 'ACT_048', // tipo E
            'nombre_producto' => 'Convenio firmado',
            'estatus'         => 'entregado',
            'evidencia_url'   => 'https://drive.google.com/file/d/p/view',
        ]);
        $this->assertSame(201, $r['status']);
        $this->assertSame('OK', $r['body']['data']['control_registro']);
    }

    public function testProductoSinEvidenciaQuedaIncompleto(): void
    {
        $r = $this->json('coordinacion@demo.test', 'post', 'api/v1/productos', [
            'id_actividad' => 'ACT_048', 'nombre_producto' => 'Borrador de convenio',
        ]);
        $this->assertSame(201, $r['status']);
        $this->assertSame('INCOMPLETO', $r['body']['data']['control_registro']);
    }

    public function testProductoSobreActividadNoEDevuelve422(): void // RN-020
    {
        $this->assertSame(422, $this->json('coordinacion@demo.test', 'post', 'api/v1/productos', [
            'id_actividad' => 'ACT_031', 'nombre_producto' => 'No permitido', // tipo P
        ])['status']);
    }

    /* ===================== Tableros (KPIs reales) ===================== */

    public function testTableroEjecutivoCifrasReales(): void // QA4
    {
        $d = $this->json('coordinacion@demo.test', 'get', 'api/v1/tableros/ejecutivo')['body']['data'];

        // Solo cuenta control=OK: 3 participaciones OK (988/989/990), 991 REVISAR excluida.
        $this->assertSame(2, $d['beneficiarios_unicos']);   // PER_00762, PER_00763
        $this->assertSame(3, $d['participaciones_nominales']);
        $this->assertSame(8, $d['participaciones_agregadas']);
        $this->assertSame(11, $d['cobertura_total']);
        $this->assertSame(5, $d['eventos_programados']);
        $this->assertSame(3, $d['ejecuciones']);
        $this->assertEqualsWithDelta(0.6, $d['cumplimiento_ejecucion'], 0.01); // 3/5, no 100%
    }

    public function testTableroAcotadoAlAmbito(): void
    {
        $d = $this->json('capturista@demo.test', 'get', 'api/v1/tableros/ejecutivo')['body']['data'];
        // Las participaciones nominales viven en INS_00001: el capturista no las ve.
        $this->assertSame(0, $d['beneficiarios_unicos']);
        $this->assertSame(0, $d['participaciones_nominales']);
    }

    public function testTableroDesconocidoDevuelve404(): void
    {
        $this->assertSame(404, $this->json('coordinacion@demo.test', 'get', 'api/v1/tableros/inexistente')['status']);
    }

    /* ===================== Helpers ===================== */

    /**
     * @param list<array<string, mixed>> $rows
     *
     * @return array<string, mixed>|null
     */
    private function fila(array $rows, string $idActividad, string $mes): ?array
    {
        foreach ($rows as $r) {
            if (($r['id_actividad'] ?? null) === $idActividad && ($r['mes'] ?? null) === $mes) {
                return $r;
            }
        }

        return null;
    }

    /**
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
