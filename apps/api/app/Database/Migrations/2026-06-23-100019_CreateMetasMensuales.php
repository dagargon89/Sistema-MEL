<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Metas mensuales normalizadas M01–M18 (doc 03 §3.3). UNIQUE(id_meta, mes). */
class CreateMetasMensuales extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id_meta_mensual' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'id_meta'         => ['type' => 'BIGINT', 'unsigned' => true],
            'mes'             => ['type' => 'ENUM', 'constraint' => ['M01', 'M02', 'M03', 'M04', 'M05', 'M06', 'M07', 'M08', 'M09', 'M10', 'M11', 'M12', 'M13', 'M14', 'M15', 'M16', 'M17', 'M18']],
            'valor'           => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
        ]);
        $this->forge->addKey('id_meta_mensual', true);
        $this->forge->addUniqueKey(['id_meta', 'mes']);
        $this->forge->addForeignKey('id_meta', 'metas', 'id_meta', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('metas_mensuales', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('metas_mensuales', true);
    }
}
