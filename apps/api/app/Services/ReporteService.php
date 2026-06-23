<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\TableroRepository;
use Config\Services;

/**
 * Reportería para FECHAC (doc 05 §12, RF-TAB-122). Agrega los KPIs reales
 * (control=OK) + composición del catálogo y resultados, acotado al ámbito.
 * Es la base del paquete que se exporta al financiador.
 */
class ReporteService
{
    /**
     * @return array{generado:string, periodo:string|null, beneficiarios_unicos:int, participaciones_nominales:int, participaciones_agregadas:int, cobertura_total:int, eventos_programados:int, ejecuciones:int, cumplimiento_ejecucion:float, actividades:array{P:int,E:int,R:int,total:int}, resultados_reportados:int}
     */
    public function fechac(?string $periodo): array
    {
        $ambito = Services::currentScope()->ambitoRepositorio();
        $repo   = new TableroRepository();

        $nominales   = $repo->nominales($ambito);
        $agregadas   = $repo->agregadasSuma($ambito);
        $eventos     = $repo->eventosProgramados($ambito);
        $ejecuciones = $repo->ejecucionesConFecha($ambito);

        return [
            'generado'                  => date('Y-m-d H:i:s'),
            'periodo'                   => $periodo,
            'beneficiarios_unicos'      => $repo->beneficiariosUnicos($ambito),
            'participaciones_nominales' => $nominales,
            'participaciones_agregadas' => $agregadas,
            'cobertura_total'           => $nominales + $agregadas,
            'eventos_programados'       => $eventos,
            'ejecuciones'               => $ejecuciones,
            'cumplimiento_ejecucion'    => $eventos === 0 ? 0.0 : round($ejecuciones / $eventos * 100) / 100,
            'actividades'               => $repo->actividadesPorTipo($ambito),
            'resultados_reportados'     => $repo->resultadosReportados($ambito),
        ];
    }
}
