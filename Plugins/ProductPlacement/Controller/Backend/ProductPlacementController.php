<?php

namespace ProductPlacement\Controller\Backend;

use Doctrine\ORM\ORMException;
use Oforge\Engine\Modules\Core\Annotation\Endpoint\EndpointClass;
use Oforge\Engine\Modules\Core\Exceptions\ServiceNotFoundException;
use Oforge\Engine\Modules\CRUD\Controller\Backend\BaseCrudController;
use Oforge\Engine\Modules\CRUD\Enum\CrudDataTypes;
use ProductPlacement\Models\ProductPlacement;
use ProductPlacement\Services\ProductPlacementService;

/**
 * Class CategoryController
 *
 * @package ProductPlacement\Controller\Backend\ProductPlacement
 * @EndpointClass(path="/backend/product-placement", name="backend_product_placement", assetScope="Backend")
 */
class ProductPlacementController extends BaseCrudController {
    /** @var string $model */
    protected $model = ProductPlacement::class;
    /** @var array $modelProperties */
    protected $modelProperties = [
        [
            'name'  => 'source',
            'type'  => CrudDataTypes::TEXT,
            'label' => [
                'key'     => 'plugin_product_placement_property_source',
                'default' => [
                    'en' => 'Source',
                    'de' => 'Quelle',
                ],
            ],
            'crud'  => [
                'index'  => 'readonly',
                'view'   => 'readonly',
                'create' => 'editable',
                'update' => 'editable',
                'delete' => 'readonly',
            ],
        ],# source
        [
            'name'     => 'tags',
            'type'     => CrudDataTypes::SELECT,
            'label'    => [
                'key'     => 'plugin_product_placement_property_tags',
                'default' => [
                    'en' => 'Tags',
                    'de' => 'Tags',
                ],
            ],
            'crud'     => [
                'index'  => 'readonly',
                'create' => 'editable',
                'update' => 'editable',
            ],
            'list'     => 'getTags',
            'multiple' => true,
        ],#tags
    ];

    /**
     * @return array
     * @throws ORMException
     * @throws ServiceNotFoundException
     */
    protected function getTags() {
        /** @var ProductPlacementService $productPlacementService */
        $productPlacementService = Oforge()->Services()->get('product.placement');
        $tags                    = $productPlacementService->getAllTags();
        Oforge()->View()->assign(['tags' => $tags]);

        return $tags;
    }
}
