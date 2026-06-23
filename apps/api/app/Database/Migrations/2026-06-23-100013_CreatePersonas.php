<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Cadena MEL (3/6): personas únicas — tabla DERIVADA (doc 03 §3.2, ADR-003).
 * Sin alta manual: la puebla el DeduplicacionService. UNIQUE sobre la clave de
 * dedup hace imposible una identidad duplicada por construcción.
 */
class CreatePersonas extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id_persona'            => ['type' => 'CHAR', 'constraint' => 10],
            'nombres'               => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'apellido_paterno'      => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'apellido_materno'      => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'nombre_completo'       => ['type' => 'VARCHAR', 'constraint' => 300, 'null' => true],
            'anio_nacimiento'       => ['type' => 'SMALLINT', 'null' => true],
            'sexo'                  => ['type' => 'ENUM', 'constraint' => ['F', 'M', 'X'], 'null' => true],
            'telefono'              => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'correo'                => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'colonia'               => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'id_datosbeneficiario'  => ['type' => 'CHAR', 'constraint' => 40],
            'primera_participacion' => ['type' => 'DATE', 'null' => true],
            'total_participaciones' => ['type' => 'INT', 'default' => 0],
            'control_registro'      => ['type' => 'ENUM', 'constraint' => ['OK', 'REVISAR'], 'default' => 'REVISAR'],
            'decision_coordinacion' => ['type' => 'VARCHAR', 'constraint' => 60, 'null' => true],
        ]);
        $this->forge->addKey('id_persona', true);
        $this->forge->addUniqueKey('id_datosbeneficiario');
        $this->forge->createTable('personas', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('personas', true);
    }
}
