<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;

/**
 * Crea los 4 usuarios demo (Shield) sobre datos reales ya migrados (mel:import).
 * Liga capturista/coordinación a instituciones reales del Excel; dirección/admin sin ámbito.
 */
class UsuariosRealesSeeder extends Seeder
{
    public const DEV_PASSWORD = 'MelDemo2026!';

    /** Roles canónicos del sistema MEL (independiente de InitialSeeder). */
    private const ROLES = [
        ['id_rol' => 1, 'clave' => 'capturista',    'nombre' => 'Capturista',      'descripcion' => 'Registra el día a día dentro de su institución/territorio.'],
        ['id_rol' => 2, 'clave' => 'coordinacion',   'nombre' => 'Coordinación MEL', 'descripcion' => 'Garantiza calidad y trazabilidad; valida, resuelve duplicados, gestiona metas.'],
        ['id_rol' => 3, 'clave' => 'direccion',      'nombre' => 'Dirección',        'descripcion' => 'Consume indicadores confiables; solo lectura.'],
        ['id_rol' => 4, 'clave' => 'administrador',  'nombre' => 'Administrador',    'descripcion' => 'Gestión técnica de usuarios y configuración.'],
    ];

    public function run(): void
    {
        // Sembrar roles si la tabla está vacía (el runbook no corre InitialSeeder).
        if ($this->db->table('roles')->countAllResults() === 0) {
            $this->db->table('roles')->insertBatch(self::ROLES);
        }

        $resultado = $this->db->table('roles')->get();
        $roles     = $resultado === false ? [] : $resultado->getResultArray();
        $idRol     = [];
        foreach ($roles as $r) {
            $idRol[$r['clave']] = (int) $r['id_rol'];
        }

        $usuarios = [
            ['email' => 'capturista@demo.test',   'nombre' => 'Capturista Demo',   'rol' => 'capturista',    'inst' => 'INST_001'],
            ['email' => 'coordinacion@demo.test', 'nombre' => 'Coordinación Demo', 'rol' => 'coordinacion',  'inst' => 'INST_001'],
            ['email' => 'direccion@demo.test',    'nombre' => 'Dirección Demo',    'rol' => 'direccion',     'inst' => null],
            ['email' => 'admin@demo.test',        'nombre' => 'Admin Sistema',     'rol' => 'administrador', 'inst' => null],
        ];

        $provider = new UserModel();
        foreach ($usuarios as $u) {
            if ($this->db->table('usuarios')->where('email', $u['email'])->countAllResults() > 0) {
                continue;
            }
            $entity = new User(['username' => null]);
            $provider->save($entity);
            $saved = $provider->findById($provider->getInsertID());
            if ($saved === null) {
                throw new \RuntimeException("No se pudo crear el usuario Shield: {$u['email']}.");
            }
            $saved->createEmailIdentity(['email' => $u['email'], 'password' => self::DEV_PASSWORD]);
            // El rol del login (AuthController) se deriva del grupo de Shield (getGroups()),
            // no de usuarios.id_rol. Sin esto, user.rol llega null a la SPA y la UI no renderiza.
            $saved->addGroup($u['rol']);

            $idUsuario = $saved->id;
            $this->db->table('usuarios')->insert([
                'id_usuario' => $idUsuario,
                'nombre'     => $u['nombre'],
                'email'      => $u['email'],
                'id_rol'     => $idRol[$u['rol']],
                'estatus'    => 'activo',
            ]);
            if ($u['inst'] !== null) {
                $this->db->table('usuario_institucion')->insert([
                    'id_usuario'     => $idUsuario,
                    'id_institucion' => $u['inst'],
                ]);
            }
        }
    }
}
