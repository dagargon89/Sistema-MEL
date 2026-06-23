<?php

declare(strict_types=1);

use App\Database\Seeds\Sprint1Seeder;
use CodeIgniter\Shield\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * RBAC + segmentación por institución (ADR-004, doc 06 §2.1/§2.7).
 *
 * @internal
 */
final class RbacScopeTest extends CIUnitTestCase
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

    public function testCapturistaSoloVeSuInstitucion(): void // QA7
    {
        $token = $this->tokenDe('capturista@demo.test'); // ámbito = INS_00002
        $r     = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->get('api/v1/catalogos/actividades');

        $r->assertStatus(200);
        $rows = json_decode($r->getJSON() ?? '{}', true)['data'];
        $this->assertNotEmpty($rows);
        foreach ($rows as $a) {
            $this->assertSame('INS_00002', $a['id_institucion'], 'El capturista no debe ver otras instituciones.');
        }
    }

    public function testCoordinacionVeTodasLasInstituciones(): void
    {
        $token = $this->tokenDe('coordinacion@demo.test'); // data.viewAll ⇒ global
        $r     = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->get('api/v1/catalogos/actividades');

        $r->assertStatus(200);
        $rows  = json_decode($r->getJSON() ?? '{}', true)['data'];
        $insts = array_unique(array_column($rows, 'id_institucion'));
        $this->assertGreaterThan(1, count($insts), 'Coordinación (global) ve varias instituciones.');
    }

    public function testActividadesSinTokenDevuelve401(): void
    {
        $this->get('api/v1/catalogos/actividades')->assertStatus(401);
    }

    public function testThrottleDeLoginDevuelve429(): void
    {
        cache()->clean();
        for ($i = 0; $i < 5; $i++) {
            $this->post('api/v1/auth/login', ['email' => 'x@demo.test', 'password' => 'mal']);
        }
        // La 6.ª excede el límite de 5/min/IP.
        $this->post('api/v1/auth/login', ['email' => 'x@demo.test', 'password' => 'mal'])
            ->assertStatus(429);
    }

    private function tokenDe(string $email): string
    {
        $user = model(UserModel::class)->findByCredentials(['email' => $email]);
        self::assertNotNull($user);

        return $user->generateAccessToken('test')->raw_token;
    }
}
