<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\ActividadModel;
use App\Models\ResultadoModel;
use App\Support\FieldCast;
use Config\Services;

/**
 * Resultados tipo R (doc 05 §10, RF-RES-100). Solo actividades tipo R (422 si no),
 * acotado al ámbito y auditado.
 */
class ResultadoService
{
    use FieldCast;

    private AuditoriaService $auditoria;

    public function __construct()
    {
        $this->auditoria = new AuditoriaService();
    }

    /**
     * @param array<string, mixed> $d
     *
     * @return array<string, mixed>
     */
    public function crearResultado(array $d): array
    {
        $idActividad = $this->str($d, 'id_actividad') ?? '';
        $act         = (new ActividadModel())->find($idActividad);
        if (! is_array($act)) {
            throw ApiException::unprocessable('Datos inválidos.', ['id_actividad' => 'Actividad inexistente.']);
        }
        if (($act['tipo_registro'] ?? null) !== 'R') {
            throw ApiException::unprocessable('Datos inválidos.', ['id_actividad' => 'Solo las actividades tipo R admiten resultados.']);
        }
        if (! Services::currentScope()->cubre(is_string($act['id_institucion'] ?? null) ? $act['id_institucion'] : null)) {
            throw ApiException::forbidden('Fuera de su ámbito de institución.');
        }

        $row = [
            'id_actividad'    => $idActividad,
            'indicador'       => $this->str($d, 'indicador'),
            'linea_base'      => $this->str($d, 'linea_base'),
            'valor_medido'    => $this->str($d, 'valor_medido'),
            'metodo_medicion' => $this->str($d, 'metodo_medicion'),
            'fecha_medicion'  => $this->str($d, 'fecha_medicion'),
            'evidencia_url'   => $this->str($d, 'evidencia_url'),
        ];

        $model = new ResultadoModel();
        $model->insert($row);
        $id = $model->getInsertID();
        $this->auditoria->registrar('resultados', (string) $id, 'alta', null, ['indicador' => $row['indicador']]);

        /** @var array<string, mixed>|null $created */
        $created = $model->find($id);
        if (is_array($created)) {
            return $created;
        }
        $row['id_resultado'] = $id;

        return $row;
    }
}
