<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\TableroRepository;
use Config\Services;

/**
 * Tableros con KPIs reales (doc 05 §12, RF-TAB-120). Todo se calcula en vivo sobre
 * `control_registro = OK` y acotado al ámbito (ADR-004); ningún conteo proviene de
 * filas-plantilla. Espeja el `tablero()` del mock (mismas cifras por construcción).
 */
class TableroService
{
    /**
     * @return array{beneficiarios_unicos:int, participaciones_nominales:int, participaciones_agregadas:int, cobertura_total:int, eventos_programados:int, ejecuciones:int, cumplimiento_ejecucion:float}
     */
    public function tablero(): array
    {
        $ambito = Services::currentScope()->ambitoRepositorio();
        $repo   = new TableroRepository();

        $beneficiarios = $repo->beneficiariosUnicos($ambito);
        $nominales     = $repo->nominales($ambito);
        $agregadas     = $repo->agregadasSuma($ambito);
        $eventos       = $repo->eventosProgramados($ambito);
        $ejecuciones   = $repo->ejecucionesConFecha($ambito);

        return [
            'beneficiarios_unicos'      => $beneficiarios,
            'participaciones_nominales' => $nominales,
            'participaciones_agregadas' => $agregadas,
            'cobertura_total'           => $nominales + $agregadas,
            'eventos_programados'       => $eventos,
            'ejecuciones'               => $ejecuciones,
            'cumplimiento_ejecucion'    => $eventos === 0 ? 0.0 : round($ejecuciones / $eventos * 100) / 100,
        ];
    }
}
