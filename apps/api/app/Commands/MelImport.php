<?php

declare(strict_types=1);

namespace App\Commands;

use App\Services\MigracionService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * `spark mel:import` — importa y concilia los datos del Excel v1.9 (Sprint 4, doc 06 §4).
 * Lee los CSV exportados, limpia `#REF!`, descarta filas-plantilla, carga en orden de
 * dependencias y regenera personas por deduplicación. Imprime la conciliación y la
 * contrasta con la línea base (≈988/762/279/132); ningún tablero debe quedar en 1000/100%.
 *
 * Pre-requisito: BD migrada y vacía. Los CSV (con PII) se colocan en el directorio
 * indicado, NO se versionan. Corre contra MySQL real en staging (doc 04 §6.3).
 */
class MelImport extends BaseCommand
{
    protected $group       = 'MEL';
    protected $name        = 'mel:import';
    protected $description = 'Importa y concilia los datos del Excel v1.9 (CSV) en la base de datos.';
    protected $usage       = 'mel:import [--dir <ruta>]';

    /** @var array<string, string> */
    protected $options = [
        '--dir' => 'Directorio con los CSV exportados (default: ROOTPATH/data/excel).',
    ];

    /** Línea base verificada (corte 5-jun-2026, doc 06 §4) para conciliar. */
    private const BASE = [
        'participaciones'     => 988,
        'personas'            => 762,
        'eventos_programados' => 279,
        'actividades'         => 236,
    ];

    /** @param array<int|string, string|null> $params */
    public function run(array $params): int
    {
        $dir = $params['dir'] ?? CLI::getOption('dir');
        if (! is_string($dir) || $dir === '') {
            $dir = ROOTPATH . 'data/excel';
        }
        if (! is_dir($dir)) {
            CLI::error("Directorio no encontrado: {$dir}");

            return EXIT_ERROR;
        }

        CLI::write("Importando datos MEL desde {$dir} ...", 'yellow');
        $rep = (new MigracionService())->importar($dir);

        /** @var array{total:int, P:int, E:int, R:int} $act */
        $act = $rep['actividades'];
        /** @var array{total:int, con_fecha:int} $ejec */
        $ejec = $rep['ejecuciones'];

        CLI::newLine();
        CLI::write('Conciliación de la migración (doc 06 §4):', 'green');
        CLI::table([
            ['Actividades', (string) $act['total'], sprintf('%d P / %d E / %d R', $act['P'], $act['E'], $act['R'])],
            ['Procesos', (string) $rep['procesos'], ''],
            ['Eventos programados', (string) $rep['eventos_programados'], ''],
            ['Ejecuciones', (string) $ejec['total'], $ejec['con_fecha'] . ' con fecha real'],
            ['Participaciones nominales', (string) $rep['participaciones'], ''],
            ['Personas únicas (regeneradas)', (string) $rep['personas'], ''],
            ['Duplicados a cola (REVISAR)', (string) $rep['cola_revisar'], ''],
            ['Participaciones agregadas (suma)', (string) $rep['agregadas_suma'], $rep['agregadas_filas'] . ' filas'],
            ['Cobertura total', (string) $rep['cobertura_total'], ''],
            ['Celdas #REF! limpiadas', (string) $rep['refs_limpiados'], ''],
            ['Filas-plantilla descartadas', (string) $rep['plantillas_descartadas'], ''],
        ], ['Indicador', 'Valor', 'Detalle']);

        CLI::newLine();
        $this->conciliar('Participaciones', $rep['participaciones'], self::BASE['participaciones']);
        $this->conciliar('Personas únicas', $rep['personas'], self::BASE['personas']);
        $this->conciliar('Eventos programados', $rep['eventos_programados'], self::BASE['eventos_programados']);
        $this->conciliar('Actividades', $act['total'], self::BASE['actividades']);

        return EXIT_SUCCESS;
    }

    /** Compara un conteo contra la línea base con ±15% de tolerancia documentada. */
    private function conciliar(string $indicador, mixed $valor, int $base): void
    {
        $v   = is_int($valor) ? $valor : 0;
        $tol = (int) round($base * 0.15);
        if (abs($v - $base) <= $tol) {
            CLI::write(CLI::color("  ✓ {$indicador}: {$v} (línea base ≈ {$base})", 'green'));

            return;
        }
        CLI::write(CLI::color("  ⚠ {$indicador}: {$v} fuera de la línea base ≈ {$base} (±{$tol}). Revisar el CSV.", 'yellow'));
    }
}
