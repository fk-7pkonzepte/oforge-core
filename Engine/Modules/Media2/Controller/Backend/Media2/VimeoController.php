<?php

namespace Oforge\Engine\Modules\Media2\Controller\Backend\Media2;

use Oforge\Engine\Modules\AdminBackend\Core\Abstracts\SecureBackendController;
use Oforge\Engine\Modules\Auth\Models\User\BackendUser;
use Oforge\Engine\Modules\Core\Annotation\Endpoint\EndpointAction;
use Oforge\Engine\Modules\Core\Annotation\Endpoint\EndpointClass;
use Oforge\Engine\Modules\Core\Exceptions\ServiceNotFoundException;
use Oforge\Engine\Modules\Media\Services\MediaService;
use Slim\Http\Request;
use Slim\Http\Response;

/**
 * Class ImageController
 *
 * @package Oforge\Engine\Modules\Media2\Controller\Backend\Media2
 * @EndpointClass(path="/backend/media2/vimeo", name="backend_media_vimeo", assetScope="Backend")
 */
class VimeoController extends SecureBackendController {

    /**
     * @inheritdoc
     */
    public function initPermissions() {
        $this->ensurePermissions([
            'indexAction',
        ], BackendUser::ROLE_MODERATOR);
    }

    /**
     * @param Request $request
     * @param Response $response
     *
     * @EndpointAction()
     */
    public function indexAction(Request $request, Response $response) {
    }

}
