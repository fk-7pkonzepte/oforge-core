<?php

namespace Oforge\Engine\Modules\AdminBackend\Core\Controller\Backend;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Oforge\Engine\Modules\AdminBackend\Core\Abstracts\SecureBackendController;
use Oforge\Engine\Modules\AdminBackend\Core\Services\BackendNavigationService;
use Oforge\Engine\Modules\AdminBackend\Core\Services\DashboardWidgetsService;
use Oforge\Engine\Modules\Auth\Models\User\BackendUser;
use Oforge\Engine\Modules\Core\Annotation\Endpoint\EndpointAction;
use Oforge\Engine\Modules\Core\Annotation\Endpoint\EndpointClass;
use Oforge\Engine\Modules\Core\Exceptions\ConfigElementAlreadyExistException;
use Oforge\Engine\Modules\Core\Exceptions\ConfigOptionKeyNotExistException;
use Oforge\Engine\Modules\Core\Exceptions\ParentNotFoundException;
use Oforge\Engine\Modules\Core\Exceptions\ServiceNotFoundException;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Class DashboardController
 *
 * @package Oforge\Engine\Modules\AdminBackend\Core\Controller\Backend
 * @EndpointClass(path="/backend/dashboard", name="backend_dashboard", assetScope="Backend")
 */
class DashboardController extends SecureBackendController {

    public function initPermissions() {
        $this->ensurePermissions([
            'indexAction',
            'widgetsAction',
        ], BackendUser::ROLE_LOGGED_IN);
        $this->ensurePermissions([
            'buildAction',
            'fontAwesomeAction',
            'testAction',
        ], BackendUser::ROLE_ADMINISTRATOR);
    }

    /**
     * @param Request $request
     * @param Response $response
     *
     * @throws ServiceNotFoundException
     * @EndpointAction()
     */
    public function indexAction(Request $request, Response $response) {
        $data = [
            'page_header'             => 'Willkommen auf dem Dashboard',
            'page_header_description' => 'Hier finden Sie alle relevanten Informationen übersichtlich dargestellt.',
        ];

        Oforge()->View()->assign($data);
    }

    /**
     * @param Request $request
     * @param Response $response
     *
     * @throws ServiceNotFoundException
     * @EndpointAction()
     */
    public function buildAction(Request $request, Response $response) {
        Oforge()->Services()->get('assets.template')->build('', Oforge()->View()->get('meta')['route']['assetScope']);
    }

    /**
     * @param Request $request
     * @param Response $response
     *
     * @throws ORMException
     * @throws ServiceNotFoundException
     * @EndpointAction()
     */
    public function widgetsAction(Request $request, Response $response) {
        if ($_POST && isset($_POST['data'])) {
            $data = $_POST['data'];
            $user = Oforge()->View()->get('user');
            if ($user !== null) {
                /**  @var DashboardWidgetsService $dashboardWidgetsService */
                $dashboardWidgetsService = Oforge()->Services()->get('backend.dashboard.widgets');
                $dashboardWidgetsService->saveUserSettings($data);
            }

            Oforge()->View()->assign(['json' => $data]);
        }

    }

    /**
     * @param Request $request
     * @param Response $response
     * @EndpointAction()
     */
    public function fontAwesomeAction(Request $request, Response $response) {
    }

    /**
     * @param Request $request
     * @param Response $response
     * @EndpointAction()
     */
    public function ioniconsAction(Request $request, Response $response) {
    }

    /**
     * @param Request $request
     * @param Response $response
     * @EndpointAction()
     */
    public function helpAction(Request $request, Response $response) {
    }

    /**
     * @param Request $request
     * @param Response $response
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws ConfigElementAlreadyExistException
     * @throws ConfigOptionKeyNotExistException
     * @throws ServiceNotFoundException
     * @throws ParentNotFoundException
     * @EndpointAction()
     */
    public function testAction(Request $request, Response $response) {
        /** @var BackendNavigationService $backendNavigationService */
        $backendNavigationService = Oforge()->Services()->get('backend.navigation');
        $backendNavigationService->add(BackendNavigationService::CONFIG_ADMIN);
        $backendNavigationService->add([
            'name'     => 'help',
            'order'    => 99,
            'parent'   => BackendNavigationService::KEY_ADMIN,
            'icon'     => 'ion-help',
            'position' => 'sidebar',
        ]);
        $backendNavigationService->add([
            'name'     => 'ionicons',
            'order'    => 2,
            'parent'   => 'help',
            'icon'     => 'ion-nuclear',
            'path'     => 'backend_dashboard_ionicons',
            'position' => 'sidebar',
        ]);
        $backendNavigationService->add([
            'name'     => 'fontAwesome',
            'order'    => 1,
            'parent'   => 'help',
            'icon'     => 'fa-fort-awesome',
            'path'     => 'backend_dashboard_fontAwesome',
            'position' => 'sidebar',
        ]);
    }

}
