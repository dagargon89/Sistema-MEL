<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Dimensión: componentes (FK→instituciones, RESTRICT). doc 03 §4. */
class CreateComponentes extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id_componente'       => ['type' => 'CHAR', 'constraint' => 12],
            'num_componente'      => ['type' => 'INT', 'null' => true],
            'clave_componente'    => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'nombre'              => ['type' => 'VARCHAR', 'constraint' => 200],
            'id_institucion'      => ['type' => 'CHAR', 'constraint' => 12],
            'orden_visualizacion' => ['type' => 'INT', 'default' => 0],
            'estatus'             => ['type' => 'ENUM', 'constraint' => ['activo', 'inactivo'], 'default' => 'activo'],
        ]);
        $this->forge->addKey('id_componente', true);
        $this->forge->addKey('id_institucion');
        $this->forge->addForeignKey('id_institucion', 'instituciones', 'id_institucion', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('componentes', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('componentes', true);
    }
}
