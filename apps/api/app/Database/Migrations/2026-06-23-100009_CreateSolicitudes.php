<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/** Gobernanza: solicitudes de corrección/mejora/ajuste (FK→usuarios). doc 03 §3.7. */
class CreateSolicitudes extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id_solicitud'         => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'fecha_solicitud'      => ['type' => 'DATETIME'],
            'id_solicitante'       => ['type' => 'BIGINT', 'unsigned' => true],
            'rol_solicitante'      => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'entidad_afectada'     => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
            'descripcion'          => ['type' => 'TEXT'],
            'tipo_solicitud'       => ['type' => 'ENUM', 'constraint' => ['correccion', 'mejora', 'ajuste']],
            'nivel_criticidad'     => ['type' => 'ENUM', 'constraint' => ['BAJA', 'MEDIA', 'ALTA'], 'default' => 'MEDIA'],
            'impacto'              => ['type' => 'VARCHAR', 'constraint' => 250, 'null' => true],
            'estado'               => ['type' => 'ENUM', 'constraint' => ['en_revision', 'en_proceso', 'resuelta', 'descartada'], 'default' => 'en_revision'],
            'responsable_atencion' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'fecha_resolucion'     => ['type' => 'DATETIME', 'null' => true],
            'comentarios'          => ['type' => 'TEXT', 'null' => true],
        ]);
        $this->forge->addKey('id_solicitud', true);
        $this->forge->addKey('id_solicitante');
        $this->forge->addKey('estado');
        $this->forge->addForeignKey('id_solicitante', 'usuarios', 'id_usuario', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('responsable_atencion', 'usuarios', 'id_usuario', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('solicitudes', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('solicitudes', true);
    }
}
