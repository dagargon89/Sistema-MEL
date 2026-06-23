<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Cadena MEL (4/6): ejecuciones reales de un evento (doc 03 §3.2).
 * FK→eventos_programados RESTRICT (RN-001): imposible ejecutar sin evento.
 * `control_registro` es máquina de estados calculada en servidor (SRS §4).
 */
class CreateEjecuciones extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id_ejecucion'                => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'id_evento_programado'        => ['type' => 'BIGINT', 'unsigned' => true],
            'fecha_ejecucion_real'        => ['type' => 'DATE', 'null' => true],
            'hora_inicio_real'            => ['type' => 'TIME', 'null' => true],
            'hora_finalizacion_real'      => ['type' => 'TIME', 'null' => true],
            'lugar_real'                  => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'colonia_real'                => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'responsable_real'            => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'estatus_ejecucion'           => ['type' => 'ENUM', 'constraint' => ['ejecutada', 'suspendida', 'parcial'], 'null' => true],
            'tipo_registro_participacion' => ['type' => 'ENUM', 'constraint' => ['Nominal', 'Agregado', 'Mixta'], 'default' => 'Nominal'],
            'total_participantes'         => ['type' => 'INT', 'null' => true],
            'evidencia_url'               => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'nombre_archivo_evidencia'    => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'resumen_narrativo'           => ['type' => 'TEXT', 'null' => true],
            'control_registro'            => ['type' => 'ENUM', 'constraint' => ['CAPTURADO', 'INCOMPLETO', 'REVISAR', 'OK', 'AGREGADO'], 'default' => 'CAPTURADO'],
            'observaciones'               => ['type' => 'TEXT', 'null' => true],
        ]);
        $this->forge->addKey('id_ejecucion', true);
        $this->forge->addKey('id_evento_programado');
        $this->forge->addKey('control_registro');
        $this->forge->addKey('fecha_ejecucion_real');
        $this->forge->addForeignKey('id_evento_programado', 'eventos_programados', 'id_evento_programado', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('ejecuciones', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('ejecuciones', true);
    }
}
