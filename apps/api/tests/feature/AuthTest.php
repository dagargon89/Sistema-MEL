<?php

declare(strict_types=1);

use App\Database\Seeds\Sprint1Seeder;
use CodeIgniter\Shield\Models\UserModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Autenticación por token (doc 05 §2, doc 06 §2.7). Corre Shield + App en SQLite memoria.
 *
 * @internal
 */
final class AuthTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $namespace = null; // migra Shield + App
    protected $refresh   = true;
    protected $seed      = Sprint1Seeder::class;

    protected function setUp(): void
    {
        parent::setUp();
        cache()->clean(); // aísla el throttler entre pruebas
    }

    public function testLoginDevuelveTokenUsuarioYAmbito(): void
    {
        $r = $this->post('api/v1/auth/login', [
            'email'    => 'capturista@demo.test',
            'password' => Sprint1Seeder::DEV_PASSWORD,
        ]);

        $r->assertStatus(200);
        $json = json_decode($r->getJSON() ?? '{}', true);
        $this->assertTrue($json['success']);
        $this->assertNotEmpty($json['data']['token']);
        $this->assertSame('capturista', $json['data']['user']['rol']);
        $this->assertSame(['INS_00002'], $json['data']['ambito']);
    }

    public function testLoginCredencialesInvalidasDevuelve401(): void
    {
        $this->post('api/v1/auth/login', [
            'email'    => 'capturista@demo.test',
            'password' => 'incorrecta',
        ])->assertStatus(401);
    }

    public function testLoginValidacionDevuelve422(): void
    {
        $this->post('api/v1/auth/login', ['email' => 'no-es-email'])->assertStatus(422);
    }

    public function testMeSinTokenDevuelve401(): void
    {
        $this->get('api/v1/auth/me')->assertStatus(401);
    }

    public function testMeConTokenDevuelvePerfilYAmbito(): void
    {
        $token = $this->tokenDe('coordinacion@demo.test');
        $r     = $this->withHeaders(['Authorization' => 'Bearer ' . $token])->get('api/v1/auth/me');

        $r->assertStatus(200);
        $json = json_decode($r->getJSON() ?? '{}', true);
        $this->assertSame('coordinacion', $json['data']['rol']);
        $this->assertContains('INS_00001', $json['data']['ambito']);
    }

    public function testLogoutRevocaElToken(): void
    {
        $token   = $this->tokenDe('capturista@demo.test');
        $headers = ['Authorization' => 'Bearer ' . $token];

        $this->withHeaders($headers)->post('api/v1/auth/logout')->assertStatus(204);
        // Token revocado ⇒ /me con el mismo token ya no autentica.
        $this->withHeaders($headers)->get('api/v1/auth/me')->assertStatus(401);
    }

    private function tokenDe(string $email): string
    {
        $user = model(UserModel::class)->findByCredentials(['email' => $email]);
        self::assertNotNull($user);

        return $user->generateAccessToken('test')->raw_token;
    }
}
