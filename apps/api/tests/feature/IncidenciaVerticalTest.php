<?php

declare(strict_types=1);

use App\Database\Seeds\Sprint6Seeder;
use CodeIgniter\Shield\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Fase 3 · Sprint 6 — incidencia y verticales (doc 05 §8/§9, doc 03 §3.5/§3.6).
 *
 * @internal
 */
final class IncidenciaVerticalTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = null;
    protected $refresh   = true;
    protected $seed      = Sprint6Seeder::class;

    protected function setUp(): void
    {
        parent::setUp();
        cache()->clean();
    }

    /* ===================== Incidencia ===================== */

    public function testListarPropuestasYProcesos(): void
    {
        $this->assertNotEmpty($this->json('coordinacion@demo.test', 'get', 'api/v1/incidencia/propuestas')['body']['data']);
        $this->assertNotEmpty($this->json('coordinacion@demo.test', 'get', 'api/v1/incidencia/procesos')['body']['data']);
    }

    public function testCrearPropuestaSobreActividadValida(): void
    {
        $r = $this->json('coordinacion@demo.test', 'post', 'api/v1/incidencia/propuestas', [
            'id_actividad' => 'ACT_030', 'nombre_propuesta' => 'Nueva propuesta', 'mejora_documentada' => true,
        ]);
        $this->assertSame(201, $r['status']);
        $this->assertTrue($r['body']['data']['mejora_documentada']); // booleano correctamente serializado
        $this->seeInDatabase('auditoria', ['entidad' => 'propuestas_incidencia', 'accion' => 'alta']);
    }

    public function testCrearPropuestaActividadInexistente422(): void
    {
        $this->assertSame(422, $this->json('coordinacion@demo.test', 'post', 'api/v1/incidencia/propuestas', [
            'id_actividad' => 'ACT_NOPE', 'nombre_propuesta' => 'x',
        ])['status']);
    }

    public function testCompromisoExigeProcesoValido(): void // RN-004
    {
        $malo = $this->json('coordinacion@demo.test', 'post', 'api/v1/incidencia/compromisos', [
            'id_proceso_incidencia' => 999999, 'identificacion' => 'sin proceso',
        ]);
        $this->assertSame(422, $malo['status']);

        $bueno = $this->json('coordinacion@demo.test', 'post', 'api/v1/incidencia/compromisos', [
            'id_proceso_incidencia' => 1, 'identificacion' => 'Compromiso válido',
        ]);
        $this->assertSame(201, $bueno['status']);
        $this->seeInDatabase('compromisos', ['id_proceso_incidencia' => 1, 'identificacion' => 'Compromiso válido']);
    }

    public function testHitoExigeProcesoValido(): void // RN-004
    {
        $this->assertSame(422, $this->json('coordinacion@demo.test', 'post', 'api/v1/incidencia/hitos', [
            'id_proceso_incidencia' => 999999, 'descripcion_hito' => 'sin proceso',
        ])['status']);

        $r = $this->json('coordinacion@demo.test', 'post', 'api/v1/incidencia/hitos', [
            'id_proceso_incidencia' => 1, 'tipo_hito' => 'Avance', 'descripcion_hito' => 'Hito válido',
        ]);
        $this->assertSame(201, $r['status']);
    }

    public function testCapturistaNoCreaProcesoFueraDeAmbito403(): void
    {
        // ACT_031 es de INS_00001; el capturista (INS_00002) no puede.
        $this->assertSame(403, $this->json('capturista@demo.test', 'post', 'api/v1/incidencia/procesos', [
            'id_actividad' => 'ACT_031', 'nombre' => 'Intento',
        ])['status']);
    }

    /* ===================== Verticales ===================== */

    public function testOcupacionShelterCalculaPorcentaje(): void // RF-VERT-090
    {
        $rows = $this->json('coordinacion@demo.test', 'get', 'api/v1/shelter/ocupacion')['body']['data'];
        $this->assertNotEmpty($rows);
        // Sembrado: ACT_224, capacidad 20, ocupación 17 -> 85.0 %.
        $this->assertEqualsWithDelta(85.0, $rows[0]['pct_ocupacion'], 0.01);
    }

    public function testSostenibilidadCalculaIndicadores(): void // RF-VERT-091
    {
        $rows = $this->json('coordinacion@demo.test', 'get', 'api/v1/sostenibilidad')['body']['data'];
        $this->assertNotEmpty($rows);
        $s = $rows[0]; // ingresos 50000, costos 30000+8000, recursos 12000+5000, meta 240000
        $this->assertEqualsWithDelta(12000.0, $s['utilidad_neta_mes'], 0.01);
        $this->assertEqualsWithDelta(17000.0, $s['recursos_totales_mes'], 0.01);
        $this->assertEqualsWithDelta(20.8, $s['pct_avance_anual'], 0.05);
        $this->assertSame('ROJO', $s['semaforo']);
    }

    public function testOcupacionAcotadaAlAmbito(): void
    {
        // La ocupación sembrada es de ACT_224 (INS_00003): el capturista (INS_00002) no la ve.
        $rows = $this->json('capturista@demo.test', 'get', 'api/v1/shelter/ocupacion')['body']['data'];
        $this->assertEmpty($rows);
    }

    public function testCrearOcupacion(): void
    {
        $r = $this->json('coordinacion@demo.test', 'post', 'api/v1/shelter/ocupacion', [
            'id_actividad' => 'ACT_224', 'mes_periodo' => 'M07', 'tipo_espacio' => 'Dormitorio',
            'capacidad_instalada' => 10, 'ocupacion' => 5,
        ]);
        $this->assertSame(201, $r['status']);
        $this->assertEqualsWithDelta(50.0, $r['body']['data']['pct_ocupacion'], 0.01);
    }

    /* ===================== Helpers ===================== */

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
