<?php

namespace Oforge\Engine\Modules\SystemSettings;

use Oforge\Engine\Modules\AdminBackend\Services\BackendNavigationService;
use Oforge\Engine\Modules\Core\Abstracts\AbstractBootstrap;
use Oforge\Engine\Modules\SystemSettings\Controller\Backend\SystemSettingsController;
use Oforge\Engine\Modules\SystemSettings\Controller\Backend\SystemSettingsGroupController;

/**
 * Class SystemSettings-Bootstrap
 *
 * @package Oforge\Engine\Modules\SystemSettings
 */
class Bootstrap extends AbstractBootstrap {

    /**
     * Bootstrap constructor.
     */
    public function __construct() {
        $this->endpoints = [
            '/backend/settings[/]'      => [
                'controller'  => SystemSettingsController::class,
                'name'        => 'backend_settings',
                'asset_scope' => 'Backend',
            ],
            '/backend/settings/{group}' => [
                'controller'  => SystemSettingsGroupController::class,
                'name'        => 'backend_settings_group',
                'asset_scope' => 'Backend',
            ],
        ];
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Oforge\Engine\Modules\Core\Exceptions\ConfigElementAlreadyExistException
     * @throws \Oforge\Engine\Modules\Core\Exceptions\ConfigOptionKeyNotExistException
     * @throws \Oforge\Engine\Modules\Core\Exceptions\ParentNotFoundException
     * @throws \Oforge\Engine\Modules\Core\Exceptions\ServiceNotFoundException
     */
    public function activate() {
        /**@var BackendNavigationService $backendNavigation */
        $backendNavigation = Oforge()->Services()->get('backend.navigation');

        $backendNavigation->put([
            'name'   => 'backend_settings',
            'order'  => 100,
            'parent' => 'admin',
            'icon'   => 'fa fa-gears',
            'path'   => 'backend_settings',
        ]);

        $backendNavigation->put([
            'name'    => 'backend_settings_group',
            'parent'  => 'backend_settings',
            'visible' => false,
            'path'    => 'backend_settings_group',
        ]);
    }
}
