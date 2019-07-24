<?php

namespace Oforge\Engine\Modules\Auth\Controller;

use Oforge\Engine\Modules\Auth\Services\PermissionService;
use Oforge\Engine\Modules\Core\Abstracts\AbstractController;
use Oforge\Engine\Modules\Core\Exceptions\ServiceNotFoundException;

/**
 * Class SecureController
 *
 * @package Oforge\Engine\Modules\Auth\Controller
 */
class SecureController extends AbstractController {

    public function initPermissions() {
    }

    /**
     * @param string $method
     * @param string $userType
     * @param int|null $role
     */
    protected function ensurePermissions(string $method, string $userType, ?int $role = null) {
        try {
            /** @var PermissionService $permissionService */
            $permissionService = Oforge()->Services()->get('permissions');
            $permissionService->put(static::class, $method, $userType, $role);
        } catch (ServiceNotFoundException $exception) {
            Oforge()->Logger()->logException($exception);
        }
    }

}
