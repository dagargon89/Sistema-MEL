<?php

declare(strict_types=1);

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\Shield\Entities\User;
use CodeIgniter\Shield\Models\UserModel;

/**
 * Identidad inicial (Sprint 1): crea los usuarios Shield desde `db.json`, les asigna
 * el grupo = su rol, y puebla las tablas de dominio `usuarios` y `usuario_institucion`
 * (ámbito, ADR-004). Encadena InitialSeeder (roles + dimensiones). Idempotente.
 *
 * Contraseña de desarrollo común para las cuentas demo (cámbiala en producción).
 */
class Sprint1Seeder extends Seeder
{
    public const DEV_PASSWORD = 'MelDemo2026!';

    public function run(): void
    {
        $this->call(InitialSeeder::class);

        if ($this->db->table('usuarios')->countAllResults() > 0) {
            return; // idempotente
        }

        $path = __DIR__ . '/data/db.json';
        if (! is_file($path)) {
            return;
        }
        /** @var array<string, mixed> $data */
        $data     = json_decode((string) file_get_contents($path), true) ?? [];
        $usuarios = is_array($data['usuarios'] ?? null) ? $data['usuarios'] : [];
        $ambitos  = is_array($data['usuario_institucion'] ?? null) ? $data['usuario_institucion'] : [];
        $roles    = is_array($data['roles'] ?? null) ? $data['roles'] : [];
        if ($usuarios === []) {
            return;
        }

        /** @var array<int, string> $claveDeRol */
        $claveDeRol = [];
        foreach ($roles as $r) {
            $claveDeRol[(int) $r['id_rol']] = (string) $r['clave'];
        }

        $users = model(UserModel::class);
        /** @var array<int, int> $idMap db.json id_usuario => id de Shield */
        $idMap = [];

        foreach ($usuarios as $u) {
            $email = (string) $u['email'];

            $entity = new User(['username' => null]);
            $users->save($entity);
            $shieldId = (int) $users->getInsertID();

            $saved = $users->findById($shieldId);
            if (! $saved instanceof User) {
                continue;
            }
            // createEmailIdentity hashea la contraseña sin pasar por el validador Pwned (sin red).
            $saved->createEmailIdentity(['email' => $email, 'password' => self::DEV_PASSWORD]);
            $saved->addGroup($claveDeRol[(int) $u['id_rol']] ?? 'capturista');

            $this->db->table('usuarios')->insert([
                'id_usuario' => $shieldId,
                'nombre'     => (string) $u['nombre'],
                'email'      => $email,
                'id_rol'     => (int) $u['id_rol'],
                'estatus'    => (string) ($u['estatus'] ?? 'activo'),
            ]);

            $idMap[(int) $u['id_usuario']] = $shieldId;
        }

        foreach ($ambitos as $row) {
            $orig = (int) $row['id_usuario'];
            if (! isset($idMap[$orig])) {
                continue;
            }
            $this->db->table('usuario_institucion')->insert([
                'id_usuario'     => $idMap[$orig],
                'id_institucion' => (string) $row['id_institucion'],
            ]);
        }
    }
}
