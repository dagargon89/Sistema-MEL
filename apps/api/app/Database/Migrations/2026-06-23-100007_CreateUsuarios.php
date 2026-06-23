<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Gobernanza: usuarios (extensión de dominio sobre Shield `users`). doc 03 §3.7.
 * `id_usuario` = user_id de Shield (NO auto_increment). La FK a `users` de Shield
 * se añade en el Sprint 1, tras `php spark shield:setup`.
 */
class CreateUsuarios extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id_usuario' => ['type' => 'BIGINT', 'unsigned' => true],
            'nombre'     => ['type' => 'VARCHAR', 'constraint' => 150],
            'email'      => ['type' => 'VARCHAR', 'constraint' => 190],
            'id_rol'     => ['type' => 'TINYINT', 'unsigned' => true],
            'estatus'    => ['type' => 'ENUM', 'constraint' => ['activo', 'inactivo'], 'default' => 'activo'],
        ]);
        $this->forge->addKey('id_usuario', true);
        $this->forge->addUniqueKey('email');
        $this->forge->addKey('id_rol');
        $this->forge->addForeignKey('id_rol', 'roles', 'id_rol', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('usuarios', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('usuarios', true);
    }
}
