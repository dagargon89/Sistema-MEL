<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Services\EvidenciaService;
use CodeIgniter\HTTP\ResponseInterface;

/** Nombre normalizado de evidencia (doc 05 §11, RF-GOB-113). */
class EvidenciaController extends BaseApiController
{
    public function nombre(): ResponseInterface
    {
        $idActividad = $this->queryStr('id_actividad');
        if ($idActividad === null) {
            return $this->err(422, 'Datos inválidos.', ['id_actividad' => 'La actividad es obligatoria.']);
        }
        $ext = $this->queryStr('ext') ?? 'pdf';

        return $this->ok(['nombre' => EvidenciaService::nombre($this->queryInt('id_evento'), $idActividad, $ext)]);
    }
}
