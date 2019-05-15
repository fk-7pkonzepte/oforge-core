<?php

namespace Insertion\Controller\Backend;

use Doctrine\ORM\ORMException;
use Insertion\Services\AttributeService;
use Insertion\Services\InsertionMockService;
use Insertion\Services\InsertionTypeService;
use Oforge\Engine\Modules\AdminBackend\Core\Abstracts\SecureBackendController;
use Oforge\Engine\Modules\Core\Annotation\Endpoint\EndpointAction;
use Oforge\Engine\Modules\Core\Annotation\Endpoint\EndpointClass;
use Oforge\Engine\Modules\Core\Exceptions\ServiceNotFoundException;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Class FrontendHelpdeskController
 *
 * @package FrontendInsertion\Controller\Backend
 * @EndpointClass(path="/backend/insertion/attribute", name="backend_attribute", assetScope="Backend")
 */
class BackendAttributeController extends SecureBackendController {
    /**
     * @param Request $request
     * @param Response $response
     *
     * @throws ORMException
     * @throws ServiceNotFoundException
     */
    public function indexAction(Request $request, Response $response) {
    }

    /**
     * @param Request $request
     * @param Response $response
     * @EndpointAction(path="/create")
     */
    public function createAction(Request $request, Response $response) {

        //if ($request->isPost()) {}
    }

    public function initPermissions() {
        parent::initPermissions(); // TODO: Change the autogenerated stub
    }
}
