<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

/*
 * API REST JSON (doc 05). Toda la API vive bajo /api/v1.
 * Autenticación: access tokens de Shield (Authorization: Bearer), filtro `tokens`.
 * No se usan las rutas web de Shield (`service('auth')->routes()`): la API gestiona
 * su propia sesión por token en AuthController.
 */
$routes->group('api/v1', ['namespace' => 'App\Controllers\Api'], static function ($routes): void {
    // --- Públicas (sin token) ---
    $routes->get('health', 'Health::index');
    $routes->post('auth/login', 'AuthController::login', ['filter' => 'throttle:login']);

    // --- Protegidas: token + ámbito de institución (denegación por defecto) ---
    $routes->group('', ['filter' => ['tokens', 'scope-institucion']], static function ($routes): void {
        $routes->post('auth/logout', 'AuthController::logout');
        $routes->get('auth/me', 'AuthController::me');

        // Catálogos (lectura para cualquier rol autenticado, acotada al ámbito).
        $routes->get('catalogos/actividades', 'ActividadController::index', ['filter' => 'throttle:read']);
    });
});
