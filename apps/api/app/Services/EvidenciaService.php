<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Genera el nombre normalizado de una evidencia (RF-GOB-113):
 * `CPJ_EVID_[fecha]_[id_evento]_[actividad]_[consecutivo].[ext]`.
 * Espeja `generarNombreEvidencia()` del mock; usado por la ejecución y el endpoint.
 */
class EvidenciaService
{
    public static function nombre(?int $idEvento, string $idActividad, string $ext): string
    {
        $fecha = date('Ymd');
        $act   = str_replace('_', '', $idActividad);
        $ev    = $idEvento ?? 'NA';
        $ext   = preg_replace('/[^A-Za-z0-9]/', '', $ext) ?? 'pdf';

        return "CPJ_EVID_{$fecha}_{$ev}_{$act}_001.{$ext}";
    }
}
