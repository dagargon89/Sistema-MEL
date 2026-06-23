<?php

declare(strict_types=1);

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Verifica el esquema base (doc 03) y el InitialSeeder corriendo las migraciones
 * de App contra el grupo `tests` (SQLite :memory:, foreignKeys ON).
 *
 * @internal
 */
final class SchemaSeedTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = 'App';
    protected $migrate   = true;
    protected $refresh   = true;
    protected $seed      = \App\Database\Seeds\InitialSeeder::class;

    public function testRolesSeeded(): void
    {
        $this->seeInDatabase('roles', ['clave' => 'coordinacion']);
        $this->assertSame(4, $this->db->table('roles')->countAllResults());
    }

    public function testDimensionesSeeded(): void
    {
        $this->assertGreaterThan(0, $this->db->table('ejes')->countAllResults());
        $this->assertGreaterThan(0, $this->db->table('actividades')->countAllResults());
    }

    public function testHerenciaActividadApuntaAFilasReales(): void
    {
        // Cada actividad sembrada referencia eje/institución existentes (integridad de herencia).
        $act = $this->db->table('actividades')->get()->getRowArray();
        $this->assertNotNull($act);
        $this->seeInDatabase('ejes', ['id_eje' => $act['id_eje']]);
        $this->seeInDatabase('instituciones', ['id_institucion' => $act['id_institucion']]);
    }

    public function testForeignKeyRestringeLineaHuerfana(): void
    {
        // Insertar una línea con id_eje inexistente debe ser rechazado por la FK (RESTRICT).
        $this->expectException(\CodeIgniter\Database\Exceptions\DatabaseException::class);
        $this->db->table('lineas')->insert([
            'id_linea' => 'LIN_HUERF',
            'nombre'   => 'Línea huérfana',
            'id_eje'   => 'EJE_NOEXISTE',
            'estatus'  => 'activo',
        ]);
    }
}
