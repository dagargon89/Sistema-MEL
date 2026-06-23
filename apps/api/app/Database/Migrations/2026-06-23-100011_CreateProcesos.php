<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Cadena MEL (1/6): procesos/grupos que agrupan sesiones (doc 03 §3.2).
 * FK→actividades RESTRICT: no hay proceso sin actividad estratégica (RN-001).
 */
class CreateProcesos extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id_proceso'                 => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'nombre'                     => ['type' => 'VARCHAR', 'constraint' => 250],
            'tipo_programacion'          => ['type' => 'ENUM', 'constraint' => ['SESION_UNICA', 'MULTI_SESION_PROGRAMADA', 'PROCESO_CONTINUO']],
            'id_actividad'               => ['type' => 'CHAR', 'constraint' => 8],
            'fecha_inicio'               => ['type' => 'DATE', 'null' => true],
            'fecha_fin'                  => ['type' => 'DATE', 'null' => true],
            'total_sesiones_programadas' => ['type' => 'INT', 'null' => true],
            'responsable'                => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'contacto'                   => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'estatus'                    => ['type' => 'ENUM', 'constraint' => ['activo', 'concluido', 'cancelado'], 'default' => 'activo'],
            'observaciones'              => ['type' => 'TEXT', 'null' => true],
        ]);
        $this->forge->addKey('id_proceso', true);
        $this->forge->addKey('id_actividad');
        $this->forge->addForeignKey('id_actividad', 'actividades', 'id_actividad', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('procesos', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('procesos', true);
    }
}
