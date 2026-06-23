<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Models\ComponenteModel;
use App\Models\EjeModel;
use App\Models\InstitucionModel;
use App\Models\LineaModel;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

/**
 * Catálogos dimensionales en solo lectura (doc 05 §3). El contrato congelado (`api.ts`)
 * los devuelve como arrays simples (no paginados). Instituciones se acota al ámbito.
 */
class CatalogoController extends BaseApiController
{
    public function ejes(): ResponseInterface
    {
        return $this->ok((new EjeModel())->orderBy('orden_visualizacion', 'ASC')->findAll());
    }

    public function lineas(): ResponseInterface
    {
        $model = new LineaModel();
        $idEje = $this->request->getGet('id_eje');
        if (is_string($idEje) && $idEje !== '') {
            $model->where('id_eje', $idEje);
        }

        return $this->ok($model->orderBy('orden_visualizacion', 'ASC')->findAll());
    }

    public function componentes(): ResponseInterface
    {
        $model  = new ComponenteModel();
        $idInst = $this->request->getGet('id_institucion');
        if (is_string($idInst) && $idInst !== '') {
            $model->where('id_institucion', $idInst);
        }

        return $this->ok($model->orderBy('orden_visualizacion', 'ASC')->findAll());
    }

    public function instituciones(): ResponseInterface
    {
        $scope = Services::currentScope();
        $model = new InstitucionModel();

        if (! $scope->isGlobal()) {
            $ambito = $scope->instituciones();
            $model->whereIn('id_institucion', $ambito === [] ? ['__DENY__'] : $ambito);
        }

        return $this->ok($model->orderBy('orden_visualizacion', 'ASC')->findAll());
    }
}
