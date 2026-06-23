<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

/** Tabla de dominio `usuarios` (extensión sobre Shield `users`). doc 03 §3.7. */
class UsuarioModel extends Model
{
    protected $table            = 'usuarios';
    protected $primaryKey       = 'id_usuario';
    protected $useAutoIncrement = false; // id_usuario = id de Shield
    protected $returnType       = 'array';
    protected $useTimestamps    = false;

    // Anti mass-assignment: nunca campos calculados por el servidor (doc 04 §A08).
    protected $allowedFields = ['id_usuario', 'nombre', 'email', 'id_rol', 'estatus'];
}
