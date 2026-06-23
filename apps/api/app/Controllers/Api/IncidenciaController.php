<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Repositories\IncidenciaRepository;
use App\Services\IncidenciaService;
use App\Support\Shape;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

/**
 * Incidencia (doc 05 §8): propuestas, procesos, compromisos, alianzas e hitos.
 * Lectura acotada al ámbito; compromisos/hitos exigen proceso de incidencia válido (RN-004).
 */
class IncidenciaController extends BaseApiController
{
    /* ---- Propuestas ---- */
    public function propuestasIndex(): ResponseInterface
    {
        $page  = $this->pageParam();
        $limit = $this->limitParam();
        $res   = (new IncidenciaRepository())->listarPropuestas(Services::currentScope()->ambitoRepositorio(), $page, $limit);

        return $this->ok(array_map(Shape::propuestaIncidencia(...), $res['rows']), 200, $this->pager($page, $limit, $res['total']));
    }

    public function propuestasCreate(): ResponseInterface
    {
        $rules = [
            'id_actividad'                    => 'required|string|max_length[8]',
            'nombre_propuesta'                => 'required|string|max_length[250]',
            'promotor_colectivo'              => 'permit_empty|string|max_length[200]',
            'tipo_actor'                      => 'permit_empty|string|max_length[80]',
            'fecha_inicio_asesoria'           => 'permit_empty|valid_date[Y-m-d]',
            'responsable_equipo'              => 'permit_empty|string|max_length[150]',
            'sesiones_documentadas'           => 'permit_empty|is_natural',
            'mejora_documentada'              => 'permit_empty',
            'cambios_resultado_asesoria'      => 'permit_empty|string',
            'evidencia_principal'             => 'permit_empty|string|max_length[500]',
            'alineada_proyectos_estrategicos' => 'permit_empty',
            'criterios_alineacion_nota'       => 'permit_empty|string|max_length[300]',
            'estatus'                         => 'permit_empty|string|max_length[40]',
            'elegible_reporte'                => 'permit_empty',
            'periodo_reporte'                 => 'permit_empty|in_list[M01,M02,M03,M04,M05,M06,M07,M08,M09,M10,M11,M12,M13,M14,M15,M16,M17,M18]',
        ];
        $d = $this->validado($rules);
        if ($d === null) {
            return $this->err(422, 'Datos inválidos.', $this->validator?->getErrors() ?? []);
        }

        return $this->attempt(fn (): ResponseInterface => $this->ok(Shape::propuestaIncidencia((new IncidenciaService())->crearPropuesta($d)), 201));
    }

    /* ---- Procesos de incidencia ---- */
    public function procesosIndex(): ResponseInterface
    {
        $page  = $this->pageParam();
        $limit = $this->limitParam();
        $res   = (new IncidenciaRepository())->listarProcesos(Services::currentScope()->ambitoRepositorio(), $page, $limit);

        return $this->ok(array_map(Shape::procesoIncidencia(...), $res['rows']), 200, $this->pager($page, $limit, $res['total']));
    }

    public function procesosCreate(): ResponseInterface
    {
        $rules = [
            'id_actividad'           => 'required|string|max_length[8]',
            'nombre'                 => 'required|string|max_length[250]',
            'criterios_elegibilidad' => 'permit_empty|string|max_length[300]',
            'ultimo_hito_resumen'    => 'permit_empty|string|max_length[300]',
        ];
        $d = $this->validado($rules);
        if ($d === null) {
            return $this->err(422, 'Datos inválidos.', $this->validator?->getErrors() ?? []);
        }

        return $this->attempt(fn (): ResponseInterface => $this->ok(Shape::procesoIncidencia((new IncidenciaService())->crearProceso($d)), 201));
    }

    /* ---- Compromisos (RN-004) ---- */
    public function compromisosIndex(): ResponseInterface
    {
        $page  = $this->pageParam();
        $limit = $this->limitParam();
        $res   = (new IncidenciaRepository())->listarCompromisos(Services::currentScope()->ambitoRepositorio(), $page, $limit);

        return $this->ok(array_map(Shape::compromiso(...), $res['rows']), 200, $this->pager($page, $limit, $res['total']));
    }

    public function compromisosCreate(): ResponseInterface
    {
        $rules = [
            'id_proceso_incidencia'   => 'required|is_natural',
            'identificacion'          => 'permit_empty|string|max_length[300]',
            'seguimiento_documentado' => 'permit_empty|string',
            'criterios_elegibilidad'  => 'permit_empty|string|max_length[300]',
        ];
        $d = $this->validado($rules);
        if ($d === null) {
            return $this->err(422, 'Datos inválidos.', $this->validator?->getErrors() ?? []);
        }

        return $this->attempt(fn (): ResponseInterface => $this->ok(Shape::compromiso((new IncidenciaService())->crearCompromiso($d)), 201));
    }

    /* ---- Alianzas ---- */
    public function alianzasIndex(): ResponseInterface
    {
        $page  = $this->pageParam();
        $limit = $this->limitParam();
        $res   = (new IncidenciaRepository())->listarAlianzas(Services::currentScope()->ambitoRepositorio(), $page, $limit);

        return $this->ok(array_map(Shape::alianza(...), $res['rows']), 200, $this->pager($page, $limit, $res['total']));
    }

    public function alianzasCreate(): ResponseInterface
    {
        $rules = [
            'id_actividad'           => 'required|string|max_length[8]',
            'nombre_alianza'         => 'required|string|max_length[250]',
            'datos_alianza'          => 'permit_empty|string',
            'criterios_elegibilidad' => 'permit_empty|string|max_length[300]',
        ];
        $d = $this->validado($rules);
        if ($d === null) {
            return $this->err(422, 'Datos inválidos.', $this->validator?->getErrors() ?? []);
        }

        return $this->attempt(fn (): ResponseInterface => $this->ok(Shape::alianza((new IncidenciaService())->crearAlianza($d)), 201));
    }

    /* ---- Hitos (RN-004) ---- */
    public function hitosIndex(): ResponseInterface
    {
        $page  = $this->pageParam();
        $limit = $this->limitParam();
        $res   = (new IncidenciaRepository())->listarHitos(Services::currentScope()->ambitoRepositorio(), $page, $limit);

        return $this->ok(array_map(Shape::hito(...), $res['rows']), 200, $this->pager($page, $limit, $res['total']));
    }

    public function hitosCreate(): ResponseInterface
    {
        $rules = [
            'id_proceso_incidencia'   => 'required|is_natural',
            'fecha_hito'              => 'permit_empty|valid_date[Y-m-d]',
            'tipo_hito'               => 'permit_empty|string|max_length[80]',
            'descripcion_hito'        => 'permit_empty|string',
            'evidencia_nombre_o_nota' => 'permit_empty|string|max_length[300]',
            'observaciones'           => 'permit_empty|string',
        ];
        $d = $this->validado($rules);
        if ($d === null) {
            return $this->err(422, 'Datos inválidos.', $this->validator?->getErrors() ?? []);
        }

        return $this->attempt(fn (): ResponseInterface => $this->ok(Shape::hito((new IncidenciaService())->crearHito($d)), 201));
    }

    /**
     * Valida y devuelve los datos saneados, o null si falla (el llamador responde 422).
     *
     * @param array<string, string> $rules
     *
     * @return array<string, mixed>|null
     */
    private function validado(array $rules): ?array
    {
        if (! $this->validate($rules)) {
            return null;
        }

        return $this->validator?->getValidated() ?? [];
    }
}
