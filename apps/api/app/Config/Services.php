<?php

namespace Config;

use App\Services\CurrentScope;
use CodeIgniter\Config\BaseService;

/**
 * Services Configuration file.
 *
 * Services are simply other classes/libraries that the system uses
 * to do its job. This is used by CodeIgniter to allow the core of the
 * framework to be swapped out easily without affecting the usage within
 * the rest of your application.
 *
 * This file holds any application-specific services, or service overrides
 * that you might need. An example has been included with the general
 * method format you should use for your service methods. For more examples,
 * see the core Services file at system/Config/Services.php.
 */
class Services extends BaseService
{
    /**
     * Ámbito de la petición autenticada (institución/rol), cargado por el filtro
     * `scope-institucion` y consumido por controladores y Repositories (ADR-004).
     */
    public static function currentScope(bool $getShared = true): CurrentScope
    {
        if ($getShared) {
            /** @var CurrentScope */
            return static::getSharedInstance('currentScope');
        }

        return new CurrentScope();
    }
}
