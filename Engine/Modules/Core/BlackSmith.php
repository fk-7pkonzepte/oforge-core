<?php

use Oforge\Engine\Modules\Core\Abstracts\AbstractTemplateManager;
use Oforge\Engine\Modules\Core\Abstracts\AbstractViewManager;
use Oforge\Engine\Modules\Core\Forge\ForgeSlimApp;
use Oforge\Engine\Modules\Core\Forge\Database\ForgeDatabase;
use Oforge\Engine\Modules\Core\Forge\ForgeSettings;
use Oforge\Engine\Modules\Core\Manager\Logger\LoggerManager;
use Oforge\Engine\Modules\Core\Manager\Modules\ModuleManager;
use Oforge\Engine\Modules\Core\Manager\Plugins\PluginManager;
use Oforge\Engine\Modules\Core\Manager\Routes\RouteManager;
use Oforge\Engine\Modules\Core\Manager\Services\ServiceManager;

// TODO: find a better way to use a TemplateEngine Module

/**
 * Class BlackSmith
 */
class BlackSmith {
    public const INIT_RUNTIME_EXCEPTION_MESSAGE = 'Oforge fire does not burn. Ask the blacksmith to start forging.';
    /**
     * The main instance to start the whole application.
     *
     * @var BlackSmith
     */
    protected static $instance = null;
    /**
     * App
     *
     * @var \Oforge\Engine\Modules\Core\Forge\ForgeSlimApp $app
     */
    private $app = null;
    /**
     * Container
     *
     * @var \Slim\Container $container
     */
    private $container = null;
    /**
     *  DataBase
     *
     * @var ForgeDatabase $db
     */
    private $db = null;
    /**
     * LogManager
     *
     * @var LoggerManager $logger
     */
    private $logger = null;
    /**
     * PluginManager
     *
     * @var PluginManager $pluginManager
     */
    private $pluginManager = null;
    /**
     *  RouteManager
     *
     * @var RouteManager $router
     */
    private $router = null;
    /**
     * Services
     *
     * @var ServiceManager $services
     */
    private $services = null;
    /**
     * ForgeSettings
     *
     * @var ForgeSettings $settings
     */
    private $settings = null;
    /**
     * TemplateManager
     *
     * @var AbstractTemplateManager $templateManager
     */
    private $templateManager = null;
    /**
     * ViewManager
     *
     * @var AbstractViewManager $viewManager
     */
    private $viewManager = null;

    /**
     * BlackSmith constructor.
     */
    protected function __construct() {
        Oforge($this);
    }

    /**
     * Create a singleton instance of the inner core
     *
     * @return BlackSmith
     */
    public static function getInstance() : BlackSmith {
        if (!isset(self::$instance)) {
            self::$instance = new BlackSmith();
        }

        return self::$instance;
    }

    /**
     * @return \Oforge\Engine\Modules\Core\Forge\ForgeSlimApp
     */
    public function App() : ForgeSlimApp {
        if (!isset($this->app)) {
            throw new \RuntimeException(self::INIT_RUNTIME_EXCEPTION_MESSAGE);
        }

        return $this->app;
    }

    /**
     * @return \Slim\Container
     */
    public function Container() : \Slim\Container {
        if (!isset($this->container)) {
            throw new \RuntimeException(self::INIT_RUNTIME_EXCEPTION_MESSAGE);
        }

        return $this->container;
    }

    /**
     * @return ForgeDatabase
     */
    public function DB() : ForgeDatabase {
        if (!isset($this->db)) {
            throw new \RuntimeException(self::INIT_RUNTIME_EXCEPTION_MESSAGE);
        }

        return $this->db;
    }

    /**
     * @return LoggerManager
     */
    public function Logger() : LoggerManager {
        if (!isset($this->logger)) {
            throw new \RuntimeException(self::INIT_RUNTIME_EXCEPTION_MESSAGE);
        }

        return $this->logger;
    }

    /**
     * @return PluginManager
     */
    public function Plugins() : PluginManager {
        if (!isset($this->pluginManager)) {
            throw new RuntimeException(self::INIT_RUNTIME_EXCEPTION_MESSAGE);
        }

        return $this->pluginManager;
    }

    /**
     * @return RouteManager
     */
    public function Router() : RouteManager {
        if (!isset($this->router)) {
            throw new \RuntimeException(self::INIT_RUNTIME_EXCEPTION_MESSAGE);
        }

        return $this->router;
    }

    /**
     * @return ServiceManager
     */
    public function Services() : ServiceManager {
        if (!isset($this->services)) {
            throw new \RuntimeException(self::INIT_RUNTIME_EXCEPTION_MESSAGE);
        }

        return $this->services;
    }

    /**
     * @return ForgeSettings
     */
    public function Settings() : ForgeSettings {
        if (!isset($this->settings)) {
            throw new \RuntimeException(self::INIT_RUNTIME_EXCEPTION_MESSAGE);
        }

        return $this->settings;
    }

    /**
     * @return AbstractTemplateManager
     */
    public function Templates() : AbstractTemplateManager {
        if (!isset($this->templateManager)) {
            throw new RuntimeException(self::INIT_RUNTIME_EXCEPTION_MESSAGE);
        }

        return $this->templateManager;
    }

    /**
     * @return AbstractViewManager
     */
    public function View() : AbstractViewManager {
        if (!isset($this->viewManager)) {
            throw new RuntimeException(self::INIT_RUNTIME_EXCEPTION_MESSAGE);
        }

        return $this->viewManager;
    }

    /**
     * @param bool $start defines if slim should be started or not
     * @param bool $test defines if test environment should be used
     *
     * @throws \Oforge\Engine\Modules\Core\Exceptions\ServiceNotFoundException
     * @throws \Slim\Exception\MethodNotAllowedException
     * @throws \Slim\Exception\NotFoundException
     * @throws Exception
     */
    public function forge($start = true, $test = false) {
        // load settings from disk like mysql credentials, log paths, s.o.
        $this->settings = ForgeSettings::getInstance($test);
        $this->settings->load();

        // Init logger
        $this->logger = new LoggerManager($this->settings->get('logger'));

        // Connect to database
        $this->db = ForgeDatabase::getInstance();
        $this->db->init($this->settings->get('db'));

        // Start service manager
        $this->services = ServiceManager::getInstance();

        // Start slim application
        $this->app       = ForgeSlimApp::getInstance();
        $this->container = $this->App()->getContainer();

        // Init and load modules
        $modules = ModuleManager::getInstance();
        $modules->init();

        // Init and load plugins
        $this->pluginManager = PluginManager::getInstance();
        $this->pluginManager->init();

        // Init route manager
        $this->router = RouteManager::getInstance();

        // Let the Blacksmith forge all the things \°/
        if ($start) {
            $this->router->init();
            $this->app->run();
        }
    }

    /**
     * @param AbstractViewManager $viewManager
     */
    public function setViewManager(AbstractViewManager $viewManager) {
        $this->viewManager = $viewManager;
    }

    /**
     * @param AbstractTemplateManager $templateManager
     */
    public function setTemplateManager(AbstractTemplateManager $templateManager) {
        $this->templateManager = $templateManager;
    }

}

/**
 * Returns application instance.
 * This Function call is globally available.
 * So you can call Oforge()->{App(), Service(), DB(), ...}->whatever
 *
 * @param BlackSmith $newInstance
 *
 * @return BlackSmith
 */
function Oforge(BlackSmith &$newInstance = null) : BlackSmith {
    static $instance;

    if (isset($newInstance)) {
        // $oldInstance = $instance;
        $instance = $newInstance;

        return $newInstance;
    } elseif (!isset($instance)) {
        throw new RuntimeException(BlackSmith::INIT_RUNTIME_EXCEPTION_MESSAGE);
    }

    return $instance;
}
