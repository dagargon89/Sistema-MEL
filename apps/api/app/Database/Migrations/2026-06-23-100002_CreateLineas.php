<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Dimensión: líneas de acción (FK→ejes, RESTRICT). doc 03 §4. */
class CreateLineas extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id_linea'            => ['type' => 'CHAR', 'constraint' => 12],
            'num_linea'           => ['type' => 'INT', 'null' => true],
            'clave_linea_corta'   => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'nombre'              => ['type' => 'VARCHAR', 'constraint' => 200],
            'id_eje'              => ['type' => 'CHAR', 'constraint' => 12],
            'orden_visualizacion' => ['type' => 'INT', 'default' => 0],
            'estatus'             => ['type' => 'ENUM', 'constraint' => ['activo', 'inactivo'], 'default' => 'activo'],
        ]);
        $this->forge->addKey('id_linea', true);
        $this->forge->addKey('id_eje');
        $this->forge->addForeignKey('id_eje', 'ejes', 'id_eje', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('lineas', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('lineas', true);
    }
}
