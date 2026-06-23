<?php

declare(strict_types=1);

use App\Database\Seeds\Sprint6Seeder;
use CodeIgniter\Shield\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Fase 4 · Sprint 7 — resultados (tipo R), gobernanza (solicitudes/auditoría) y
 * reportería FECHAC (doc 05 §10/§11/§12).
 *
 * @internal
 */
final class ResultadoGobernanzaTest extends CIUnitTestCase
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

    /* ===================== Resultados (tipo R) ===================== */

    public function testCrearResultadoSobreTipoR(): void
    {
        $r = $this->json('coordinacion@demo.test', 'post', 'api/v1/resultados', [
            'id_actividad' => 'ACT_200', 'indicador' => 'Reducción de incidentes', 'linea_base' => 0, 'metodo_medicion' => 'Encuesta',
        ]);
        $this->assertSame(201, $r['status']);
        $this->seeInDatabase('resultados', ['id_actividad' => 'ACT_200', 'indicador' => 'Reducción de incidentes']);
        $this->seeInDatabase('auditoria', ['entidad' => 'resultados', 'accion' => 'alta']);
    }

    public function testResultadoSobreActividadNoRDevuelve422(): void // RF-RES-100
    {
        $this->assertSame(422, $this->json('coordinacion@demo.test', 'post', 'api/v1/resultados', [
            'id_actividad' => 'ACT_031', 'indicador' => 'No permitido', // tipo P
        ])['status']);
    }

    /* ===================== Gobernanza: solicitudes ===================== */

    public function testCualquierUsuarioRegistraSolicitud(): void
    {
        $r = $this->json('capturista@demo.test', 'post', 'api/v1/solicitudes', [
            'descripcion' => 'Corregir teléfono de PAR-988', 'tipo_solicitud' => 'correccion', 'entidad_afectada' => 'participaciones',
        ]);
        $this->assertSame(201, $r['status']);
        $this->assertSame('en_revision', $r['body']['data']['estado']);
        $this->seeInDatabase('solicitudes', ['descripcion' => 'Corregir teléfono de PAR-988', 'estado' => 'en_revision']);
    }

    public function testResolverSolicitudSoloCoordinacion(): void
    {
        $crear = $this->json('capturista@demo.test', 'post', 'api/v1/solicitudes', [
            'descripcion' => 'Ajuste menor', 'tipo_solicitud' => 'ajuste',
        ]);
        $id = (int) $crear['body']['data']['id_solicitud'];

        // Capturista no resuelve.
        $this->assertSame(403, $this->json('capturista@demo.test', 'patch', "api/v1/solicitudes/{$id}", [
            'estado' => 'resuelta',
        ])['status']);

        // Coordinación sí.
        $res = $this->json('coordinacion@demo.test', 'patch', "api/v1/solicitudes/{$id}", [
            'estado' => 'resuelta', 'comentarios' => 'Aplicado',
        ]);
        $this->assertSame(200, $res['status']);
        $this->seeInDatabase('solicitudes', ['id_solicitud' => $id, 'estado' => 'resuelta']);
        $this->seeInDatabase('auditoria', ['entidad' => 'solicitudes', 'id_registro' => (string) $id, 'accion' => 'edicion']);
    }

    /* ===================== Gobernanza: auditoría ===================== */

    public function testAuditoriaSoloRolesAutorizados(): void
    {
        $this->assertSame(403, $this->json('capturista@demo.test', 'get', 'api/v1/auditoria')['status']);
        $this->assertSame(200, $this->json('coordinacion@demo.test', 'get', 'api/v1/auditoria')['status']);
        $this->assertSame(200, $this->json('direccion@demo.test', 'get', 'api/v1/auditoria')['status']);
    }

    public function testAuditoriaRegistraEventos(): void
    {
        $this->json('coordinacion@demo.test', 'post', 'api/v1/solicitudes', ['descripcion' => 'x', 'tipo_solicitud' => 'mejora']);
        $r = $this->json('coordinacion@demo.test', 'get', 'api/v1/auditoria?entidad=solicitudes');
        $this->assertSame(200, $r['status']);
        $this->assertNotEmpty($r['body']['data']);
        $this->assertSame('alta', $r['body']['data'][0]['accion']);
    }

    /* ===================== Reportería FECHAC ===================== */

    public function testExportFechacSoloCoordinacionDireccion(): void
    {
        $this->assertSame(403, $this->json('capturista@demo.test', 'get', 'api/v1/export/fechac')['status']);
        $this->assertSame(200, $this->json('coordinacion@demo.test', 'get', 'api/v1/export/fechac')['status']);
        $this->assertSame(200, $this->json('direccion@demo.test', 'get', 'api/v1/export/fechac')['status']);
    }

    public function testExportFechacAgregaCifrasReales(): void
    {
        $d = $this->json('coordinacion@demo.test', 'get', 'api/v1/export/fechac')['body']['data'];

        $this->assertSame(2, $d['beneficiarios_unicos']);          // sobre control=OK
        $this->assertSame(9, $d['actividades']['total']);          // 9 actividades sembradas
        $this->assertSame(6, $d['actividades']['P']);
        $this->assertSame(2, $d['actividades']['E']);
        $this->assertSame(1, $d['actividades']['R']);
        $this->assertArrayHasKey('cumplimiento_ejecucion', $d);
        $this->assertArrayHasKey('generado', $d);
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
