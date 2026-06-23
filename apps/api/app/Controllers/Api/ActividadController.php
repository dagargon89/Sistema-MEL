<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\ActividadModel;
use App\Repositories\ActividadRepository;
use App\Services\AuditoriaService;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

/**
 * Catálogo de actividades (RF-CAT-010..013, doc 05 §3): lista paginada con herencia
 * resuelta y acotada al ámbito, alta (coordinación/admin) y reclasificación P/E/R
 * (coordinación, auditada). El CRUD completo de las dimensiones es de solo lectura
 * (CatalogoController); la carga de las 236 actividades llega con `mel:import` (Sprint 4).
 */
class ActividadController extends BaseApiController
{
    public function index(): ResponseInterface
    {
        $pageRaw  = $this->request->getGet('page');
        $limitRaw = $this->request->getGet('limit');
        $page     = max(1, is_numeric($pageRaw) ? (int) $pageRaw : 1);
        $limit    = min(100, max(1, is_numeric($limitRaw) ? (int) $limitRaw : 15));

        $filtros = [
            'tipo'        => $this->getStr('tipo'),
            'caso'        => $this->getStr('caso'),
            'institucion' => $this->getStr('institucion'),
        ];

        $ambito = Services::currentScope()->ambitoRepositorio();
        $res    = (new ActividadRepository())->listarPaginado($ambito, $filtros, $page, $limit);

        $data  = array_map([$this, 'forma'], $res['rows']);
        $total = $res['total'];
        $pager = [
            'currentPage' => $page,
            'perPage'     => $limit,
            'total'       => $total,
            'pageCount'   => $total > 0 ? (int) ceil($total / $limit) : 1,
        ];

        return $this->ok($data, 200, $pager);
    }

    public function create(): ResponseInterface
    {
        $rules = [
            'id_actividad'     => 'required|string|max_length[8]|is_unique[actividades.id_actividad]',
            'nombre'           => 'required|string|max_length[300]',
            'id_eje'           => 'required|is_not_unique[ejes.id_eje]',
            'id_linea'         => 'required|is_not_unique[lineas.id_linea]',
            'id_componente'    => 'required|is_not_unique[componentes.id_componente]',
            'id_institucion'   => 'required|is_not_unique[instituciones.id_institucion]',
            'tipo_registro'    => 'required|in_list[P,E,R]',
            'caso_excepcional' => 'permit_empty|in_list[A,B,C,D]',
            'num_actividad'    => 'permit_empty|is_natural',
        ];
        if (! $this->validate($rules)) {
            return $this->err(422, 'Datos inválidos.', $this->validator?->getErrors() ?? []);
        }

        $d     = $this->validator?->getValidated() ?? [];
        $nueva = [
            'id_actividad'     => $this->campoStr($d, 'id_actividad'),
            'num_actividad'    => $this->campoInt($d, 'num_actividad'),
            'nombre'           => $this->campoStr($d, 'nombre'),
            'id_eje'           => $this->campoStr($d, 'id_eje'),
            'id_linea'         => $this->campoStr($d, 'id_linea'),
            'id_componente'    => $this->campoStr($d, 'id_componente'),
            'id_institucion'   => $this->campoStr($d, 'id_institucion'),
            'tipo_registro'    => $this->campoStr($d, 'tipo_registro'),
            'caso_excepcional' => $this->campoStr($d, 'caso_excepcional'),
        ];

        (new ActividadModel())->insert($nueva);
        (new AuditoriaService())->registrar('actividades', (string) $nueva['id_actividad'], 'alta', null, $nueva);

        return $this->ok($nueva, 201);
    }

    public function reclasificar(string $id): ResponseInterface
    {
        if (! $this->validate(['tipo_registro' => 'required|in_list[P,E,R]', 'motivo' => 'required|string|min_length[3]'])) {
            return $this->err(422, 'Datos inválidos.', $this->validator?->getErrors() ?? []);
        }

        $model = new ActividadModel();
        $act   = $model->find($id);
        if (! is_array($act)) {
            return $this->err(404, 'Actividad inexistente.');
        }

        $d         = $this->validator?->getValidated() ?? [];
        $nuevoTipo = $this->campoStr($d, 'tipo_registro') ?? 'P';
        $motivo    = $this->campoStr($d, 'motivo') ?? '';

        // Sprint 3 añadirá el bloqueo 409 si $nuevoTipo === 'E' y la actividad ya tiene ejecuciones (RN-021).
        $antes = ['tipo_registro' => $act['tipo_registro'] ?? null];
        $model->update($id, ['tipo_registro' => $nuevoTipo]);
        (new AuditoriaService())->registrar('actividades', $id, 'reclasificacion', $antes, ['tipo_registro' => $nuevoTipo, 'motivo' => $motivo]);

        $act['tipo_registro'] = $nuevoTipo;

        return $this->ok($act);
    }

    private function getStr(string $key): ?string
    {
        $v = $this->request->getGet($key);

        return is_string($v) && $v !== '' ? $v : null;
    }

    /**
     * @param array<string, mixed> $a
     *
     * @return array<string, mixed>
     */
    private function forma(array $a): array
    {
        return [
            'id_actividad'     => $this->campoStr($a, 'id_actividad') ?? '',
            'num_actividad'    => $this->campoInt($a, 'num_actividad'),
            'nombre'           => $this->campoStr($a, 'nombre') ?? '',
            'id_eje'           => $this->campoStr($a, 'id_eje') ?? '',
            'id_linea'         => $this->campoStr($a, 'id_linea') ?? '',
            'id_componente'    => $this->campoStr($a, 'id_componente') ?? '',
            'id_institucion'   => $this->campoStr($a, 'id_institucion') ?? '',
            'tipo_registro'    => $this->campoStr($a, 'tipo_registro') ?? '',
            'caso_excepcional' => $this->campoStr($a, 'caso_excepcional'),
            'herencia'         => [
                'eje'         => $this->campoStr($a, 'eje') ?? '',
                'linea'       => $this->campoStr($a, 'linea') ?? '',
                'componente'  => $this->campoStr($a, 'componente') ?? '',
                'institucion' => $this->campoStr($a, 'institucion') ?? '',
            ],
        ];
    }
}
