<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Dimensión: ejes estratégicos (doc 03 §4). */
class CreateEjes extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id_eje'              => ['type' => 'CHAR', 'constraint' => 12],
            'num_eje_original'    => ['type' => 'INT', 'null' => true],
            'clave_eje_corto'     => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'nombre'              => ['type' => 'VARCHAR', 'constraint' => 200],
            'orden_visualizacion' => ['type' => 'INT', 'default' => 0],
        ]);
        $this->forge->addKey('id_eje', true);
        $this->forge->createTable('ejes', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('ejes', true);
    }
}
