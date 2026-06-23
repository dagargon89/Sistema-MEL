<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Incidencia: compromisos (doc 03 §3.5, RN-004). FK→procesos_incidencia RESTRICT:
 * no existe compromiso sin un proceso de incidencia válido.
 */
class CreateCompromisos extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id_compromiso'          => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'id_proceso_incidencia'  => ['type' => 'BIGINT', 'unsigned' => true],
            'identificacion'         => ['type' => 'VARCHAR', 'constraint' => 300, 'null' => true],
            'seguimiento_documentado' => ['type' => 'TEXT', 'null' => true],
            'criterios_elegibilidad' => ['type' => 'VARCHAR', 'constraint' => 300, 'null' => true],
            'control_registro'       => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'CAPTURADO'],
        ]);
        $this->forge->addKey('id_compromiso', true);
        $this->forge->addKey('id_proceso_incidencia');
        $this->forge->addForeignKey('id_proceso_incidencia', 'procesos_incidencia', 'id_proceso_incidencia', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('compromisos', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('compromisos', true);
    }
}
