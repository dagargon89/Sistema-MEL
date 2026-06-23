<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Cadena MEL (6/6): participaciones agregadas — conteos no nominales (doc 03 §3.2).
 * FK→ejecuciones RESTRICT (RN-003). `periodo_corte` es obligatorio en casos A/B
 * (regla de aplicación, RF-AGRE-051).
 */
class CreateParticipacionesAgregadas extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id_participacion_agregada'   => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'id_ejecucion'                => ['type' => 'BIGINT', 'unsigned' => true],
            'tipo_registro_participacion' => ['type' => 'ENUM', 'constraint' => ['Agregado', 'Mixta'], 'default' => 'Agregado'],
            'sexo_grupo'                  => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'grupo_edad_aprox'            => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'cantidad_participantes'      => ['type' => 'INT'],
            'motivo_no_nominal'           => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'fuente_conteo'               => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'periodo_corte'               => ['type' => 'ENUM', 'constraint' => ['M01', 'M02', 'M03', 'M04', 'M05', 'M06', 'M07', 'M08', 'M09', 'M10', 'M11', 'M12', 'M13', 'M14', 'M15', 'M16', 'M17', 'M18'], 'null' => true],
            'evidencia_url'               => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'control_registro'            => ['type' => 'ENUM', 'constraint' => ['AGREGADO', 'INCOMPLETO'], 'default' => 'AGREGADO'],
        ]);
        $this->forge->addKey('id_participacion_agregada', true);
        $this->forge->addKey('id_ejecucion');
        $this->forge->addForeignKey('id_ejecucion', 'ejecuciones', 'id_ejecucion', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('participaciones_agregadas', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('participaciones_agregadas', true);
    }
}
