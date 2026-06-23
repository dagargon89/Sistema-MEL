<?php

declare(strict_types=1);

use App\Database\Seeds\Sprint1Seeder;
use CodeIgniter\Shield\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Catálogos y herencia (Sprint 2, doc 05 §3, RF-CAT-010..013). Shield + App en SQLite.
 *
 * @internal
 */
final class CatalogoTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = null;
    protected $refresh   = true;
    protected $seed      = Sprint1Seeder::class;

    protected function setUp(): void
    {
        parent::setUp();
        cache()->clean();
    }

    public function testActividadesTraenHerenciaYPager(): void
    {
        $r = $this->bearer('coordinacion@demo.test')->get('api/v1/catalogos/actividades');
        $r->assertStatus(200);
        $json = json_decode($r->getJSON() ?? '{}', true);

        $this->assertTrue($json['success']);
        $this->assertArrayHasKey('pager', $json);
        $this->assertArrayHasKey('currentPage', $json['pager']);
        $this->assertNotEmpty($json['data']);
        $this->assertArrayHasKey('herencia', $json['data'][0]);
        $this->assertArrayHasKey('eje', $json['data'][0]['herencia']);
        $this->assertNotSame('', $json['data'][0]['herencia']['eje']); // nombre resuelto, no id
    }

    public function testCapturistaSoloVeSuInstitucion(): void // QA7
    {
        $r    = $this->bearer('capturista@demo.test')->get('api/v1/catalogos/actividades');
        $rows = json_decode($r->getJSON() ?? '{}', true)['data'];
        $this->assertNotEmpty($rows);
        foreach ($rows as $a) {
            $this->assertSame('INS_00002', $a['id_institucion']);
        }
    }

    public function testFiltraPorTipo(): void
    {
        $r    = $this->bearer('coordinacion@demo.test')->get('api/v1/catalogos/actividades?tipo=E');
        $rows = json_decode($r->getJSON() ?? '{}', true)['data'];
        $this->assertNotEmpty($rows);
        foreach ($rows as $a) {
            $this->assertSame('E', $a['tipo_registro']);
        }
    }

    public function testCapturistaNoPuedeCrear403(): void
    {
        $this->bearer('capturista@demo.test')
            ->withBodyFormat('json')
            ->post('api/v1/catalogos/actividades', $this->actividadValida())
            ->assertStatus(403);
    }

    public function testCoordinacionCreaYAudita(): void
    {
        $r = $this->bearer('coordinacion@demo.test')
            ->withBodyFormat('json')
            ->post('api/v1/catalogos/actividades', $this->actividadValida());

        $r->assertStatus(201);
        $this->seeInDatabase('actividades', ['id_actividad' => 'ACT_900']);
        $this->seeInDatabase('auditoria', ['entidad' => 'actividades', 'id_registro' => 'ACT_900', 'accion' => 'alta']);
    }

    public function testCrearConFkInvalidaDevuelve422(): void
    {
        $payload           = $this->actividadValida();
        $payload['id_eje'] = 'EJE_NOEXISTE';
        $this->bearer('coordinacion@demo.test')
            ->withBodyFormat('json')
            ->post('api/v1/catalogos/actividades', $payload)
            ->assertStatus(422);
    }

    public function testReclasificarComoCoordinacionAudita(): void
    {
        $r = $this->bearer('coordinacion@demo.test')
            ->withBodyFormat('json')
            ->patch('api/v1/catalogos/actividades/ACT_031/tipo-registro', ['tipo_registro' => 'E', 'motivo' => 'Ajuste POA 2026']);

        $r->assertStatus(200);
        $this->seeInDatabase('actividades', ['id_actividad' => 'ACT_031', 'tipo_registro' => 'E']);
        $this->seeInDatabase('auditoria', ['entidad' => 'actividades', 'id_registro' => 'ACT_031', 'accion' => 'reclasificacion']);
    }

    public function testReclasificarComoCapturista403(): void
    {
        $this->bearer('capturista@demo.test')
            ->withBodyFormat('json')
            ->patch('api/v1/catalogos/actividades/ACT_030/tipo-registro', ['tipo_registro' => 'E', 'motivo' => 'x intento'])
            ->assertStatus(403);
    }

    public function testListaEjesYLineasFiltradas(): void
    {
        $ejes = json_decode($this->bearer('coordinacion@demo.test')->get('api/v1/catalogos/ejes')->getJSON() ?? '{}', true)['data'];
        $this->assertCount(3, $ejes);

        $lineas = json_decode($this->bearer('coordinacion@demo.test')->get('api/v1/catalogos/lineas?id_eje=EJE_00001')->getJSON() ?? '{}', true)['data'];
        $this->assertNotEmpty($lineas);
        foreach ($lineas as $l) {
            $this->assertSame('EJE_00001', $l['id_eje']);
        }
    }

    /** @return array<string, mixed> */
    private function actividadValida(): array
    {
        return [
            'id_actividad'   => 'ACT_900',
            'num_actividad'  => 900,
            'nombre'         => 'Actividad de prueba Sprint 2',
            'id_eje'         => 'EJE_00001',
            'id_linea'       => 'LIN_00001',
            'id_componente'  => 'COM_00001',
            'id_institucion' => 'INS_00001',
            'tipo_registro'  => 'P',
        ];
    }

    private function bearer(string $email): self
    {
        $user = model(UserModel::class)->findByCredentials(['email' => $email]);
        self::assertNotNull($user);

        return $this->withHeaders(['Authorization' => 'Bearer ' . $user->generateAccessToken('test')->raw_token]);
    }
}
