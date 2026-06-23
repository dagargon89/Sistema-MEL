<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Cadena MEL (2/6): eventos programados — el universo planeado (doc 03 §3.2).
 * FK→actividades y FK→procesos (RESTRICT). El proceso es opcional a nivel BD;
 * su obligatoriedad en multisesión es regla de aplicación (RF-PROG-021).
 */
class CreateEventosProgramados extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id_evento_programado' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'id_actividad'         => ['type' => 'CHAR', 'constraint' => 8],
            'id_proceso'           => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'tipo_programacion'    => ['type' => 'ENUM', 'constraint' => ['SESION_UNICA', 'MULTI_SESION_PROGRAMADA', 'PROCESO_CONTINUO']],
            'fecha_inicio'         => ['type' => 'DATE'],
            'fecha_finalizacion'   => ['type' => 'DATE'],
            'hora_inicio'          => ['type' => 'TIME', 'null' => true],
            'hora_finalizacion'    => ['type' => 'TIME', 'null' => true],
            'modalidad'            => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'lugar'                => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'calle_y_numero'       => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'colonia'              => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'responsable'          => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'contacto'             => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'estatus'              => ['type' => 'ENUM', 'constraint' => ['programado', 'ejecutado', 'cancelado', 'reprogramado'], 'default' => 'programado'],
            'num_sesion'           => ['type' => 'INT', 'null' => true],
            'total_sesiones'       => ['type' => 'INT', 'null' => true],
            'observaciones'        => ['type' => 'TEXT', 'null' => true],
        ]);
        $this->forge->addKey('id_evento_programado', true);
        $this->forge->addKey('id_actividad');
        $this->forge->addKey('id_proceso');
        $this->forge->addKey('fecha_inicio');
        $this->forge->addForeignKey('id_actividad', 'actividades', 'id_actividad', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('id_proceso', 'procesos', 'id_proceso', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('eventos_programados', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('eventos_programados', true);
    }
}
