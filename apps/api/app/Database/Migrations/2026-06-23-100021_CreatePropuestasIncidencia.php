<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Incidencia: propuestas asesoradas (doc 03 §3.5). FK→actividades RESTRICT. */
class CreatePropuestasIncidencia extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id_propuesta'                    => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'nombre_propuesta'                => ['type' => 'VARCHAR', 'constraint' => 250],
            'promotor_colectivo'              => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'tipo_actor'                      => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'fecha_inicio_asesoria'           => ['type' => 'DATE', 'null' => true],
            'responsable_equipo'              => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'sesiones_documentadas'           => ['type' => 'INT', 'null' => true],
            'mejora_documentada'              => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'cambios_resultado_asesoria'      => ['type' => 'TEXT', 'null' => true],
            'evidencia_principal'             => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'alineada_proyectos_estrategicos' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'criterios_alineacion_nota'       => ['type' => 'VARCHAR', 'constraint' => 300, 'null' => true],
            'estatus'                         => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'activo'],
            'elegible_reporte'                => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'id_actividad'                    => ['type' => 'CHAR', 'constraint' => 8],
            'periodo_reporte'                 => ['type' => 'ENUM', 'constraint' => ['M01', 'M02', 'M03', 'M04', 'M05', 'M06', 'M07', 'M08', 'M09', 'M10', 'M11', 'M12', 'M13', 'M14', 'M15', 'M16', 'M17', 'M18'], 'null' => true],
            'control_registro'                => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'CAPTURADO'],
        ]);
        $this->forge->addKey('id_propuesta', true);
        $this->forge->addKey('id_actividad');
        $this->forge->addForeignKey('id_actividad', 'actividades', 'id_actividad', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('propuestas_incidencia', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('propuestas_incidencia', true);
    }
}
