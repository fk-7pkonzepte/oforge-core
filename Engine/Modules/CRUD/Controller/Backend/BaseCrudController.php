<?php

namespace Oforge\Engine\Modules\CRUD\Controller\Backend;

use Exception;
use Oforge\Engine\Modules\AdminBackend\Core\Abstracts\SecureBackendController;
use Oforge\Engine\Modules\Auth\Models\User\BackendUser;
use Oforge\Engine\Modules\Core\Exceptions\ServiceNotFoundException;
use Oforge\Engine\Modules\Core\Helper\ArrayHelper;
use Oforge\Engine\Modules\CRUD\Services\GenericCrudService;
use Oforge\Engine\Modules\I18n\Helper\I18N;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Router;

/**
 * Class CrudController
 *
 * @package Oforge\Engine\Modules\CRUD\Controller\Backend
 */
class BaseCrudController extends SecureBackendController {
    /** @var string $baseEndpointName */
    protected static $baseEndpointName = null;
    /** @var string $model */
    protected $model = null;
    /**
     * Define model properties (editor, visibility in index table and crate/update forms) e.g.
     *      protected $modelProperties = [
     *          [
     *              // Property name. Required
     *              'name'      => 'id',
     *               // Custom i18n key for label of form field, default('crud_<Module>_<ModelName>_<propertyName>' with name as default, or 'ID' default if name = id).
     *               // String key or array with key and default value.
     *              'label'     => 'label_id' | ['key' => 'label_id', 'default' => 'ID'],
     *              'type'      => CrudDataTypes::..., // Required
     *              'crud'      => [    // default = off (not rendered). (Required)
     *                  'index'     => 'off|readonly|editable',
     *                  'view'      => 'off|readonly|editable',
     *                  'create'    => 'off|readonly|editable',
     *                  'update'    => 'off|readonly|editable',
     *                  'delete'    => 'off|readonly|editable',
     *              ],
     *              'list'          => [ // If type = select. Name of a protected function to create a dynamic array (e.g. 'getListUsers', or a static array. (Required)
     *                  <value> => <option text or label e.g. 'backend_crud_property__<name>_<value>'>  // value => (text|(i18n-)label) pair
     *              ],
     *                  'listI18nLabel' => true,    // If type = select. Is Select label i18n-label? (Optional)
     *              'editor' => [       // Configuration for field editor.
     *                  'default'       => '',      // Default value. (Optional)
     *                  'custom'        => '...'    // If type = custom. Twig path for include.
     *                  'pattern'       => '...',   // If type = string. (Optional)
     *                  'placeholder'   => ...,     //. (Optional)
     *                  'maxlength'     => ...,     // If type = string|text. (Optional)
     *                  'min'           => '...',   // If type = int|float|currency. (Optional)
     *                  'max"           => ...,     // If type = string|text. (Optional)
     *                  'step"          => ...,     // If type = string|text. (Optional)
     *                  'multiple'      => false,   // If type = select. (Optional)
     *                  'size'          => ...,     // If type = select. (Optional)
     *              ],
     *              'renderer' => [ // Configuration for renderer
     *                  'alignment' => 'left|center|right', // If type = int|float|currency then default = right otherwise left. (Optional)
     *                  'custom'    => '...'        // If type = custom. Twig path for include.
     *              ],
     *          ], ...
     *      ];
     *
     * @var array|null $modelProperties
     */
    protected $modelProperties = null;
    /**
     * Enable or disable crud actions for this model with
     *      protected $crudActions = [
     *          'index'     => true, // enable view
     *          'create'    => true, // enable create button and view
     *          'view'      => true, // enable update button and view
     *          'update'    => true, // enable update button and view
     *          'delete'    => true, // enable delete button and view
     *      ];
     *
     * @var array $crudActions Keys of 'add|edit|delete'
     */
    protected $crudActions = [
        'index'  => true,
        'create' => true,
        'view'   => true,
        'update' => true,
        'delete' => true,
    ];
    /**
     * @var int $crudPermission
     */
    protected $crudPermission = BackendUser::ROLE_MODERATOR;
    /** @var GenericCrudService $crudService */
    protected $crudService;
    /** @var string $moduleModelName */
    private $moduleModelName;
    /** @var Router $router */
    private $router;

    /**
     * CrudController constructor.
     *
     * @throws ServiceNotFoundException
     */
    public function __construct() {
        if (is_null($this->model) || is_null($this->modelProperties)) {
            echo 'Properties "$model" and "$modelProperties" must be override!';
            die();
        }
        $this->crudService     = Oforge()->Services()->get('crud');
        $this->moduleModelName = '';
        if (isset($this->model)) {
            $parts = explode('\\Models\\', $this->model, 2);
            if (count($parts) === 2) {
                $module                = substr($parts[0], 1 + strrpos($parts[0], '\\'));
                $modelName             = $parts[1];
                $this->moduleModelName = $module . '_' . $modelName;
            }
        }
    }

    /**
     * Endpoints config for Bootstrap class.
     *
     * @param array $config
     *
     * @return array
     */
    public static function getBootstrapEndpointsArray($config = []) : array {
        if (is_null(static::$baseEndpointName)) {
            echo 'Properties "$baseEndpointName" must be override!';
            die();
        }
        $endpointsConfig = array_merge([
            'controller'  => static::class,
            'name'        => static::$baseEndpointName,
            'asset_scope' => 'Backend',
        ], $config);

        return $endpointsConfig;
    }

    /**
     * @param Request $request
     * @param Response $response
     *
     * @return Response
     */
    public function indexAction(Request $request, Response $response) {
        $params = $request->getParams();
        if ($request->isPost() && !empty($params)) {
            if (isset($params['action'])) {
                $methodName = 'handleIndex' . ucfirst($params['action']);
                if (method_exists($this, $methodName)) {
                    $this->{$methodName}($params);
                }

                return $this->redirect($response, 'index');
            }
        }
        $params   = $request->getQueryParams();
        $entities = $this->crudService->list($this->model, $params);
        if (Oforge()->View()->Flash()->hasData($this->moduleModelName)) {
            $postData = Oforge()->View()->Flash()->getData($this->moduleModelName);
            Oforge()->View()->Flash()->clearData($this->moduleModelName);
        }
        if (!empty($entities)) {
            foreach ($entities as $index => $entity) {
                if (!empty($entity)) {
                    $entity = $this->prepareItemData($entity, 'index');
                    if (isset($postData[$index])) {
                        $entity = ArrayHelper::mergeRecursive($entity, $postData[$index]);
                    }
                    $entities[$index] = $entity;
                }
            }
        }
        list($properties, $hasEditors) = $this->filterPropertiesFor('index');
        $hasRowActions = false;
        if (isset($this->crudActions)) {
            $actionKeys = ['view', 'update', 'delete'];
            foreach ($actionKeys as $actionKey) {
                if (isset($this->crudActions[$actionKey]) && $this->crudActions[$actionKey]) {
                    $hasRowActions = true;
                    break;
                }
            }
        }

        Oforge()->View()->assign([
            'crud' => [
                'context'       => 'index',
                'properties'    => $properties,
                'model'         => $this->moduleModelName,
                'actions'       => $this->crudActions,
                'hasEditors'    => $hasEditors,
                'hasRowActions' => $hasRowActions,
                'items'         => $entities,
            ],
        ]);

        return $response;
    }

    /**
     * @param Request $request
     * @param Response $response
     *
     * @return Response
     * @throws ServiceNotFoundException
     */
    public function createAction(Request $request, Response $response) {
        $params = $request->getParams();
        if ($request->isPost() && !empty($params)) {
            try {
                $this->crudService->create($this->model, $params['data']);
                Oforge()->View()->Flash()->addMessage('success', I18N::translate('backend_crud_msg_create_success', 'Entity successfully created.'));

                return $this->redirect($response, 'index');
            } catch (Exception $exception) {
                Oforge()->View()->Flash()->addMessage('danger', I18N::translate('backend_crud_msg_create_failed', 'Entity creation failed.'));
                Oforge()->View()->Flash()->addExceptionMessage('danger', $exception);
                Oforge()->View()->Flash()->setData($this->moduleModelName, $params['data']);

                return $this->redirect($response, 'create');
            }
        }
        $entity = $this->prepareItemData([], 'create');
        if (Oforge()->View()->Flash()->hasData($this->moduleModelName)) {
            $postData = Oforge()->View()->Flash()->getData($this->moduleModelName);
            $entity   = ArrayHelper::mergeRecursive($entity, $postData);
            Oforge()->View()->Flash()->clearData($this->moduleModelName);
        }
        list($properties, $hasEditors) = $this->filterPropertiesFor('create');
        Oforge()->View()->assign([
            'crud' => [
                'context'    => 'create',
                'properties' => $properties,
                'model'      => $this->moduleModelName,
                'actions'    => $this->crudActions,
                'item'       => $entity,
            ],
        ]);

        return $response;
    }

    /**
     * @param Request $request
     * @param Response $response
     *
     * @return Response
     */
    public function viewAction(Request $request, Response $response) {
        $params = $request->getParams();

        $entity = $this->crudService->getById($this->model, $params['id']);
        if (!empty($entity)) {
            $entity = $this->prepareItemData($entity, 'view');
        }
        list($properties, $hasEditors) = $this->filterPropertiesFor('view');
        Oforge()->View()->assign([
            'crud' => [
                'context'    => 'view',
                'properties' => $properties,
                'model'      => $this->moduleModelName,
                'actions'    => $this->crudActions,
                'item'       => $entity,
            ],
        ]);

        return $response;
    }

    /**
     * @param Request $request
     * @param Response $response
     *
     * @return Response
     * @throws ServiceNotFoundException
     */
    public function updateAction(Request $request, Response $response) {
        $params = $request->getParams();
        if ($request->isPost() && !empty($params)) {
            try {
                $data = $this->convertData($params['data']);
                $this->crudService->update($this->model, $data);
                Oforge()->View()->Flash()->addMessage('success', I18N::translate('backend_crud_msg_update_success', 'Entity successfully updated.'));
            } catch (Exception $exception) {
                Oforge()->View()->Flash()->addMessage('danger', I18N::translate('backend_crud_msg_update_failed', 'Entity update failed.'));
                Oforge()->View()->Flash()->addExceptionMessage('danger', $exception);
                Oforge()->View()->Flash()->setData($this->moduleModelName, $params['data']);
            }

            return $this->redirect($response, 'update', [], ['id' => $params['id']]);
        }
        $entity = $this->crudService->getById($this->model, $params['id']);
        if (!empty($entity)) {
            $entity = $this->prepareItemData($entity, 'update');
        }
        if (Oforge()->View()->Flash()->hasData($this->moduleModelName)) {
            $postData = Oforge()->View()->Flash()->getData($this->moduleModelName);
            $entity   = ArrayHelper::mergeRecursive($entity, $postData);
            Oforge()->View()->Flash()->clearData($this->moduleModelName);
        }
        list($properties, $hasEditors) = $this->filterPropertiesFor('update');
        Oforge()->View()->assign([
            'crud' => [
                'context'    => 'update',
                'properties' => $properties,
                'model'      => $this->moduleModelName,
                'actions'    => $this->crudActions,
                'item'       => $entity,
            ],
        ]);

        return $response;
    }

    /**
     * @param Request $request
     * @param Response $response
     *
     * @return Response
     * @throws ServiceNotFoundException
     */
    public function deleteAction(Request $request, Response $response) {
        $params = $request->getParams();
        if ($request->isPost() && !empty($params)) {
            try {
                $this->crudService->delete($this->model, $params['id']);
                Oforge()->View()->Flash()->addMessage('success', I18N::translate('backend_crud_msg_delete_success', 'Entity successfully delete.'));

                return $this->redirect($response, 'index');
            } catch (Exception $exception) {
                Oforge()->View()->Flash()->addMessage('danger', I18N::translate('backend_crud_msg_delete_failed', 'Entity delete failed.'));
                Oforge()->View()->Flash()->addExceptionMessage('danger', $exception);

                return $this->redirect($response, 'delete', [], ['id' => $params['id']]);
            }
        }
        $entity = $this->crudService->getById($this->model, $params['id']);
        if (!empty($entity)) {
            $entity = $this->prepareItemData($entity, 'delete');
        }
        list($properties, $hasEditors) = $this->filterPropertiesFor('delete');
        Oforge()->View()->assign([
            'crud' => [
                'context'    => 'delete',
                'properties' => $properties,
                'model'      => $this->moduleModelName,
                'actions'    => $this->crudActions,
                'item'       => $entity,
            ],
        ]);

        return $response;
    }

    /**
     * @inheritdoc
     * @throws ServiceNotFoundException
     */
    public function initPermissions() {
        if ($this->crudActions['index'] ?? true) {
            $this->ensurePermissions('indexAction', BackendUser::class, $this->crudPermission);
        }
        if ($this->crudActions['create'] ?? true) {
            $this->ensurePermissions('createAction', BackendUser::class, $this->crudPermission);
        }
        if ($this->crudActions['view'] ?? true) {
            $this->ensurePermissions('viewAction', BackendUser::class, $this->crudPermission);
        }
        if ($this->crudActions['update'] ?? true) {
            $this->ensurePermissions('updateAction', BackendUser::class, $this->crudPermission);
        }
        if ($this->crudActions['delete'] ?? true) {
            $this->ensurePermissions('deleteAction', BackendUser::class, $this->crudPermission);
        }
    }

    /**
     * Convert form data to model data, e.g. string to DateTime.
     *
     * @param array $data
     *
     * @return array
     */
    protected function convertData(array $data) : array {
        return $data;
    }

    /**
     * Prepare Item data for view, e.G. DateTime to string or custom column data.
     *
     * @param array $data
     * @param string $crudAction
     *
     * @return array
     */
    protected function prepareItemData(array $data, string $crudAction) : array {
        return $data;
    }

    /**
     * Handle update action on crud index.
     *
     * @param array $params
     *
     * @throws ServiceNotFoundException
     */
    protected function handleIndexUpdate(array $params) {
        $list = $params['data'];
        foreach ($list as $index => $data) {
            $list[$index] = $this->convertData($data);
        }
        $params['data'] = $list;
        try {
            $this->crudService->update($this->model, $params);
            Oforge()->View()->Flash()->addMessage('success', I18N::translate('backend_crud_msg_bulk_update_success', 'Entities successfully bulk updated.'));
        } catch (Exception $exception) {
            Oforge()->View()->Flash()->addMessage('danger', I18N::translate('backend_crud_msg_bulk_update_failed', 'Entities bulk update failed.'));
            Oforge()->View()->Flash()->addExceptionMessage('danger', $exception);
            Oforge()->View()->Flash()->setData($this->moduleModelName, $params['data']);
        }
    }

    /**
     * Filter properties based on crud action for view.
     *
     * @param string $crudAction
     *
     * @return array
     */
    protected function filterPropertiesFor(string $crudAction) : array {
        $hasEditors = false;
        $properties = [];
        if (isset($this->modelProperties)) {
            foreach ($this->modelProperties as $property) {
                if (isset($property['crud'][$crudAction])) {
                    if ($property['crud'][$crudAction] === 'off') {
                        continue;
                    } elseif ($property['crud'][$crudAction] === 'editable') {
                        $hasEditors = true;
                    }
                    if (isset($property['list']) && is_string($property['list']) && method_exists($this, $property['list'])) {
                        $property['list'] = $this->{$property['list']}();
                    }
                    $properties[] = $property;
                }
            }
        }

        return [$properties, $hasEditors];
    }

    /**
     * Redirect/reload after post request.
     *
     * @param Response $response
     * @param string $crudAction
     * @param array $urlParams
     * @param array $queryParams
     *
     * @return Response
     */
    protected function redirect(Response $response, string $crudAction, $urlParams = [], $queryParams = []) {
        if (!isset($this->router)) {
            $this->router = Oforge()->App()->getContainer()->get('router');;
        }
        $endpointName = static::$baseEndpointName;
        if ($crudAction !== 'index') {
            $endpointName .= '_' . $crudAction;
        }
        $uri = $this->router->pathFor($endpointName, $urlParams, $queryParams);

        return $response->withRedirect($uri, 303);
    }

}


