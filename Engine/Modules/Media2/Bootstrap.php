<?php

namespace Oforge\Engine\Modules\Media2;

use Oforge\Engine\Modules\AdminBackend\Core\Services\BackendNavigationService;
use Oforge\Engine\Modules\Core\Abstracts\AbstractBootstrap;
use Oforge\Engine\Modules\I18n\Helper\I18N;

/**
 * Class Bootstrap
 *
 * @package Oforge\Engine\Modules\Media
 */
class Bootstrap extends AbstractBootstrap {

    public function __construct() {
        $this->endpoints = [
            // Controller\Backend\Media2\AjaxController::class,
            Controller\Backend\Media2\ImageController::class,
            Controller\Backend\Media2\VimeoController::class,
        ];

        $this->models = [
            Models\Image::class,
        ];

        $this->services = [
            // 'media2.image' => Services\ImageUploadService::class,
            // 'media2.vimeo' => Services\VimeoUploadService::class,
        ];
    }

    public function activate() {
        /** @var BackendNavigationService $backendNavigationService */
        $backendNavigationService = Oforge()->Services()->get('backend.navigation');
        I18N::translate('module_media', [
            'en' => 'Media',
            'de' => 'Medien',
        ]);
        $backendNavigationService->add([
            'name'     => 'module_media',
            'order'    => 3,
            'position' => 'sidebar',
        ]);
        I18N::translate('module_media_image', [
            'en' => 'Images',
            'de' => 'Bilder',
        ]);
        $backendNavigationService->add([
            'name'     => 'module_media_image',
            'parent'   => 'module_media',
            'icon'     => 'fa fa-picture-o',
            'path'     => 'backend_media_image',
            'position' => 'sidebar',
            'order'    => 1,
        ]);
        I18N::translate('module_media_vimeo', [
            'en' => 'Vimeo video',
            'de' => 'Vimeo-Video',
        ]);
        $backendNavigationService->add([
            'name'     => 'module_media_vimeo',
            'parent'   => 'module_media',
            'icon'     => 'fa fa-vimeo',
            'path'     => 'backend_media_vimeo',
            'position' => 'sidebar',
            'order'    => 2,
        ]);
    }

    /** @inheritDoc */
    public function load() {
        // /** @var TemplateRenderService $templateRenderer */
        // $templateRenderer = Oforge()->Services()->get('template.render');
        // $templateRenderer->View()->addExtension(new MediaExtension());
    }

}
