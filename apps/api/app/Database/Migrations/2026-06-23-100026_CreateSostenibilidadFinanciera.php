<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Vertical sostenibilidad financiera (doc 03 §3.6). FK→actividades RESTRICT.
 * Utilidad, recursos totales, % de avance y semáforo NO se almacenan: se calculan
 * en vivo (RF-VERT-091).
 */
class CreateSostenibilidadFinanciera extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id_registro'       => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'id_actividad'      => ['type' => 'CHAR', 'constraint' => 8],
            'mes_periodo'       => ['type' => 'ENUM', 'constraint' => ['M01', 'M02', 'M03', 'M04', 'M05', 'M06', 'M07', 'M08', 'M09', 'M10', 'M11', 'M12', 'M13', 'M14', 'M15', 'M16', 'M17', 'M18']],
            'ingresos_brutos'   => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'costos_directos'   => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'costos_indirectos' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'recursos_efectivo' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'recursos_especie'  => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'fuente_datos'      => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'meta_anual'        => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'control_registro'  => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'AGREGADO'],
        ]);
        $this->forge->addKey('id_registro', true);
        $this->forge->addKey('id_actividad');
        $this->forge->addForeignKey('id_actividad', 'actividades', 'id_actividad', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('sostenibilidad_financiera', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('sostenibilidad_financiera', true);
    }
}
