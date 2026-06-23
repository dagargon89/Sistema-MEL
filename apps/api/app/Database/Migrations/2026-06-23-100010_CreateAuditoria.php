<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Gobernanza: bitácora append-only de toda escritura (RF-GOB-112).
 * El servidor escribe aquí quién/qué/cuándo + valor antes/después (JSON). doc 03 §3.7.
 */
class CreateAuditoria extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id_evento'     => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'fecha_hora'    => ['type' => 'DATETIME'],
            'id_usuario'    => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'entidad'       => ['type' => 'VARCHAR', 'constraint' => 60],
            'id_registro'   => ['type' => 'VARCHAR', 'constraint' => 40],
            'accion'        => ['type' => 'ENUM', 'constraint' => ['alta', 'edicion', 'baja', 'reclasificacion', 'validacion']],
            'valor_antes'   => ['type' => 'JSON', 'null' => true],
            'valor_despues' => ['type' => 'JSON', 'null' => true],
        ]);
        $this->forge->addKey('id_evento', true);
        $this->forge->addKey(['entidad', 'id_registro']);
        $this->forge->addForeignKey('id_usuario', 'usuarios', 'id_usuario', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('auditoria', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('auditoria', true);
    }
}
