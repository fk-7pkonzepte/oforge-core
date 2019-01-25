<?php

namespace Oforge\Engine\Modules\SystemSettings\Controller\Backend;

use Oforge\Engine\Modules\AdminBackend\Abstracts\SecureBackendController;
use Oforge\Engine\Modules\Auth\Models\User\BackendUser;
use Oforge\Engine\Modules\Core\Exceptions\ServiceNotFoundException;
use Oforge\Engine\Modules\Core\Services\ConfigService;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Class SystemSettingsGroupController
 *
 * @package Oforge\Engine\Modules\SystemSettings\Controller\Backend
 */
class SystemSettingsGroupController extends SecureBackendController {
    /**
     * @param Request $request
     * @param Response $response
     * @param array $args
     *
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Oforge\Engine\Modules\Core\Exceptions\ConfigElementNotFoundException
     */
    public function indexAction(Request $request, Response $response, array $args) {
        try {
            /** @var ConfigService $configService */
            $configService = Oforge()->Services()->get('config');
            if ($request->isPost()) {
                $formData = $request->getParsedBody();
                foreach ($formData as $key => $value) {
                    $configService->set($key, $value);
                }
            }

            $config = $configService->getGroupConfigs($args['group']);
            Oforge()->View()->assign([
                'page_header' => $args['group'],
                'config'      => $config,
                'groupname'   => $args['group'],
            ]);
        } catch (ServiceNotFoundException $exception) {
            $response->withRedirect($request->getRequestTarget());
        }
    }

    public function initPermissions() {
        $this->ensurePermissions('indexAction', BackendUser::class, BackendUser::ROLE_ADMINISTRATOR);
    }

}
