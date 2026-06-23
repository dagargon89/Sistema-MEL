<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Incidencia: alianzas (doc 03 §3.5, ACT_048). FK→actividades RESTRICT. */
class CreateAlianzas extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id_alianza'             => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'nombre_alianza'         => ['type' => 'VARCHAR', 'constraint' => 250],
            'datos_alianza'          => ['type' => 'TEXT', 'null' => true],
            'criterios_elegibilidad' => ['type' => 'VARCHAR', 'constraint' => 300, 'null' => true],
            'id_actividad'           => ['type' => 'CHAR', 'constraint' => 8],
            'control_registro'       => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'CAPTURADO'],
        ]);
        $this->forge->addKey('id_alianza', true);
        $this->forge->addKey('id_actividad');
        $this->forge->addForeignKey('id_actividad', 'actividades', 'id_actividad', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('alianzas', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('alianzas', true);
    }
}
