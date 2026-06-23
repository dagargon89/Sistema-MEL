<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ApiException;
use App\Repositories\CadenaRepository;
use App\Support\FieldCast;
use Config\Services;

/**
 * Verticales (doc 05 §9, doc 03 §3.6). Ocupación de shelter y sostenibilidad financiera.
 * Los indicadores derivados (% ocupación, utilidad, % avance, semáforo) NO se almacenan:
 * se calculan al dar forma a la respuesta (Shape). Acotado al ámbito (ADR-004).
 */
class VerticalService
{
    use FieldCast;

    private CadenaRepository $cadena;
    private AuditoriaService $auditoria;

    public function __construct()
    {
        $this->cadena    = new CadenaRepository();
        $this->auditoria = new AuditoriaService();
    }

    /**
     * @param array<string, mixed> $d
     *
     * @return array<string, mixed>
     */
    public function crearOcupacion(array $d): array
    {
        $idActividad = $this->str($d, 'id_actividad') ?? '';
        $this->exigirActividad($idActividad);

        $row = [
            'id_actividad'        => $idActividad,
            'mes_periodo'         => $this->str($d, 'mes_periodo'),
            'tipo_espacio'        => $this->str($d, 'tipo_espacio'),
            'capacidad_instalada' => $this->intOrNull($d, 'capacidad_instalada') ?? 0,
            'ocupacion'           => $this->intOrNull($d, 'ocupacion') ?? 0,
            'fuente'              => $this->str($d, 'fuente'),
            'control_registro'    => 'AGREGADO',
        ];

        return $this->insertar('ocupacion_shelter', $row, 'id_ocupacion');
    }

    /**
     * @param array<string, mixed> $d
     *
     * @return array<string, mixed>
     */
    public function crearSostenibilidad(array $d): array
    {
        $idActividad = $this->str($d, 'id_actividad') ?? '';
        $this->exigirActividad($idActividad);

        $row = [
            'id_actividad'      => $idActividad,
            'mes_periodo'       => $this->str($d, 'mes_periodo'),
            'ingresos_brutos'   => $this->str($d, 'ingresos_brutos') ?? '0',
            'costos_directos'   => $this->str($d, 'costos_directos') ?? '0',
            'costos_indirectos' => $this->str($d, 'costos_indirectos') ?? '0',
            'recursos_efectivo' => $this->str($d, 'recursos_efectivo') ?? '0',
            'recursos_especie'  => $this->str($d, 'recursos_especie') ?? '0',
            'fuente_datos'      => $this->str($d, 'fuente_datos'),
            'meta_anual'        => $this->str($d, 'meta_anual') ?? '0',
            'control_registro'  => 'AGREGADO',
        ];

        return $this->insertar('sostenibilidad_financiera', $row, 'id_registro');
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return array<string, mixed>
     */
    private function insertar(string $tabla, array $row, string $pk): array
    {
        $db = db_connect();
        $db->transStart();
        $db->table($tabla)->insert($row);
        $id = (int) $db->insertID();
        $this->auditoria->registrar($tabla, (string) $id, 'alta', null, ['control_registro' => $row['control_registro'] ?? null]);
        $db->transComplete();

        $row[$pk] = $id;

        return $row;
    }

    private function exigirActividad(string $idActividad): void
    {
        $inst = $this->cadena->institucionDeActividad($idActividad);
        if ($inst === null) {
            throw ApiException::unprocessable('Datos inválidos.', ['id_actividad' => 'Actividad inexistente.']);
        }
        if (! Services::currentScope()->cubre($inst)) {
            throw ApiException::forbidden('Fuera de su ámbito de institución.');
        }
    }
}
