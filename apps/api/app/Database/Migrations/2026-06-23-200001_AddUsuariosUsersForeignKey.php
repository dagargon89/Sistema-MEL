<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * FK diferida de Sprint 0: `usuarios.id_usuario` → `users.id` (Shield), ON DELETE CASCADE.
 * Se añade ahora que Shield ya creó `users`. Solo MySQL: SQLite no soporta
 * ALTER TABLE ADD FOREIGN KEY (las pruebas corren en SQLite; la FK se valida en MySQL).
 */
class AddUsuariosUsersForeignKey extends Migration
{
    public function up(): void
    {
        if (db_connect()->DBDriver !== 'MySQLi') {
            return;
        }

        $this->forge->addForeignKey('id_usuario', 'users', 'id', 'CASCADE', 'CASCADE', 'fk_usuarios_users');
        $this->forge->processIndexes('usuarios');
    }

    public function down(): void
    {
        if (db_connect()->DBDriver !== 'MySQLi') {
            return;
        }

        $this->forge->dropForeignKey('usuarios', 'fk_usuarios_users');
    }
}
