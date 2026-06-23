<?php

declare(strict_types=1);

namespace Config;

use CodeIgniter\Shield\Config\AuthGroups as ShieldAuthGroups;

/**
 * Grupos y permisos de Sistema MEL (RBAC, doc 04 §A01).
 * Un usuario tiene UN grupo = su rol. El permiso `data.viewAll` distingue a los
 * roles globales (ven todas las instituciones) del capturista (acotado a su ámbito).
 */
class AuthGroups extends ShieldAuthGroups
{
    public string $defaultGroup = 'capturista';

    /** @var array<string, array<string, string>> */
    public array $groups = [
        'capturista' => [
            'title'       => 'Capturista',
            'description' => 'Registra el día a día dentro de su institución/territorio.',
        ],
        'coordinacion' => [
            'title'       => 'Coordinación MEL',
            'description' => 'Garantiza calidad y trazabilidad; valida, deduplica, gestiona catálogos y metas.',
        ],
        'direccion' => [
            'title'       => 'Dirección',
            'description' => 'Consume indicadores confiables; solo lectura.',
        ],
        'administrador' => [
            'title'       => 'Administrador',
            'description' => 'Gestión técnica de usuarios y configuración.',
        ],
    ];

    public array $permissions = [
        'data.viewAll'    => 'Ve datos de todas las instituciones (omite el filtrado por ámbito).',
        'data.capture'    => 'Captura/edita la cadena MEL y productos en su ámbito.',
        'data.validate'   => 'Valida registros, resuelve duplicados y reclasifica P/E/R.',
        'catalogs.manage' => 'Gestiona catálogos (ejes/líneas/componentes/instituciones/actividades) y metas.',
        'users.manage'    => 'Gestiona usuarios y configuración del sistema.',
        'audit.view'      => 'Consulta la bitácora de auditoría.',
    ];

    public array $matrix = [
        'capturista' => [
            'data.capture',
        ],
        'coordinacion' => [
            'data.viewAll',
            'data.capture',
            'data.validate',
            'catalogs.manage',
            'audit.view',
        ],
        'direccion' => [
            'data.viewAll',
            'audit.view',
        ],
        'administrador' => [
            'data.viewAll',
            'catalogs.manage',
            'users.manage',
            'audit.view',
        ],
    ];
}
