<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Incidencia: procesos de incidencia (doc 03 §3.5). FK→actividades RESTRICT. */
class CreateProcesosIncidencia extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id_proceso_incidencia'  => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'nombre'                 => ['type' => 'VARCHAR', 'constraint' => 250],
            'criterios_elegibilidad' => ['type' => 'VARCHAR', 'constraint' => 300, 'null' => true],
            'ultimo_hito_resumen'    => ['type' => 'VARCHAR', 'constraint' => 300, 'null' => true],
            'control_registro'       => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'CAPTURADO'],
            'id_actividad'           => ['type' => 'CHAR', 'constraint' => 8],
        ]);
        $this->forge->addKey('id_proceso_incidencia', true);
        $this->forge->addKey('id_actividad');
        $this->forge->addForeignKey('id_actividad', 'actividades', 'id_actividad', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('procesos_incidencia', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('procesos_incidencia', true);
    }
}
