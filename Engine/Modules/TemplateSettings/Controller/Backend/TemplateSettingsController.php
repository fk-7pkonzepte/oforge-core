<?php

namespace Oforge\Engine\Modules\TemplateSettings\Controller\Backend;

use Oforge\Engine\Modules\AdminBackend\Abstracts\SecureBackendController;
use Oforge\Engine\Modules\Auth\Models\User\BackendUser;
use Oforge\Engine\Modules\TemplateEngine\Services\ScssVariableService;
use Oforge\Engine\Modules\TemplateEngine\Services\TemplateManagementService;
use Slim\Http\Request;
use Slim\Http\Response;

class TemplateSettingsController extends SecureBackendController
{
    /**
     * @param Request $request
     * @param Response $response
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Oforge\Engine\Modules\Core\Exceptions\NotFoundException
     * @throws \Oforge\Engine\Modules\Core\Exceptions\ServiceNotFoundException
     * @throws \Oforge\Engine\Modules\Core\Exceptions\TemplateNotFoundException
     * @throws \Oforge\Engine\Modules\TemplateEngine\Exceptions\InvalidScssVariableException
     */
    public function indexAction(Request $request, Response $response)
    {
        /** @var TemplateManagementService $templateManagementService */
        $templateManagementService = Oforge()->Services()->get('template.management');
        /** @var ScssVariableService $scssVariableService */
        $scssVariableService = Oforge()->Services()->get('scss.variables');

        if($request->isPost()) {
            $formData = $request->getParsedBody();
            if(isset($formData['selectedTheme'])) {
                $templateManagementService->activate($formData['selectedTheme']);
            }

            foreach ($formData as $key => $value) {
                if(strpos($key,'|') !== false) {
                    $arr = explode('|', $key);
                    $scssVariableService->update($arr[0], $value);
                }
            }

            $templateManagementService->build();
        }
        $scssData = $scssVariableService->getScope('Frontend');
        $templateData = $templateManagementService->list();
        Oforge()-> View()->assign(["scssVariables" => $scssData]);
        Oforge()-> View()->assign(["templates" => $templateData]);
    }

    public function initPermissions()
    {
        $this->ensurePermissions("indexAction", BackendUser::class, BackendUser::ROLE_ADMINISTRATOR);
    }
}
