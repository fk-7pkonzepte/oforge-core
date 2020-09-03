<?php

namespace ProductPlacement;

use Oforge\Engine\Modules\AdminBackend\Core\Services\BackendNavigationService;
use Oforge\Engine\Modules\Core\Abstracts\AbstractBootstrap;
use Oforge\Engine\Modules\Core\Helper\Statics;
use Oforge\Engine\Modules\TemplateEngine\Core\Services\TemplateRenderService;
use ProductPlacement\Controller\Backend\ProductPlacementController;
use ProductPlacement\Models\ProductPlacement;
use ProductPlacement\Services\ProductPlacementService;
use ProductPlacement\Twig\ProductPlacementExtension;

/**
 * Class Bootstrap
 *
 * @package ProductPlacement
 */
class Bootstrap extends AbstractBootstrap {

    public function __construct() {
        $this->endpoints    = [
            ProductPlacementController::class,
        ];
        $this->models       = [
            ProductPlacement::class,
        ];
        $this->services     = [
            'product.placement' => ProductPlacementService::class,
        ];
        $this->dependencies = [
            \Insertion\Bootstrap::class,
        ];
    }

    public function activate() {
        $backendNavigationService = Oforge()->Services()->get('backend.navigation');
        $backendNavigationService->add(BackendNavigationService::CONFIG_CONTENT);
        $backendNavigationService->add([
            'name'     => 'plugin_product_placement',
            'order'    => 100,
            'parent'   => BackendNavigationService::KEY_CONTENT,
            'icon'     => 'fa fa-tags',
            'path'     => 'backend_product_placement',
            'position' => 'sidebar',
        ]);
    }

    public function load() {
        /** @var TemplateRenderService $templateRenderer */
        $templateRenderer = Oforge()->Services()->get('template.render');
        $templateRenderer->View()->addExtension(new ProductPlacementExtension());
    }

}
