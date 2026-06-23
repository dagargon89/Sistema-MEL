<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Services\DeduplicacionService;
use App\Support\Shape;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Participaciones nominales (con dedup en servidor) y conteos agregados
 * (doc 05 §4, RF-PART-040..046 / RF-AGRE-050..051). El cliente nunca envía
 * `id_persona` ni `control_registro`: los calcula el DeduplicacionService.
 */
class ParticipacionController extends BaseApiController
{
    public function create(): ResponseInterface
    {
        $rules = [
            'id_ejecucion'     => 'required|is_natural',
            'nombres'          => 'required|string|max_length[120]',
            'apellido_paterno' => 'required|string|max_length[80]',
            'apellido_materno' => 'permit_empty|string|max_length[80]',
            'anio_nacimiento'  => 'permit_empty|integer|greater_than_equal_to[1900]|less_than_equal_to[2026]',
            'sexo'             => 'required|in_list[F,M,X]',
            'telefono'         => 'required|string|max_length[20]',
            'correo'           => 'permit_empty|valid_email|max_length[150]',
            'colonia_persona'  => 'required|string|max_length[120]',
        ];
        if (! $this->validate($rules)) {
            return $this->err(422, 'Datos inválidos.', $this->validator?->getErrors() ?? []);
        }

        $d = $this->validator?->getValidated() ?? [];

        return $this->attempt(fn (): ResponseInterface => $this->ok((new DeduplicacionService())->crearParticipacion($d), 201));
    }

    public function agregada(): ResponseInterface
    {
        $rules = [
            'id_ejecucion'           => 'required|is_natural',
            'cantidad_participantes' => 'required|is_natural',
            'sexo_grupo'             => 'permit_empty|string|max_length[40]',
            'grupo_edad_aprox'       => 'permit_empty|string|max_length[40]',
            'motivo_no_nominal'      => 'permit_empty|string|max_length[200]',
            'fuente_conteo'          => 'permit_empty|string|max_length[200]',
            'periodo_corte'          => 'permit_empty|in_list[M01,M02,M03,M04,M05,M06,M07,M08,M09,M10,M11,M12,M13,M14,M15,M16,M17,M18]',
        ];
        if (! $this->validate($rules)) {
            return $this->err(422, 'Datos inválidos.', $this->validator?->getErrors() ?? []);
        }

        $d = $this->validator?->getValidated() ?? [];

        return $this->attempt(fn (): ResponseInterface => $this->ok(Shape::agregada((new DeduplicacionService())->crearAgregada($d)), 201));
    }
}
