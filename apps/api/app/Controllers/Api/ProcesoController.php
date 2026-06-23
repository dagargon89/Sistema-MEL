<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Repositories\CadenaRepository;
use App\Services\CadenaService;
use App\Support\Shape;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

/** Procesos/grupos de la cadena MEL (doc 05 §4, RF-PROG-020). Acotado al ámbito (ADR-004). */
class ProcesoController extends BaseApiController
{
    public function index(): ResponseInterface
    {
        $page  = $this->pageParam();
        $limit = $this->limitParam();

        $ambito = Services::currentScope()->ambitoRepositorio();
        $res    = (new CadenaRepository())->listarProcesos($ambito, $this->queryStr('id_actividad'), $page, $limit);

        $data = array_map(Shape::proceso(...), $res['rows']);

        return $this->ok($data, 200, $this->pager($page, $limit, $res['total']));
    }

    public function create(): ResponseInterface
    {
        $rules = [
            'nombre'                     => 'required|string|max_length[250]',
            'tipo_programacion'          => 'required|in_list[SESION_UNICA,MULTI_SESION_PROGRAMADA,PROCESO_CONTINUO]',
            'id_actividad'               => 'required|string|max_length[8]',
            'fecha_inicio'               => 'permit_empty|valid_date[Y-m-d]',
            'fecha_fin'                  => 'permit_empty|valid_date[Y-m-d]',
            'total_sesiones_programadas' => 'permit_empty|is_natural',
            'responsable'                => 'permit_empty|string|max_length[150]',
            'contacto'                   => 'permit_empty|string|max_length[150]',
            'observaciones'              => 'permit_empty|string',
        ];
        if (! $this->validate($rules)) {
            return $this->err(422, 'Datos inválidos.', $this->validator?->getErrors() ?? []);
        }

        $d = $this->validator?->getValidated() ?? [];

        return $this->attempt(fn (): ResponseInterface => $this->ok(Shape::proceso((new CadenaService())->crearProceso($d)), 201));
    }
}
