<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Catálogo central: actividades (236 oficiales: 174 P / 42 E / 20 R).
 * Hereda la estructura estratégica (FK a eje/línea/componente/institución, RESTRICT).
 * doc 03 §4.
 */
class CreateActividades extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id_actividad'     => ['type' => 'CHAR', 'constraint' => 8],
            'num_actividad'    => ['type' => 'INT', 'null' => true],
            'nombre'           => ['type' => 'VARCHAR', 'constraint' => 300],
            'id_eje'           => ['type' => 'CHAR', 'constraint' => 12],
            'id_linea'         => ['type' => 'CHAR', 'constraint' => 12],
            'id_componente'    => ['type' => 'CHAR', 'constraint' => 12],
            'id_institucion'   => ['type' => 'CHAR', 'constraint' => 12],
            'tipo_registro'    => ['type' => 'ENUM', 'constraint' => ['P', 'E', 'R']],
            'caso_excepcional' => ['type' => 'ENUM', 'constraint' => ['A', 'B', 'C', 'D'], 'null' => true],
        ]);
        $this->forge->addKey('id_actividad', true);
        $this->forge->addKey('id_institucion');
        $this->forge->addKey('tipo_registro');
        $this->forge->addForeignKey('id_eje', 'ejes', 'id_eje', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('id_linea', 'lineas', 'id_linea', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('id_componente', 'componentes', 'id_componente', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('id_institucion', 'instituciones', 'id_institucion', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('actividades', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('actividades', true);
    }
}
