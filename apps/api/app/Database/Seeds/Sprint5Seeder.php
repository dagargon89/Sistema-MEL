<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Siembra metas + mensuales + productos de muestra (Fase 2) desde `db.json`, encadenando
 * Sprint3Seeder (cadena MEL). Soporta los casos QA4 (incompleto fuera de KPIs) y QA6
 * (caso C con corte al cierre: meta 3 → ACT_094). Idempotente.
 */
class Sprint5Seeder extends Seeder
{
    /** Orden por dependencias de FK. */
    private const ORDEN = ['metas', 'metas_mensuales', 'productos_entregables'];

    /** Columnas a insertar por tabla. */
    private const COLUMNAS = [
        'metas'                 => ['id_meta', 'id_actividad', 'unidad_meta', 'unidad_especifica', 'meta_anual_total', 'observaciones'],
        'metas_mensuales'       => ['id_meta_mensual', 'id_meta', 'mes', 'valor'],
        'productos_entregables' => ['id_producto', 'id_actividad', 'nombre_producto', 'tipo_producto', 'fecha_inicio', 'fecha_entrega', 'responsable', 'cantidad', 'unidad_medida', 'estatus', 'descripcion', 'evidencia_url', 'nombre_archivo_evidencia', 'control_registro'],
    ];

    public function run(): void
    {
        $this->call(Sprint3Seeder::class);

        $path = __DIR__ . '/data/db.json';
        if (! is_file($path)) {
            return;
        }
        /** @var array<string, list<array<string, mixed>>> $data */
        $data = json_decode((string) file_get_contents($path), true) ?? [];

        foreach (self::ORDEN as $tabla) {
            $filas = $data[$tabla] ?? null;
            if (! is_array($filas) || $filas === []) {
                continue;
            }
            if ($this->db->table($tabla)->countAllResults() > 0) {
                continue; // idempotente
            }

            $cols = self::COLUMNAS[$tabla];
            $rows = array_map(
                static fn (array $r): array => array_combine($cols, array_map(static fn (string $c) => $r[$c] ?? null, $cols)),
                $filas,
            );

            $this->db->table($tabla)->insertBatch($rows);
        }
    }
}
