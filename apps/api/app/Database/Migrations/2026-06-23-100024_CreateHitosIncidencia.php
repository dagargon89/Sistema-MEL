<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Incidencia: bitácora de hitos (doc 03 §3.5). FK→procesos_incidencia RESTRICT (RN-004).
 * `registrado_por` referencia lógica al usuario (Shield id); sin FK para no acoplar al
 * remapeo de ids del seeder. El servidor lo fija con el usuario autenticado.
 */
class CreateHitosIncidencia extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id_hito'                 => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'id_proceso_incidencia'   => ['type' => 'BIGINT', 'unsigned' => true],
            'fecha_hito'              => ['type' => 'DATE', 'null' => true],
            'tipo_hito'               => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'descripcion_hito'        => ['type' => 'TEXT', 'null' => true],
            'evidencia_nombre_o_nota' => ['type' => 'VARCHAR', 'constraint' => 300, 'null' => true],
            'registrado_por'          => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'observaciones'           => ['type' => 'TEXT', 'null' => true],
        ]);
        $this->forge->addKey('id_hito', true);
        $this->forge->addKey('id_proceso_incidencia');
        $this->forge->addForeignKey('id_proceso_incidencia', 'procesos_incidencia', 'id_proceso_incidencia', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('hitos_incidencia', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('hitos_incidencia', true);
    }
}
