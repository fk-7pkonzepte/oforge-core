<?php

namespace Oforge\Engine\Modules\Core\Manager\Bootstrap;

use Doctrine\ORM\EntityRepository;
use Oforge\Engine\Modules\Core\Abstracts\AbstractBootstrap;
use Oforge\Engine\Modules\Core\Bootstrap as CoreBootstrap;
use Oforge\Engine\Modules\Core\Helper\FileSystemHelper;
use Oforge\Engine\Modules\Core\Helper\PhpArrayFileWriter;
use Oforge\Engine\Modules\Core\Helper\StringHelper;
use Oforge\Engine\Modules\Core\Statics;

/**
 * Class BootstrapManager
 *
 * @package Oforge\Engine\Modules\Core\Manager\BootstrapManager
 */
class BootstrapManager {
    private const FILE_PATH = ROOT_PATH . Statics::VAR_DIR . DIRECTORY_SEPARATOR . 'bootstrap.php';
    public const  KEY_PATH  = 'path';
    private const MODULE    = 'module';
    private const PLUGIN    = 'plugin';
    /**
     * @var BootstrapManager $instance
     */
    protected static $instance = null;
    /**
     * @var array $bootstrapData
     */
    private $bootstrapData = [];
    /**
     * @var array $bootstrapInstances
     */
    private $bootstrapInstances = [];

    protected function __construct() {
    }

    /**
     * @return BootstrapManager
     */
    public static function getInstance() : BootstrapManager {
        if (is_null(self::$instance)) {
            self::$instance = new BootstrapManager();
        }

        return self::$instance;
    }

    /**
     * @param string $class
     *
     * @return AbstractBootstrap|null
     */
    public function getBootstrapInstance(string $class) : ?AbstractBootstrap {
        if (isset($this->bootstrapInstances[$class])) {
            return $this->bootstrapInstances[$class];
        }

        return null;
    }

    /**
     * Returns map of bootstrap instances with bootstrap class key.
     *
     * @return array
     */
    public function getBootstrapInstances() : array {
        return $this->bootstrapInstances;
    }

    /**
     * Returns module bootstrap data.
     *
     * @return mixed
     */
    public function getModuleBootstrapData() {
        return $this->bootstrapData[self::MODULE];
    }

    /**
     * Returns plugin bootstrap data.
     *
     * @return mixed
     */
    public function getPluginBootstrapData() {
        return $this->bootstrapData[self::PLUGIN];
    }

    /**
     * Initialize all modules and plugins bootstrap data.
     */
    public function init() {
        $isDevelopmentMode = Oforge()->Settings()->isDevelopmentMode();
        if (file_exists(self::FILE_PATH)) {
            if ($isDevelopmentMode) {
                $this->collectBootstrapData();
            } else {
                $this->bootstrapData = require_once(self::FILE_PATH);

                $update = false;
                foreach ($this->bootstrapData as $type => $array) {
                    foreach ($array as $bootstrapClass => $bootstrapData) {
                        if (!file_exists($bootstrapData[self::KEY_PATH])) {
                            $update = true;
                        }
                    }
                }
                if ($update) {
                    $this->updateBootstrapData();
                }
            }
        } else {
            $this->updateBootstrapData();
        }
        foreach ($this->bootstrapData as $type => $data) {
            foreach ($data as $bootstrapClass => $bootstrapData) {
                /** @var AbstractBootstrap $instance */
                $instance = new $bootstrapClass();
                if ($bootstrapClass === CoreBootstrap::class) {
                    Oforge()->DB()->initModelSchemata($instance->getModels());
                }
                $this->bootstrapInstances[$bootstrapClass] = $instance;
            }
        }
    }

    /**
     * Create parent folder if not exist and updates Bootstrap-data file.
     */
    public function updateBootstrapData() {
        $this->collectBootstrapData();

        if (!file_exists($dir = dirname(self::FILE_PATH))) {
            @mkdir($dir, 0777, true);
        }

        if (!PhpArrayFileWriter::write(self::FILE_PATH, $this->bootstrapData)) {
            Oforge()->Logger()->get()->emergency('Couldn\'t write bootstrap collection file: ' . self::FILE_PATH);
        }
    }

    /**
     * Collect and set all Bootstrap-data of modules and plugins.
     */
    protected function collectBootstrapData() {
        $bootstrapData = [
            self::MODULE => $this->collectBootstrapDataSub(Statics::ENGINE_DIR, true),
            self::PLUGIN => $this->collectBootstrapDataSub(Statics::PLUGINS_DIR, false),
        ];

        $this->bootstrapData = $bootstrapData;
    }

    /**
     * Collect and return all Bootstrap-files modules or plugins.
     *
     * @param string $relativePath Root relative start search path.
     * @param bool $isModule Search for Modules.
     *
     * @return array
     */
    protected function collectBootstrapDataSub(string $relativePath, bool $isModule) {
        $data  = [];
        $files = FileSystemHelper::getBootstrapFiles(ROOT_PATH . $relativePath);
        foreach ($files as $file) {
            $directory = dirname($file);

            $class = str_replace('/', '\\', str_replace(ROOT_PATH, '', $directory)) . '\Bootstrap';
            if ($isModule) {
                $class = "Oforge$class";
            } else {
                $pluginPrefix = '\\Plugins';
                if (StringHelper::startsWith($class, $pluginPrefix)) {
                    $class = substr($class, 8); // 8=strlen($pluginPrefix)
                }
                if (StringHelper::startsWith($class, '\\')) {
                    $class = substr($class, 1);
                }
            }
            $entryData = [
                self::KEY_PATH => $directory,
            ];

            $data[$class] = $entryData;
        }
        if ($isModule) {
            // set CoreBootstrap as first entry
            $tmp = $data[CoreBootstrap::class];
            unset($data[CoreBootstrap::class]);
            $data = [CoreBootstrap::class => $tmp] + $data;
        }

        return $data;
    }

}
