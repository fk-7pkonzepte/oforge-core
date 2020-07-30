<?php
/**
 * Created by PhpStorm.
 * User: Matthaeus.Schmedding
 * Date: 07.11.2018
 * Time: 10:39
 */

namespace Oforge\Engine\Modules\TemplateEngine\Core\Services;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Oforge\Engine\Modules\Core\Exceptions\ServiceNotFoundException;
use Oforge\Engine\Modules\Core\Exceptions\Template\TemplateNotFoundException;
use Oforge\Engine\Modules\Core\Helper\FileSystemHelper;
use Oforge\Engine\Modules\Core\Helper\Statics;
use Oforge\Engine\Modules\Core\Helper\StringHelper;
use Oforge\Engine\Modules\Core\Models\Plugin\Plugin;
use Oforge\Engine\Modules\Core\Services\KeyValueStoreService;
use Oforge\Engine\Modules\Core\Services\PluginAccessService;

/**
 * Class BaseAssetService
 *
 * @package Oforge\Engine\Modules\TemplateEngine\Core\Services
 */
class BaseAssetService {
    /** @var string $key */
    protected $key;
    /** @var KeyValueStoreService $storage */
    protected $storage;

    /**
     * BaseAssetService constructor.
     *
     * @throws ServiceNotFoundException
     */
    public function __construct() {
        $this->storage = Oforge()->Services()->get('store.keyvalue');
    }

    /**
     * Create assets like JavaScript or CSS Files
     *
     * @param string $scope
     * @param string $context
     *
     * @return string
     */
    public function build(string $context, string $scope = TemplateAssetService::DEFAULT_SCOPE) : string {
        // check if the /var/public folder exists. if not, create it.
        $folder = ROOT_PATH . Statics::ASSET_CACHE_DIR . DIRECTORY_SEPARATOR . $scope . DIRECTORY_SEPARATOR . $this->key;
        if (!file_exists($folder)) {
            FileSystemHelper::mkdir($folder, true, 0750);
        }

        return '';
    }

    /**
     * @param string $scope
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function clear(string $scope = TemplateAssetService::DEFAULT_SCOPE) {
        $this->storage->set($this->getAccessKey($scope), '');
        //TODO remove file?
    }

    /**
     * @param string $scope
     *
     * @return string
     */
    public function getUrl(string $scope = TemplateAssetService::DEFAULT_SCOPE) : string {
        $value = $this->storage->get($this->getAccessKey($scope));
        if (empty($value)) {
            return $this->build($scope);
        }

        return $value;
    }

    /**
     * @param string $scope
     *
     * @return bool
     */
    public function isBuild(string $scope = TemplateAssetService::DEFAULT_SCOPE) : bool {
        $value = $this->storage->get($this->getAccessKey($scope));

        return !empty($value);
    }

    /**
     * @param string $scope
     *
     * @return string
     */
    protected function getAccessKey(string $scope = TemplateAssetService::DEFAULT_SCOPE) : string {
        return $scope . '.asset.' . $this->key . '.url';
    }

    /**
     * @return array
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws ServiceNotFoundException
     * @throws TemplateNotFoundException
     */
    protected function getAssetsDirectories() {
        /** @var TemplateManagementService $templateManagementService */
        $templateManagementService = Oforge()->Services()->get('template.management');
        $activeTemplate            = $templateManagementService->getActiveTemplate();

        $paths = [ROOT_PATH . DIRECTORY_SEPARATOR . Statics::TEMPLATE_DIR . DIRECTORY_SEPARATOR . Statics::DEFAULT_THEME];

        /** @var $pluginAccessService PluginAccessService */
        $pluginAccessService = Oforge()->Services()->get('plugin.access');

        /** @var $plugins Plugin[] */
        $plugins = $pluginAccessService->getActive();

        foreach ($plugins as $plugin) {
            $viewsDir = ROOT_PATH . DIRECTORY_SEPARATOR . Statics::PLUGIN_DIR . DIRECTORY_SEPARATOR . $plugin['name'] . DIRECTORY_SEPARATOR . Statics::VIEW_DIR;

            if (file_exists($viewsDir)) {
                $paths[] = $viewsDir;
            }
        }

        $templatePath = ROOT_PATH . DIRECTORY_SEPARATOR . Statics::TEMPLATE_DIR . DIRECTORY_SEPARATOR . $activeTemplate->getName();

        if (!in_array($templatePath, $paths)) {
            $paths[] = $templatePath;
        }

        return $paths;
    }

    /**
     * remove generated asset files that aren't used anymore.
     * - Scan the cache asset directory
     * - find all files based on file extension except the currently used file
     * - delete
     *
     * @param string $folder Search asset folder
     * @param string $newFileName the currently used file
     * @param string $extension File extension
     */
    protected function removeOldAssets(string $folder, string $newFileName, string $extension) {
        $files = scandir($folder);
        foreach ($files as $file) {
            if (StringHelper::endsWith($file, $extension) && strpos($file, $newFileName) === false) {
                unlink($folder . DIRECTORY_SEPARATOR . $file);
            }
        }
    }

}
