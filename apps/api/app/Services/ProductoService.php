<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\ActividadModel;
use App\Support\FieldCast;
use Config\Services;

/**
 * Productos/entregables tipo E (doc 05 §6, RF-PROD-060..062). Bloquea actividades
 * que no sean tipo E (RN-020), acota al ámbito y calcula `control_registro` en servidor.
 */
class ProductoService
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
    public function crearProducto(array $d): array
    {
        $idActividad = $this->str($d, 'id_actividad') ?? '';
        $act         = (new ActividadModel())->find($idActividad);
        if (! is_array($act)) {
            throw ApiException::unprocessable('Datos inválidos.', ['id_actividad' => 'Actividad inexistente.']);
        }
        if (($act['tipo_registro'] ?? null) !== 'E') {
            throw ApiException::unprocessable('Datos inválidos.', ['id_actividad' => 'Solo las actividades tipo E admiten productos/entregables.']);
        }
        if (! Services::currentScope()->cubre(is_string($act['id_institucion'] ?? null) ? $act['id_institucion'] : null)) {
            throw ApiException::forbidden('Fuera de su ámbito de institución.');
        }

        $nombre   = $this->str($d, 'nombre_producto');
        $estatus  = $this->str($d, 'estatus') ?? 'en_proceso';
        $evidUrl  = $this->str($d, 'evidencia_url');
        $completo = $nombre !== null && $evidUrl !== null;
        $archivo  = $evidUrl !== null ? EvidenciaService::nombre(null, $idActividad, 'pdf') : null;

        $row = [
            'id_actividad'             => $idActividad,
            'nombre_producto'          => $nombre,
            'tipo_producto'            => $this->str($d, 'tipo_producto'),
            'fecha_inicio'             => $this->str($d, 'fecha_inicio'),
            'fecha_entrega'            => $this->str($d, 'fecha_entrega'),
            'responsable'              => $this->str($d, 'responsable'),
            'cantidad'                 => $this->intOrNull($d, 'cantidad'),
            'unidad_medida'            => $this->str($d, 'unidad_medida'),
            'estatus'                  => $estatus,
            'descripcion'              => $this->str($d, 'descripcion'),
            'evidencia_url'            => $evidUrl,
            'nombre_archivo_evidencia' => $archivo,
            'control_registro'         => $completo ? 'OK' : 'INCOMPLETO',
        ];

        $db = db_connect();
        $db->transStart();
        $db->table('productos_entregables')->insert($row);
        $id = (int) $db->insertID();
        $this->auditoria->registrar('productos_entregables', (string) $id, 'alta', null, ['control_registro' => $row['control_registro']]);
        $db->transComplete();

        $row['id_producto'] = $id;

        return $row;
    }
}
