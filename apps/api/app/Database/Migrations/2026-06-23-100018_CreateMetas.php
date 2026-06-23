<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Metas anuales por actividad (doc 03 §3.3). UNIQUE(id_actividad): una meta por actividad. */
class CreateMetas extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id_meta'           => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'id_actividad'      => ['type' => 'CHAR', 'constraint' => 8],
            'unidad_meta'       => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'unidad_especifica' => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'meta_anual_total'  => ['type' => 'DECIMAL', 'constraint' => '12,2', 'null' => true],
            'observaciones'     => ['type' => 'TEXT', 'null' => true],
        ]);
        $this->forge->addKey('id_meta', true);
        $this->forge->addUniqueKey('id_actividad');
        $this->forge->addForeignKey('id_actividad', 'actividades', 'id_actividad', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('metas', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('metas', true);
    }
}
