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

        // --- Catálogos (Sprint 2) ---
        // Lectura: cualquier rol autenticado, acotada al ámbito.
        $routes->get('catalogos/actividades', 'ActividadController::index', ['filter' => 'throttle:read']);
        $routes->get('catalogos/ejes', 'CatalogoController::ejes', ['filter' => 'throttle:read']);
        $routes->get('catalogos/lineas', 'CatalogoController::lineas', ['filter' => 'throttle:read']);
        $routes->get('catalogos/componentes', 'CatalogoController::componentes', ['filter' => 'throttle:read']);
        $routes->get('catalogos/instituciones', 'CatalogoController::instituciones', ['filter' => 'throttle:read']);

        // Escritura de actividades: coordinación/admin; reclasificar P/E/R solo coordinación (auditado).
        $routes->post('catalogos/actividades', 'ActividadController::create', ['filter' => ['rbac:coordinacion,administrador', 'throttle:write']]);
        $routes->patch('catalogos/actividades/(:segment)/tipo-registro', 'ActividadController::reclasificar/$1', ['filter' => ['rbac:coordinacion', 'throttle:write']]);

        // --- Cadena MEL (Sprint 3): procesos → eventos → ejecuciones → participaciones ---
        // Lectura: cualquier rol autenticado, acotada al ámbito. Escritura: captura en su ámbito.
        $routes->get('procesos', 'ProcesoController::index', ['filter' => 'throttle:read']);
        $routes->post('procesos', 'ProcesoController::create', ['filter' => 'throttle:write']);

        $routes->get('eventos-programados', 'EventoController::index', ['filter' => 'throttle:read']);
        $routes->post('eventos-programados', 'EventoController::create', ['filter' => 'throttle:write']);

        $routes->get('ejecuciones', 'EjecucionController::index', ['filter' => 'throttle:read']);
        $routes->get('ejecuciones/(:num)/participaciones', 'EjecucionController::participaciones/$1', ['filter' => 'throttle:read']);
        $routes->get('ejecuciones/(:num)', 'EjecucionController::ver/$1', ['filter' => 'throttle:read']);
        $routes->post('ejecuciones', 'EjecucionController::create', ['filter' => 'throttle:write']);
        $routes->patch('ejecuciones/(:num)/validacion', 'EjecucionController::validar/$1', ['filter' => 'throttle:write']);

        $routes->post('participaciones', 'ParticipacionController::create', ['filter' => 'throttle:write']);
        $routes->post('participaciones-agregadas', 'ParticipacionController::agregada', ['filter' => 'throttle:write']);

        // Personas + cola de deduplicación: solo coordinación/admin (PII consolidada, doc 05 §5).
        $routes->get('personas', 'PersonaController::index', ['filter' => ['rbac:coordinacion,administrador', 'throttle:read']]);
        $routes->get('personas/duplicados', 'PersonaController::duplicados', ['filter' => ['rbac:coordinacion', 'throttle:read']]);
        $routes->patch('personas/duplicados/(:num)', 'PersonaController::resolver/$1', ['filter' => ['rbac:coordinacion', 'throttle:write']]);

        // Evidencias: nombre normalizado (RF-GOB-113).
        $routes->get('evidencias/nombre', 'EvidenciaController::nombre', ['filter' => 'throttle:read']);

        // --- Fase 2 · Sprint 5: metas, productos y tableros ---
        // Metas: lectura acotada al ámbito; alta solo coordinación. Seguimiento con semáforo.
        $routes->get('metas/seguimiento', 'MetaController::seguimiento', ['filter' => 'throttle:read']);
        $routes->get('metas', 'MetaController::index', ['filter' => 'throttle:read']);
        $routes->post('metas', 'MetaController::create', ['filter' => ['rbac:coordinacion', 'throttle:write']]);

        // Productos/entregables (tipo E): captura en su ámbito.
        $routes->post('productos', 'ProductoController::create', ['filter' => 'throttle:write']);

        // Tableros con KPIs reales (control=OK), acotados al ámbito.
        $routes->get('tableros/(:segment)', 'TableroController::ver/$1', ['filter' => 'throttle:read']);

        // --- Fase 3 · Sprint 6: incidencia y verticales ---
        // Incidencia (doc 05 §8): compromisos/hitos exigen proceso de incidencia válido (RN-004).
        $routes->get('incidencia/propuestas', 'IncidenciaController::propuestasIndex', ['filter' => 'throttle:read']);
        $routes->post('incidencia/propuestas', 'IncidenciaController::propuestasCreate', ['filter' => 'throttle:write']);
        $routes->get('incidencia/procesos', 'IncidenciaController::procesosIndex', ['filter' => 'throttle:read']);
        $routes->post('incidencia/procesos', 'IncidenciaController::procesosCreate', ['filter' => 'throttle:write']);
        $routes->get('incidencia/compromisos', 'IncidenciaController::compromisosIndex', ['filter' => 'throttle:read']);
        $routes->post('incidencia/compromisos', 'IncidenciaController::compromisosCreate', ['filter' => 'throttle:write']);
        $routes->get('incidencia/alianzas', 'IncidenciaController::alianzasIndex', ['filter' => 'throttle:read']);
        $routes->post('incidencia/alianzas', 'IncidenciaController::alianzasCreate', ['filter' => 'throttle:write']);
        $routes->get('incidencia/hitos', 'IncidenciaController::hitosIndex', ['filter' => 'throttle:read']);
        $routes->post('incidencia/hitos', 'IncidenciaController::hitosCreate', ['filter' => 'throttle:write']);

        // Verticales (doc 05 §9): el % de ocupación y los indicadores financieros se calculan.
        $routes->get('shelter/ocupacion', 'VerticalController::ocupacionIndex', ['filter' => 'throttle:read']);
        $routes->post('shelter/ocupacion', 'VerticalController::ocupacionCreate', ['filter' => 'throttle:write']);
        $routes->get('sostenibilidad', 'VerticalController::sostenibilidadIndex', ['filter' => 'throttle:read']);
        $routes->post('sostenibilidad', 'VerticalController::sostenibilidadCreate', ['filter' => 'throttle:write']);
    });
});
