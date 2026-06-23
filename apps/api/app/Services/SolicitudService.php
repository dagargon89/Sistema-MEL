<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\ApiException;
use App\Models\SolicitudModel;
use App\Support\FieldCast;
use Config\Services;

/**
 * Gobernanza: solicitudes de corrección/mejora/ajuste (doc 05 §11, RF-GOB-110/111).
 * Cualquier usuario registra; solo coordinación resuelve (RBAC en la ruta). Auditado.
 */
class SolicitudService
{
    use FieldCast;

    private AuditoriaService $auditoria;

    public function __construct()
    {
        $this->auditoria = new AuditoriaService();
    }

    /** @return array{rows: list<array<string, mixed>>, total: int} */
    public function listar(?string $estado, int $page, int $limit): array
    {
        $builder = db_connect()->table('solicitudes');
        if ($estado !== null) {
            $builder->where('estado', $estado);
        }
        $total  = (int) $builder->countAllResults(false);
        $result = $builder->orderBy('id_solicitud', 'DESC')->get($limit, ($page - 1) * $limit);

        /** @var list<array<string, mixed>> $rows */
        $rows = $result === false ? [] : $result->getResultArray();

        return ['rows' => $rows, 'total' => $total];
    }

    /**
     * @param array<string, mixed> $d
     *
     * @return array<string, mixed>
     */
    public function crearSolicitud(array $d): array
    {
        $uid = auth('tokens')->id();
        $row = [
            'fecha_solicitud'      => date('Y-m-d H:i:s'),
            'id_solicitante'       => is_int($uid) || is_string($uid) ? $uid : null,
            'rol_solicitante'      => Services::currentScope()->rol(),
            'entidad_afectada'     => $this->str($d, 'entidad_afectada'),
            'descripcion'          => $this->str($d, 'descripcion'),
            'tipo_solicitud'       => $this->str($d, 'tipo_solicitud'),
            'nivel_criticidad'     => $this->str($d, 'nivel_criticidad') ?? 'MEDIA',
            'impacto'              => $this->str($d, 'impacto'),
            'estado'               => 'en_revision',
            'responsable_atencion' => null,
            'fecha_resolucion'     => null,
            'comentarios'          => null,
        ];

        $db = db_connect();
        $db->transStart();
        $db->table('solicitudes')->insert($row);
        $id = (int) $db->insertID();
        $this->auditoria->registrar('solicitudes', (string) $id, 'alta', null, ['estado' => 'en_revision']);
        $db->transComplete();

        $row['id_solicitud'] = $id;

        return $row;
    }

    /**
     * @param array<string, mixed> $d
     *
     * @return array<string, mixed>
     */
    public function resolverSolicitud(int $id, array $d): array
    {
        $model = new SolicitudModel();
        /** @var array<string, mixed>|null $sol */
        $sol = $model->find($id);
        if (! is_array($sol)) {
            throw ApiException::notFound('Solicitud inexistente.');
        }

        $estado = $this->str($d, 'estado') ?? 'en_revision';
        $antes  = ['estado' => $sol['estado'] ?? null];
        $update = [
            'estado'               => $estado,
            'responsable_atencion' => $this->intOrNull($d, 'responsable_atencion'),
            'comentarios'          => $this->str($d, 'comentarios'),
            'fecha_resolucion'     => $estado === 'resuelta' ? date('Y-m-d H:i:s') : ($sol['fecha_resolucion'] ?? null),
        ];

        $db = db_connect();
        $db->transStart();
        $db->table('solicitudes')->where('id_solicitud', $id)->update($update);
        $this->auditoria->registrar('solicitudes', (string) $id, 'edicion', $antes, ['estado' => $estado]);
        $db->transComplete();

        /** @var array<string, mixed>|null $actualizada */
        $actualizada = $model->find($id);

        return is_array($actualizada) ? $actualizada : array_merge($sol, $update);
    }
}
