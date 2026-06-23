<?php

declare(strict_types=1);

use App\Services\MigracionService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Migración y conciliación (Sprint 4, doc 06 §4). Corre `mel:import` (vía MigracionService)
 * sobre un fixture CSV representativo en SQLite y verifica: limpieza de `#REF!`, descarte de
 * filas-plantilla, regeneración de `personas` por dedup y conciliación de conteos. El dataset
 * real del Excel v1.9 (≈988/762/279/132) se carga contra MySQL fuera de este entorno.
 *
 * @internal
 */
final class MigracionTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $namespace = 'App';
    protected $migrate   = true;
    protected $refresh   = true;

    private function importar(): array
    {
        $dir = dirname(__DIR__) . '/_support/fixtures/excel';

        return (new MigracionService())->importar($dir);
    }

    public function testConciliacionDeConteos(): void
    {
        $rep = $this->importar();

        $this->assertSame(3, $rep['actividades']['total']);
        $this->assertSame(1, $rep['actividades']['P']);
        $this->assertSame(1, $rep['actividades']['E']);
        $this->assertSame(1, $rep['actividades']['R']);
        $this->assertSame(1, $rep['procesos']);
        $this->assertSame(2, $rep['eventos_programados']);
        $this->assertSame(2, $rep['ejecuciones']['total']);
        $this->assertSame(2, $rep['ejecuciones']['con_fecha']);
        $this->assertSame(5, $rep['participaciones']);
        $this->assertSame(8, $rep['agregadas_suma']);
        $this->assertSame(13, $rep['cobertura_total']); // 5 nominales + 8 agregadas
    }

    public function testPersonasRegeneradasPorDedup(): void
    {
        $this->importar();

        // 5 participaciones nominales pero solo 3 personas únicas (José/Jose consolidan;
        // Mario queda en cola sin persona). Las personas se regeneran, no se copian.
        $this->assertSame(3, $this->db->table('personas')->countAllResults());
        $this->assertSame(5, $this->db->table('participaciones')->countAllResults());
    }

    public function testJoseYJoseConsolidanEnUnaPersona(): void
    {
        $this->importar();

        $rows = $this->db->table('participaciones')
            ->whereIn('nombres', ['José', 'Jose'])->get()->getResultArray();
        $this->assertCount(2, $rows);
        $ids = array_unique(array_map(static fn (array $r): string => (string) $r['id_persona'], $rows));
        $this->assertCount(1, $ids, 'José y Jose (sin acento) deben compartir id_persona.');
        $this->assertNotSame('', $ids[0]);
    }

    public function testSospechosoPorTelefonoEntraEnCola(): void
    {
        $rep = $this->importar();

        $this->assertSame(1, $rep['cola_revisar']);
        $mario = $this->db->table('participaciones')->where('nombres', 'Mario')->get()->getRowArray();
        $this->assertNotNull($mario);
        $this->assertSame('REVISAR', $mario['control_registro']);
        $this->assertNull($mario['id_persona']); // no se autofusiona
    }

    public function testRefsLimpiadosYPlantillaDescartada(): void
    {
        $rep = $this->importar();

        // #REF! en ACT_001.caso_excepcional y en Mario.correo -> 2 celdas limpiadas a null.
        $this->assertSame(2, $rep['refs_limpiados']);
        $this->assertSame(1, $rep['plantillas_descartadas']); // la fila "PLANTILLA" del Excel

        // Ninguna celda #REF! migrada, ni la fila-plantilla.
        $this->assertNull($this->db->table('actividades')->where('id_actividad', 'ACT_001')->get()->getRowArray()['caso_excepcional']);
        $this->assertSame(0, $this->db->table('actividades')->like('nombre', 'PLANTILLA')->countAllResults());
        $this->assertSame(3, $this->db->table('actividades')->countAllResults());
    }

    public function testNingunTableroInflado(): void
    {
        $this->importar();

        // El KPI de beneficiarios únicos cuenta personas reales (3), no las 5 filas ni 1000.
        $unicas = $this->db->table('participaciones')
            ->where('control_registro', 'OK')->where('id_persona IS NOT NULL', null, false)
            ->distinct()->select('id_persona')->countAllResults();
        $this->assertSame(3, $unicas);
    }
}
