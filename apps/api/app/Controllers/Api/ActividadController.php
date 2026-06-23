<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Repositories\ActividadRepository;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

/**
 * Lectura de actividades con herencia resuelta, acotada al ámbito del usuario
 * (RF-CAT-011, ADR-004). Demuestra auth + segmentación de punta a punta (QA7).
 * El CRUD completo de catálogos llega en el Sprint 2.
 */
class ActividadController extends BaseApiController
{
    public function index(): ResponseInterface
    {
        $ambito = Services::currentScope()->ambitoRepositorio();
        $rows   = (new ActividadRepository())->listar($ambito);

        $data = array_map(static fn (array $a): array => [
            'id_actividad'     => (string) $a['id_actividad'],
            'num_actividad'    => $a['num_actividad'] !== null ? (int) $a['num_actividad'] : null,
            'nombre'           => (string) $a['nombre'],
            'id_eje'           => (string) $a['id_eje'],
            'id_linea'         => (string) $a['id_linea'],
            'id_componente'    => (string) $a['id_componente'],
            'id_institucion'   => (string) $a['id_institucion'],
            'tipo_registro'    => (string) $a['tipo_registro'],
            'caso_excepcional' => $a['caso_excepcional'] !== null ? (string) $a['caso_excepcional'] : null,
            'herencia'         => [
                'eje'         => (string) $a['eje'],
                'linea'       => (string) $a['linea'],
                'componente'  => (string) $a['componente'],
                'institucion' => (string) $a['institucion'],
            ],
        ], $rows);

        return $this->ok($data);
    }
}
