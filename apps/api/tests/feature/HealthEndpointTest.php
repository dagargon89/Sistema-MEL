<?php

declare(strict_types=1);

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * GET /api/v1/health responde 200 JSON (sin BD). Hito Sprint 0: "la API responde".
 *
 * @internal
 */
final class HealthEndpointTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    public function testHealthReturnsOkJson(): void
    {
        $result = $this->get('api/v1/health');

        $result->assertStatus(200);
        $result->assertJSONFragment([
            'status'  => 'ok',
            'service' => 'sistema-mel-api',
            'version' => 'v1',
        ]);
    }
}
