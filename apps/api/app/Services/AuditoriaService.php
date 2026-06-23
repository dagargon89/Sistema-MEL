<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Bitácora append-only (RF-GOB-112). Toda escritura relevante registra quién/qué/cuándo
 * + valor antes/después. Espeja `auditar()` del mock. Lo usan la reclasificación de
 * actividades (Sprint 2) y los servicios de los sprints siguientes.
 */
class AuditoriaService
{
    /**
     * @param array<string, mixed>|null $antes
     * @param array<string, mixed>|null $despues
     */
    public function registrar(string $entidad, string $idRegistro, string $accion, ?array $antes, ?array $despues): void
    {
        $uid = auth('tokens')->id();

        db_connect()->table('auditoria')->insert([
            'fecha_hora'    => date('Y-m-d H:i:s'),
            'id_usuario'    => is_int($uid) || is_string($uid) ? $uid : null,
            'entidad'       => $entidad,
            'id_registro'   => $idRegistro,
            'accion'        => $accion,
            'valor_antes'   => $antes === null ? null : json_encode($antes),
            'valor_despues' => $despues === null ? null : json_encode($despues),
        ]);
    }
}
