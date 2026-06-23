<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Productos/entregables — rama de actividades tipo E (doc 03 §3.2, RN-020).
 * FK→actividades RESTRICT; el bloqueo "solo tipo E" es regla de aplicación.
 */
class CreateProductosEntregables extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id_producto'              => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'id_actividad'             => ['type' => 'CHAR', 'constraint' => 8],
            'nombre_producto'          => ['type' => 'VARCHAR', 'constraint' => 250],
            'tipo_producto'            => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'fecha_inicio'             => ['type' => 'DATE', 'null' => true],
            'fecha_entrega'            => ['type' => 'DATE', 'null' => true],
            'responsable'              => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'cantidad'                 => ['type' => 'INT', 'null' => true],
            'unidad_medida'            => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'estatus'                  => ['type' => 'ENUM', 'constraint' => ['en_proceso', 'entregado', 'cancelado'], 'default' => 'en_proceso'],
            'descripcion'              => ['type' => 'TEXT', 'null' => true],
            'evidencia_url'            => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'nombre_archivo_evidencia' => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'control_registro'         => ['type' => 'ENUM', 'constraint' => ['CAPTURADO', 'INCOMPLETO', 'OK'], 'default' => 'CAPTURADO'],
        ]);
        $this->forge->addKey('id_producto', true);
        $this->forge->addKey('id_actividad');
        $this->forge->addForeignKey('id_actividad', 'actividades', 'id_actividad', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('productos_entregables', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('productos_entregables', true);
    }
}
