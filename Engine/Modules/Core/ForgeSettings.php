<?php

namespace Oforge\Engine\Modules\Core;

/**
 * Class ForgeSettings
 * Loads all Settings that need to come from the filesystem, e.g. database configuration.
 *
 * @package Oforge\Engine\Modules\Core
 */
class ForgeSettings {
    private const CONFIG_FILE      = ROOT_PATH . '/config.php';
    private const CONFIG_FILE_TEST = ROOT_PATH . '/test.config.php';
    /** @var ForgeSettings $instance */
    protected static $instance = null;
    /**
     * @var array $settings
     */
    private $settings = [];

    /**
     * ForgeSettings constructor.
     *
     * @param null $path
     */
    protected function __construct($path = null) {
        $this->path = $path ?? self::CONFIG_FILE;
    }

    /**
     * @param bool $test
     *
     * @return ForgeSettings
     */
    public static function getInstance($test = false) {
        if (null === self::$instance) {
            self::$instance = new ForgeSettings($test ? self::CONFIG_FILE_TEST : self::CONFIG_FILE);
        }

        return self::$instance;
    }

    /**
     * Load the settings
     */
    public function load() {
        $config = require_once $this->path;
        $this->settings = $config;
    }
    
    /**
     * get a specific setting value based on a defined key
     *
     * @param string $key
     *
     * @return array|mixed
     */
    public function get(string $key)  {
        return array_key_exists($key, $this->settings) ? $this->settings[$key] : [];
    }

}
