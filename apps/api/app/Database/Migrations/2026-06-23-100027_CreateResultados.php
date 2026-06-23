<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Capa de resultados — rama de actividades tipo R (doc 03 §3.4). FK→actividades RESTRICT;
 * el bloqueo "solo tipo R" es regla de aplicación (RF-RES-100).
 */
class CreateResultados extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id_resultado'    => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'id_actividad'    => ['type' => 'CHAR', 'constraint' => 8],
            'indicador'       => ['type' => 'VARCHAR', 'constraint' => 250],
            'linea_base'      => ['type' => 'DECIMAL', 'constraint' => '14,4', 'null' => true],
            'valor_medido'    => ['type' => 'DECIMAL', 'constraint' => '14,4', 'null' => true],
            'metodo_medicion' => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => true],
            'fecha_medicion'  => ['type' => 'DATE', 'null' => true],
            'evidencia_url'   => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
        ]);
        $this->forge->addKey('id_resultado', true);
        $this->forge->addKey('id_actividad');
        $this->forge->addForeignKey('id_actividad', 'actividades', 'id_actividad', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('resultados', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('resultados', true);
    }
}
