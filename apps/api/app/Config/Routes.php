<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

/*
 * API REST JSON (doc 05). Toda la API vive bajo /api/v1.
 * Los recursos de dominio (catálogos, cadena MEL, metas, gobernanza) se
 * registran en sus sprints detrás de los filtros auth/rbac/scope-institucion.
 */
$routes->group('api/v1', ['namespace' => 'App\Controllers\Api'], static function ($routes): void {
    $routes->get('health', 'Health::index');
});
