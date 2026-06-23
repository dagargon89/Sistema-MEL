<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/** Pertenencia usuario↔institución = ámbito (ADR-004). doc 03 §3.7. */
class UsuarioInstitucionModel extends Model
{
    protected $table         = 'usuario_institucion';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['id_usuario', 'id_institucion'];

    /**
     * Instituciones del ámbito de un usuario.
     *
     * @return list<string>
     */
    public function institucionesDe(int $idUsuario): array
    {
        $out = [];
        foreach ($this->where('id_usuario', $idUsuario)->findAll() as $row) {
            if (is_array($row) && is_scalar($row['id_institucion'] ?? null)) {
                $out[] = (string) $row['id_institucion'];
            }
        }

        return $out;
    }
}
