<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Vertical shelter: ocupación mensual (doc 03 §3.6, ACT_224). FK→actividades RESTRICT.
 * `pct_ocupacion` NO se almacena: se calcula en vivo (RF-VERT-090).
 */
class CreateOcupacionShelter extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id_ocupacion'        => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'id_actividad'        => ['type' => 'CHAR', 'constraint' => 8],
            'mes_periodo'         => ['type' => 'ENUM', 'constraint' => ['M01', 'M02', 'M03', 'M04', 'M05', 'M06', 'M07', 'M08', 'M09', 'M10', 'M11', 'M12', 'M13', 'M14', 'M15', 'M16', 'M17', 'M18']],
            'tipo_espacio'        => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'capacidad_instalada' => ['type' => 'INT'],
            'ocupacion'           => ['type' => 'INT'],
            'fuente'              => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'control_registro'    => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'AGREGADO'],
        ]);
        $this->forge->addKey('id_ocupacion', true);
        $this->forge->addKey('id_actividad');
        $this->forge->addForeignKey('id_actividad', 'actividades', 'id_actividad', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('ocupacion_shelter', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('ocupacion_shelter', true);
    }
}
