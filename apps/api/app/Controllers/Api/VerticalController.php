<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Repositories\VerticalRepository;
use App\Services\VerticalService;
use App\Support\Shape;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

/**
 * Verticales (doc 05 §9): ocupación de shelter y sostenibilidad financiera. Los
 * indicadores derivados se calculan al dar forma a la respuesta. Acotado al ámbito.
 */
class VerticalController extends BaseApiController
{
    public function ocupacionIndex(): ResponseInterface
    {
        $page  = $this->pageParam();
        $limit = $this->limitParam();
        $res   = (new VerticalRepository())->listarOcupacion(Services::currentScope()->ambitoRepositorio(), $page, $limit);

        return $this->ok(array_map(Shape::ocupacion(...), $res['rows']), 200, $this->pager($page, $limit, $res['total']));
    }

    public function ocupacionCreate(): ResponseInterface
    {
        $rules = [
            'id_actividad'        => 'required|string|max_length[8]',
            'mes_periodo'         => 'required|in_list[M01,M02,M03,M04,M05,M06,M07,M08,M09,M10,M11,M12,M13,M14,M15,M16,M17,M18]',
            'tipo_espacio'        => 'permit_empty|string|max_length[80]',
            'capacidad_instalada' => 'required|is_natural',
            'ocupacion'           => 'required|is_natural',
            'fuente'              => 'permit_empty|string|max_length[200]',
        ];
        if (! $this->validate($rules)) {
            return $this->err(422, 'Datos inválidos.', $this->validator?->getErrors() ?? []);
        }
        $d = $this->validator?->getValidated() ?? [];

        return $this->attempt(fn (): ResponseInterface => $this->ok(Shape::ocupacion((new VerticalService())->crearOcupacion($d)), 201));
    }

    public function sostenibilidadIndex(): ResponseInterface
    {
        $page  = $this->pageParam();
        $limit = $this->limitParam();
        $res   = (new VerticalRepository())->listarSostenibilidad(Services::currentScope()->ambitoRepositorio(), $page, $limit);

        return $this->ok(array_map(Shape::sostenibilidad(...), $res['rows']), 200, $this->pager($page, $limit, $res['total']));
    }

    public function sostenibilidadCreate(): ResponseInterface
    {
        $rules = [
            'id_actividad'      => 'required|string|max_length[8]',
            'mes_periodo'       => 'required|in_list[M01,M02,M03,M04,M05,M06,M07,M08,M09,M10,M11,M12,M13,M14,M15,M16,M17,M18]',
            'ingresos_brutos'   => 'permit_empty|numeric',
            'costos_directos'   => 'permit_empty|numeric',
            'costos_indirectos' => 'permit_empty|numeric',
            'recursos_efectivo' => 'permit_empty|numeric',
            'recursos_especie'  => 'permit_empty|numeric',
            'fuente_datos'      => 'permit_empty|string|max_length[200]',
            'meta_anual'        => 'permit_empty|numeric',
        ];
        if (! $this->validate($rules)) {
            return $this->err(422, 'Datos inválidos.', $this->validator?->getErrors() ?? []);
        }
        $d = $this->validator?->getValidated() ?? [];

        return $this->attempt(fn (): ResponseInterface => $this->ok(Shape::sostenibilidad((new VerticalService())->crearSostenibilidad($d)), 201));
    }
}
