<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Siembra incidencia + verticales de muestra (Fase 3) desde `db.json`, encadenando
 * Sprint5Seeder. Orden por dependencias de FK (procesos de incidencia antes de
 * compromisos/hitos). Idempotente.
 */
class Sprint6Seeder extends Seeder
{
    private const ORDEN = ['procesos_incidencia', 'propuestas_incidencia', 'compromisos', 'alianzas', 'hitos_incidencia', 'ocupacion_shelter', 'sostenibilidad_financiera'];

    private const COLUMNAS = [
        'procesos_incidencia'       => ['id_proceso_incidencia', 'nombre', 'criterios_elegibilidad', 'ultimo_hito_resumen', 'control_registro', 'id_actividad'],
        'propuestas_incidencia'     => ['id_propuesta', 'nombre_propuesta', 'promotor_colectivo', 'tipo_actor', 'fecha_inicio_asesoria', 'responsable_equipo', 'sesiones_documentadas', 'mejora_documentada', 'cambios_resultado_asesoria', 'evidencia_principal', 'alineada_proyectos_estrategicos', 'criterios_alineacion_nota', 'estatus', 'elegible_reporte', 'id_actividad', 'periodo_reporte', 'control_registro'],
        'compromisos'               => ['id_compromiso', 'id_proceso_incidencia', 'identificacion', 'seguimiento_documentado', 'criterios_elegibilidad', 'control_registro'],
        'alianzas'                  => ['id_alianza', 'nombre_alianza', 'datos_alianza', 'criterios_elegibilidad', 'id_actividad', 'control_registro'],
        'hitos_incidencia'          => ['id_hito', 'id_proceso_incidencia', 'fecha_hito', 'tipo_hito', 'descripcion_hito', 'evidencia_nombre_o_nota', 'registrado_por', 'observaciones'],
        'ocupacion_shelter'         => ['id_ocupacion', 'id_actividad', 'mes_periodo', 'tipo_espacio', 'capacidad_instalada', 'ocupacion', 'fuente', 'control_registro'],
        'sostenibilidad_financiera' => ['id_registro', 'id_actividad', 'mes_periodo', 'ingresos_brutos', 'costos_directos', 'costos_indirectos', 'recursos_efectivo', 'recursos_especie', 'fuente_datos', 'meta_anual', 'control_registro'],
    ];

    public function run(): void
    {
        $this->call(Sprint5Seeder::class);

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
                static function (array $r) use ($cols): array {
                    $fila = array_combine($cols, array_map(static fn (string $c) => $r[$c] ?? null, $cols));
                    // Booleanos del JSON -> 0/1 para columnas TINYINT.
                    foreach ($fila as $k => $v) {
                        if (is_bool($v)) {
                            $fila[$k] = (int) $v;
                        }
                    }

                    return $fila;
                },
                $filas,
            );

            $this->db->table($tabla)->insertBatch($rows);
        }
    }
}
