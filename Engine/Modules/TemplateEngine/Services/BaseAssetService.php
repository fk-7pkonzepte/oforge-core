<?php
/**
 * Created by PhpStorm.
 * User: Matthaeus.Schmedding
 * Date: 07.11.2018
 * Time: 10:39
 */

namespace Oforge\Engine\Modules\TemplateEngine\Services;

use Oforge\Engine\Modules\Core\Helper\StringHelper;
use Oforge\Engine\Modules\Core\Models\Plugin\Plugin;
use Oforge\Engine\Modules\Core\Services\KeyValueStoreService;
use Oforge\Engine\Modules\Core\Services\PluginAccessService;
use Oforge\Engine\Modules\Core\Statics;

class BaseAssetService
{
    protected $key = "";
    /**
     * @var $store KeyValueStoreService
     */
    protected $store;

    /**
     * BaseAssetService constructor.
     * @throws \Oforge\Engine\Modules\Core\Exceptions\ServiceNotFoundException
     */
    public function __construct()
    {
        $this->store = Oforge()->Services()->get("store.keyvalue");
    }

    /**
     * Create assets like JavaScript or CSS Files
     *
     * @param string $scope
     *
     * @return string
     */
    public function build(string $scope = TemplateAssetService::DEFAULT_SCOPE): string
    {
        // check if the /var/public folder exists. if not, create it.
        if (!file_exists(ROOT_PATH . Statics::ASSET_CACHE_DIR)) {
            mkdir(ROOT_PATH . Statics::ASSET_CACHE_DIR, 0750, true);
        }
        return "";
    }

    /**
     * @param string $scope
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function clear(string $scope = TemplateAssetService::DEFAULT_SCOPE)
    {
        $this->store->set($this->getAccessKey($scope), "");
    }

    /**
     * @param string $scope
     *
     * @return string
     */
    public function getUrl(string $scope = TemplateAssetService::DEFAULT_SCOPE): string
    {
        $value = $this->store->get($this->getAccessKey($scope));
        if (isset($value)) return $value;

        return $this->build($scope);
    }

    /**
     * @param string $scope
     *
     * @return string
     */
    protected function getAccessKey(string $scope = TemplateAssetService::DEFAULT_SCOPE): string
    {
        return "compiled." . $this->key . ".url." . $scope;
    }

    /**
     * @return array
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Oforge\Engine\Modules\Core\Exceptions\ServiceNotFoundException
     */
    protected function getAssetsDirectories()
    {
        /**
         * @var $templateRenderer TemplateRenderService
         */
        $templateRenderer = Oforge()->Services()->get("template.render");
        $activeTemplate = $templateRenderer->getActiveTemplate();

        $paths = [ROOT_PATH . Statics::THEME_DIR . DIRECTORY_SEPARATOR . "Base"];

        /**
         * @var $pluginAccessService PluginAccessService
         */
        $pluginAccessService = Oforge()->Services()->get("plugin.access");

        /**
         * @var $plugins Plugin[]
         */
        $plugins = $pluginAccessService->getActive();

        foreach ($plugins as $plugin) {
            $viewsDir = ROOT_PATH . Statics::PLUGINS_DIR . DIRECTORY_SEPARATOR . $plugin->getName() . DIRECTORY_SEPARATOR . Statics::VIEW_DIR_NAME;

            if (file_exists($viewsDir)) {
                array_push($paths, $viewsDir);
            }
        }

        $templatePath = ROOT_PATH .  Statics::THEME_DIR . DIRECTORY_SEPARATOR . $activeTemplate;

        if (!in_array( $templatePath, $paths)) array_push($paths, $templatePath);

        return $paths;
    }

    /**
     * remove generated asset files that aren't used anymore.
     * - Scan the cache asset directory
     * - find all files based on file extension except the currently used file
     * - delete
     *
     * @param string $newFileName the currently used file
     */
    protected function removeOldAssets(string $folder, string $newFileName, string $extension)
    {
        $files = scandir($folder);
        foreach ($files as $file) {
            if (StringHelper::endsWith($file, $extension) && strpos($file, $newFileName) === false) {
                unlink($folder . DIRECTORY_SEPARATOR . $file);
            }
        }
    }

    /**
     * @param string $scope
     * @return bool
     */
    public function isBuild(string $scope = TemplateAssetService::DEFAULT_SCOPE) {
        $value = $this->store->get($this->getAccessKey($scope));
        return isset($value);
    }
}
