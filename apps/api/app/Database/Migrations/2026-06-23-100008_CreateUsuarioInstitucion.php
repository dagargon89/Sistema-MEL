<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Ámbito por institución/territorio (ADR-004). Sustituye la RLS: el filtrado por
 * institución se aplica en la capa Repository usando esta pertenencia N:N.
 * doc 03 §3.7.
 */
class CreateUsuarioInstitucion extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'             => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'id_usuario'     => ['type' => 'INT', 'unsigned' => true],
            'id_institucion' => ['type' => 'CHAR', 'constraint' => 12],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['id_usuario', 'id_institucion']);
        $this->forge->addForeignKey('id_usuario', 'usuarios', 'id_usuario', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('id_institucion', 'instituciones', 'id_institucion', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('usuario_institucion', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('usuario_institucion', true);
    }
}
