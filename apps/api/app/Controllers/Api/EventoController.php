<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Repositories\CadenaRepository;
use App\Services\CadenaService;
use App\Support\Shape;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

/**
 * Eventos programados — calendario del universo planeado (doc 05 §4, RF-PROG-021..023).
 * Acotado al ámbito; el alta exige proceso en multisesión y rechaza fechas invertidas.
 */
class EventoController extends BaseApiController
{
    public function index(): ResponseInterface
    {
        $page  = $this->pageParam();
        $limit = $this->limitParam();

        $filtros = [
            'id_actividad' => $this->queryStr('id_actividad'),
            'institucion'  => $this->queryStr('institucion'),
            'responsable'  => $this->queryStr('responsable'),
            'estatus'      => $this->queryStr('estatus'),
        ];

        $ambito = Services::currentScope()->ambitoRepositorio();
        $res    = (new CadenaRepository())->listarEventos($ambito, $filtros, $page, $limit);

        $data = array_map(Shape::evento(...), $res['rows']);

        return $this->ok($data, 200, $this->pager($page, $limit, $res['total']));
    }

    public function create(): ResponseInterface
    {
        $rules = [
            'id_actividad'       => 'required|string|max_length[8]',
            'id_proceso'         => 'permit_empty|is_natural',
            'tipo_programacion'  => 'required|in_list[SESION_UNICA,MULTI_SESION_PROGRAMADA,PROCESO_CONTINUO]',
            'fecha_inicio'       => 'required|valid_date[Y-m-d]',
            'fecha_finalizacion' => 'required|valid_date[Y-m-d]',
            'hora_inicio'        => 'permit_empty|string',
            'hora_finalizacion'  => 'permit_empty|string',
            'modalidad'          => 'permit_empty|string|max_length[60]',
            'lugar'              => 'permit_empty|string|max_length[200]',
            'calle_y_numero'     => 'permit_empty|string|max_length[200]',
            'colonia'            => 'permit_empty|string|max_length[120]',
            'responsable'        => 'permit_empty|string|max_length[150]',
            'contacto'           => 'permit_empty|string|max_length[150]',
        ];
        if (! $this->validate($rules)) {
            return $this->err(422, 'Datos inválidos.', $this->validator?->getErrors() ?? []);
        }

        $d = $this->validator?->getValidated() ?? [];

        return $this->attempt(fn (): ResponseInterface => $this->ok(Shape::evento((new CadenaService())->crearEvento($d)), 201));
    }
}
