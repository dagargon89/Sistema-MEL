<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Gobernanza: catálogo de roles (espeja los grupos de Shield). doc 03 §3.7. */
class CreateRoles extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id_rol'      => ['type' => 'TINYINT', 'unsigned' => true, 'auto_increment' => true],
            'clave'       => ['type' => 'ENUM', 'constraint' => ['capturista', 'coordinacion', 'direccion', 'administrador']],
            'nombre'      => ['type' => 'VARCHAR', 'constraint' => 100],
            'descripcion' => ['type' => 'VARCHAR', 'constraint' => 250, 'null' => true],
        ]);
        $this->forge->addKey('id_rol', true);
        $this->forge->addUniqueKey('clave');
        $this->forge->createTable('roles', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('roles', true);
    }
}
