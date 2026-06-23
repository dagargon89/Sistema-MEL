<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Siembra inicial de la Fase 0: roles + catálogos base (dimensiones) desde el mismo
 * `db.json` que alimenta el demo (doble uso, doc 09 §5). Se inserta solo si la tabla
 * está vacía (idempotente). Los usuarios/admin se siembran en el Sprint 1 (dependen
 * de Shield); las 236 actividades oficiales se cargan en el Sprint 2.
 */
class InitialSeeder extends Seeder
{
    /** Orden por dependencias de FK. */
    private const ORDEN = ['roles', 'ejes', 'instituciones', 'lineas', 'componentes', 'actividades'];

    /** Columnas a insertar por tabla (ignora cualquier campo extra del fixture). */
    private const COLUMNAS = [
        'roles'         => ['id_rol', 'clave', 'nombre', 'descripcion'],
        'ejes'          => ['id_eje', 'num_eje_original', 'clave_eje_corto', 'nombre', 'orden_visualizacion'],
        'instituciones' => ['id_institucion', 'num_institucion_original', 'nombre', 'estatus', 'orden_visualizacion'],
        'lineas'        => ['id_linea', 'num_linea', 'clave_linea_corta', 'nombre', 'id_eje', 'orden_visualizacion', 'estatus'],
        'componentes'   => ['id_componente', 'num_componente', 'clave_componente', 'nombre', 'id_institucion', 'orden_visualizacion', 'estatus'],
        'actividades'   => ['id_actividad', 'num_actividad', 'nombre', 'id_eje', 'id_linea', 'id_componente', 'id_institucion', 'tipo_registro', 'caso_excepcional'],
    ];

    public function run(): void
    {
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
            // Idempotente: no duplica si la tabla ya tiene datos.
            if ($this->db->table($tabla)->countAllResults() > 0) {
                continue;
            }

            $cols = self::COLUMNAS[$tabla];
            $rows = array_map(
                static fn (array $r): array => array_combine(
                    $cols,
                    array_map(static fn (string $c) => $r[$c] ?? null, $cols),
                ),
                $filas,
            );

            $this->db->table($tabla)->insertBatch($rows);
        }
    }
}
