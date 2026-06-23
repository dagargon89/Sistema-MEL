<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Dimensión: instituciones. doc 03 §4. */
class CreateInstituciones extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id_institucion'           => ['type' => 'CHAR', 'constraint' => 12],
            'num_institucion_original' => ['type' => 'INT', 'null' => true],
            'nombre'                   => ['type' => 'VARCHAR', 'constraint' => 200],
            'estatus'                  => ['type' => 'ENUM', 'constraint' => ['activo', 'inactivo'], 'default' => 'activo'],
            'orden_visualizacion'      => ['type' => 'INT', 'default' => 0],
        ]);
        $this->forge->addKey('id_institucion', true);
        $this->forge->createTable('instituciones', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('instituciones', true);
    }
}
