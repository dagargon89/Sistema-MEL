<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Cross-Origin Resource Sharing (CORS) — Sistema MEL.
 *
 * El origen permitido (la SPA) se toma de la variable de entorno
 * `CORS_ALLOWED_ORIGINS` (lista separada por comas). En desarrollo cae por
 * defecto a la SPA local de Vite. Usamos access tokens Bearer (no cookies),
 * por eso `supportsCredentials` queda en false (doc 04, ADR-002).
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
 */
class Cors extends BaseConfig
{
    /**
     * @var array{
     *      allowedOrigins: list<string>,
     *      allowedOriginsPatterns: list<string>,
     *      supportsCredentials: bool,
     *      allowedHeaders: list<string>,
     *      exposedHeaders: list<string>,
     *      allowedMethods: list<string>,
     *      maxAge: int,
     *  }
     */
    public array $default = [
        'allowedOrigins'         => ['http://localhost:5173'],
        'allowedOriginsPatterns' => [],
        'supportsCredentials'    => false,
        'allowedHeaders'         => ['Content-Type', 'Authorization', 'X-Requested-With'],
        'exposedHeaders'         => [],
        'allowedMethods'         => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
        'maxAge'                 => 7200,
    ];

    public function __construct()
    {
        parent::__construct();

        $origins = env('CORS_ALLOWED_ORIGINS');
        if (is_string($origins) && $origins !== '') {
            $this->default['allowedOrigins'] = array_values(
                array_filter(array_map('trim', explode(',', $origins))),
            );
        }
    }
}
