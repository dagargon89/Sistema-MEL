<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use App\Services\DeduplicacionService;
use CodeIgniter\Database\Seeder;

/**
 * Siembra la cadena MEL de muestra (Sprint 3) desde `db.json`: procesos → eventos →
 * personas → ejecuciones → participaciones → agregadas, en orden de dependencias de FK.
 * Encadena Sprint1Seeder (Shield + dominio + dimensiones). Idempotente.
 *
 * Es una MUESTRA para recorrer cada flujo/estado (QA1–QA7), no la migración completa:
 * la carga real (≈988/762/279/132) llega con `spark mel:import` (Sprint 4).
 */
class Sprint3Seeder extends Seeder
{
    /** Orden por dependencias de FK. */
    private const ORDEN = ['procesos', 'eventos_programados', 'personas', 'ejecuciones', 'participaciones', 'participaciones_agregadas'];

    /** Columnas a insertar por tabla (ignora cualquier campo extra del fixture). */
    private const COLUMNAS = [
        'procesos' => [
            'id_proceso', 'nombre', 'tipo_programacion', 'id_actividad', 'fecha_inicio', 'fecha_fin',
            'total_sesiones_programadas', 'responsable', 'contacto', 'estatus', 'observaciones',
        ],
        'eventos_programados' => [
            'id_evento_programado', 'id_actividad', 'id_proceso', 'tipo_programacion', 'fecha_inicio',
            'fecha_finalizacion', 'hora_inicio', 'hora_finalizacion', 'modalidad', 'lugar', 'calle_y_numero',
            'colonia', 'responsable', 'contacto', 'estatus', 'num_sesion', 'total_sesiones', 'observaciones',
        ],
        'personas' => [
            'id_persona', 'nombres', 'apellido_paterno', 'apellido_materno', 'nombre_completo', 'anio_nacimiento',
            'sexo', 'telefono', 'correo', 'colonia', 'id_datosbeneficiario', 'primera_participacion',
            'total_participaciones', 'control_registro', 'decision_coordinacion',
        ],
        'ejecuciones' => [
            'id_ejecucion', 'id_evento_programado', 'fecha_ejecucion_real', 'hora_inicio_real', 'hora_finalizacion_real',
            'lugar_real', 'colonia_real', 'responsable_real', 'estatus_ejecucion', 'tipo_registro_participacion',
            'total_participantes', 'evidencia_url', 'nombre_archivo_evidencia', 'resumen_narrativo', 'control_registro', 'observaciones',
        ],
        'participaciones' => [
            'id_participacion', 'id_ejecucion', 'id_persona', 'nombres', 'apellido_paterno', 'apellido_materno',
            'anio_nacimiento', 'sexo', 'telefono', 'correo', 'colonia_persona', 'id_datosbeneficiario',
            'alerta_duplicado', 'fecha_participacion', 'control_registro', 'control_automatico', 'decision_coordinacion', 'detalle_validacion',
        ],
        'participaciones_agregadas' => [
            'id_participacion_agregada', 'id_ejecucion', 'tipo_registro_participacion', 'sexo_grupo', 'grupo_edad_aprox',
            'cantidad_participantes', 'motivo_no_nominal', 'fuente_conteo', 'periodo_corte', 'evidencia_url', 'control_registro',
        ],
    ];

    public function run(): void
    {
        $this->call(Sprint1Seeder::class);

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

            // La clave de dedup se calcula con el MISMO algoritmo del servidor, para que
            // una recaptura de la misma persona consolide contra la fila sembrada (QA2).
            if (in_array($tabla, ['personas', 'participaciones'], true)) {
                $rows = $this->conClaveDedup($rows);
            }

            $this->db->table($tabla)->insertBatch($rows);
        }
    }

    /**
     * Recalcula `id_datosbeneficiario` de cada fila con DeduplicacionService::calcularClave.
     *
     * @param list<array<string, mixed>> $rows
     *
     * @return list<array<string, mixed>>
     */
    private function conClaveDedup(array $rows): array
    {
        return array_map(static function (array $r): array {
            $r['id_datosbeneficiario'] = DeduplicacionService::calcularClave(
                is_string($r['apellido_paterno'] ?? null) ? $r['apellido_paterno'] : null,
                is_string($r['apellido_materno'] ?? null) ? $r['apellido_materno'] : null,
                is_string($r['nombres'] ?? null) ? $r['nombres'] : null,
                is_numeric($r['anio_nacimiento'] ?? null) ? (int) $r['anio_nacimiento'] : null,
                is_string($r['telefono'] ?? null) ? $r['telefono'] : null,
            );

            return $r;
        }, $rows);
    }
}
