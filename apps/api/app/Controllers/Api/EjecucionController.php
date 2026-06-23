<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Repositories\CadenaRepository;
use App\Services\CadenaService;
use App\Support\Shape;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

/**
 * Ejecuciones reales y su máquina de estados (doc 05 §4, RF-EJEC-030..033).
 * El alta valida la cadena y calcula `control_registro` en servidor; la validación
 * mueve el estado según transiciones legales. Todo acotado al ámbito (ADR-004).
 */
class EjecucionController extends BaseApiController
{
    public function index(): ResponseInterface
    {
        $page  = $this->pageParam();
        $limit = $this->limitParam();

        $filtros = [
            'id_evento_programado' => $this->queryInt('id_evento_programado'),
            'control'              => $this->queryStr('control'),
        ];

        $ambito = Services::currentScope()->ambitoRepositorio();
        $res    = (new CadenaRepository())->listarEjecuciones($ambito, $filtros, $page, $limit);

        $data = array_map(Shape::ejecucion(...), $res['rows']);

        return $this->ok($data, 200, $this->pager($page, $limit, $res['total']));
    }

    public function ver(string $id): ResponseInterface
    {
        $repo = new CadenaRepository();
        $ejec = $repo->findEjecucion((int) $id);
        if ($ejec === null || ! Services::currentScope()->cubre($repo->institucionDeEjecucion((int) $id))) {
            return $this->err(404, 'Ejecución inexistente.');
        }

        return $this->ok(Shape::ejecucion($ejec));
    }

    public function create(): ResponseInterface
    {
        $rules = [
            'id_evento_programado'        => 'required|is_natural',
            'fecha_ejecucion_real'        => 'permit_empty|valid_date[Y-m-d]',
            'hora_inicio_real'            => 'permit_empty|string',
            'hora_finalizacion_real'      => 'permit_empty|string',
            'lugar_real'                  => 'permit_empty|string|max_length[200]',
            'colonia_real'                => 'permit_empty|string|max_length[120]',
            'responsable_real'            => 'permit_empty|string|max_length[150]',
            'estatus_ejecucion'           => 'permit_empty|in_list[ejecutada,suspendida,parcial]',
            'tipo_registro_participacion' => 'required|in_list[Nominal,Agregado,Mixta]',
            'evidencia_url'               => 'permit_empty|string|max_length[500]',
            'resumen_narrativo'           => 'permit_empty|string',
            'observaciones'               => 'permit_empty|string',
        ];
        if (! $this->validate($rules)) {
            return $this->err(422, 'Datos inválidos.', $this->validator?->getErrors() ?? []);
        }

        $d = $this->validator?->getValidated() ?? [];

        return $this->attempt(fn (): ResponseInterface => $this->ok((new CadenaService())->crearEjecucion($d), 201));
    }

    public function validar(string $id): ResponseInterface
    {
        $rules = [
            'control_registro' => 'required|in_list[CAPTURADO,INCOMPLETO,REVISAR,OK,AGREGADO]',
            'detalle'          => 'permit_empty|string|max_length[300]',
        ];
        if (! $this->validate($rules)) {
            return $this->err(422, 'Datos inválidos.', $this->validator?->getErrors() ?? []);
        }

        $d = $this->validator?->getValidated() ?? [];

        return $this->attempt(fn (): ResponseInterface => $this->ok(Shape::ejecucion((new CadenaService())->validarEjecucion((int) $id, $d))));
    }

    public function participaciones(string $id): ResponseInterface
    {
        $repo = new CadenaRepository();
        if ($repo->findEjecucion((int) $id) === null || ! Services::currentScope()->cubre($repo->institucionDeEjecucion((int) $id))) {
            return $this->err(404, 'Ejecución inexistente.');
        }

        $page  = $this->pageParam();
        $limit = $this->limitParam();
        $res   = $repo->listarParticipacionesDeEjecucion((int) $id, $page, $limit);

        $data = array_map(Shape::participacion(...), $res['rows']);

        return $this->ok($data, 200, $this->pager($page, $limit, $res['total']));
    }
}
