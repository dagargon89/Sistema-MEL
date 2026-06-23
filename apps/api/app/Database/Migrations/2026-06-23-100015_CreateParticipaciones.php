<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Cadena MEL (5/6): participaciones — 1 persona en 1 ejecución (doc 03 §3.2).
 * FK→ejecuciones RESTRICT (RN-002). `id_persona`, `id_datosbeneficiario`,
 * `alerta_duplicado` y `control_registro` los calcula el servidor (dedup), nunca el cliente.
 */
class CreateParticipaciones extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id_participacion'      => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'id_ejecucion'          => ['type' => 'BIGINT', 'unsigned' => true],
            'id_persona'            => ['type' => 'CHAR', 'constraint' => 10, 'null' => true],
            'nombres'               => ['type' => 'VARCHAR', 'constraint' => 120],
            'apellido_paterno'      => ['type' => 'VARCHAR', 'constraint' => 80],
            'apellido_materno'      => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'anio_nacimiento'       => ['type' => 'SMALLINT', 'null' => true],
            'sexo'                  => ['type' => 'ENUM', 'constraint' => ['F', 'M', 'X']],
            'telefono'              => ['type' => 'VARCHAR', 'constraint' => 20],
            'correo'                => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'colonia_persona'       => ['type' => 'VARCHAR', 'constraint' => 120],
            'id_datosbeneficiario'  => ['type' => 'CHAR', 'constraint' => 40],
            'alerta_duplicado'      => ['type' => 'ENUM', 'constraint' => ['OK', 'DUPLICADO_EN_CAPTURA'], 'default' => 'OK'],
            'fecha_participacion'   => ['type' => 'DATE', 'null' => true],
            'control_registro'      => ['type' => 'ENUM', 'constraint' => ['CAPTURADO', 'INCOMPLETO', 'REVISAR', 'OK'], 'default' => 'CAPTURADO'],
            'control_automatico'    => ['type' => 'ENUM', 'constraint' => ['OK', 'INCOMPLETO', 'REVISAR'], 'null' => true],
            'decision_coordinacion' => ['type' => 'ENUM', 'constraint' => ['OK', 'INCOMPLETO', 'REVISAR'], 'null' => true],
            'detalle_validacion'    => ['type' => 'VARCHAR', 'constraint' => 300, 'null' => true],
        ]);
        $this->forge->addKey('id_participacion', true);
        $this->forge->addKey('id_ejecucion');
        $this->forge->addKey('id_persona');
        $this->forge->addKey('id_datosbeneficiario');
        $this->forge->addKey('control_registro');
        $this->forge->addForeignKey('id_ejecucion', 'ejecuciones', 'id_ejecucion', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('id_persona', 'personas', 'id_persona', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('participaciones', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('participaciones', true);
    }
}
