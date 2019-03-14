<?php

namespace Oforge\Engine\plugins\Core\Manager\Plugins;

use Doctrine\ORM\EntityRepository;
use MJS\TopSort\Implementations\StringSort;
use Oforge\Engine\Modules\Core\Abstracts\AbstractBootstrap;
use Oforge\Engine\Modules\Core\Models\Plugin\Plugin;
use Oforge\Engine\Modules\Core\Services\EndpointService;

/**
 * Class PluginManager
 *
 * @package Oforge\Engine\Modules\Core\Manager\Plugins
 */
class PluginManager {
    /**
     * @var PluginManager $instance
     */
    protected static $instance = null;
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;
    /**
     * @var EntityRepository $pluginRepository
     */
    private $pluginRepository;
    /**
     * @var Plugin[] $activePlugins
     */
    private $activePlugins;

    protected function __construct() {
        $this->entityManager    = Oforge()->DB()->getEntityManager();
        $this->pluginRepository = $this->entityManager->getRepository(Plugin::class);
    }

    /**
     * @return PluginManager
     */
    public static function getInstance() : PluginManager {
        if (is_null(self::$instance)) {
            self::$instance = new PluginManager();
        }

        return self::$instance;
    }

    /**
     * @return Plugin[]
     */
    public function getActivePlguins() {
        return $this->activePlugins;
    }

    /**
     * Initialize all plugins.
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \MJS\TopSort\CircularDependencyException
     * @throws \MJS\TopSort\ElementNotFoundException
     * @throws \Oforge\Engine\Modules\Core\Exceptions\ConfigOptionKeyNotExistException
     * @throws \Oforge\Engine\Modules\Core\Exceptions\ServiceAlreadyDefinedException
     * @throws \Oforge\Engine\Modules\Core\Exceptions\ServiceNotFoundException
     */
    public function init() {
        $serviceManager       = Oforge()->Services();
        $bootstrapManager     = Oforge()->getBootstrapManager();
        $pluginsBootstrapData = $bootstrapManager->getPluginBootstrapData();

        /** @var Plugin[] $pluginList */
        $pluginList = $this->pluginRepository->findBy([], ['order' => 'ASC']);
        /** @var array $pluginsMap BootstrapClass=>Plugin map. */
        $pluginsMap = [];
        /** @var array $tmpDeactivated Temporary map (BootstrapClass=>true) for recursive deactivation process. */
        $tmpDeactivated = [];
        /** @var array $mapDeactivated Map (BootstrapClass=>true) of deactivated plugins. */
        $mapDeactivated = [];
        $mapAdded       = [];

        foreach ($pluginList as $index => $plugin) {
            if (!isset($pluginsBootstrapData[$plugin->getBootstrapClass()])) {
                // plugin does not exist anymore
                $tmpDeactivated[$plugin->getBootstrapClass()] = true;
                $mapDeactivated[$plugin->getBootstrapClass()] = true;
                $this->entityManager->remove($plugin);
                $this->entityManager->flush($plugin);
            } else {
                // plugin exist
                $pluginsMap[$plugin->getBootstrapClass()] = $plugin;

                $bootstrapInstance = $bootstrapManager->getBootstrapInstance($plugin->getBootstrapClass());
                $plugin->setBootstrapInstance($bootstrapInstance);
            }
        }
        // auto deactivate plugins with dependencies of removed plugins
        while (!empty($tmpDeactivated)) {
            reset($tmpDeactivated);
            $searchedDependent = key($tmpDeactivated);
            // $searchedDependent = array_key_first($tmpDeactivated); // TODO PHP 7.3
            unset($tmpDeactivated[$searchedDependent]);
            foreach ($pluginsMap as $bootstrapClass => $plugin) {
                /** @var string $bootstrapClass */
                /** @var Plugin $plugin */
                $bootstrapInstance = $plugin->getBootstrapInstance();
                if (is_null($bootstrapInstance)) {
                    continue;
                }
                $pluginDependencies = $bootstrapInstance->getDependencies();
                if (in_array($searchedDependent, $pluginDependencies)) {
                    // (one) plugin dependency is deactivated, deactivate this plugin
                    $bootstrapInstance->deactivate();
                    $tmpDeactivated[$bootstrapClass] = true;
                    $mapDeactivated[$bootstrapClass] = true;
                }
            }
        }
        // create new missing plugins
        foreach ($pluginsBootstrapData as $bootstrapClass => $bootstrapData) {
            if (!is_subclass_of($bootstrapClass, AbstractBootstrap::class)) {
                Oforge()->Logger()->get()->warning("Class '$bootstrapClass' is not subclass of " . AbstractBootstrap::class);
                continue;
            }
            /** @var AbstractBootstrap $bootstrapInstance */
            $bootstrapInstance = $bootstrapManager->getBootstrapInstance($bootstrapClass);
            if (is_null($bootstrapInstance)) {
                continue;
            }
            if (!isset($pluginsMap[$bootstrapClass])) {
                Oforge()->DB()->initModelSchemata($bootstrapInstance->getModels());
                $bootstrapInstance->install();

                $name   = str_replace('\\', ' - ', strtr($bootstrapClass, [
                    '\\Bootstrap' => '',
                    // 'Oforge\\Engine\\plugins\\' => '',
                ]));
                $plugin = Plugin::create([
                    'name'           => $name,
                    'bootstrapClass' => $bootstrapClass,
                    'order'          => $bootstrapInstance->getOrder(),
                    'installed'      => true,
                ]);
                $this->entityManager->persist($plugin);
                $this->entityManager->flush($plugin);

                $pluginsMap[$bootstrapClass] = $plugin;
                $mapAdded[$bootstrapClass]   = true;

                // add to map of deactivated plugins if dependencies are deactivated
                if (!empty($bootstrapInstance->getDependencies())) {
                    foreach ($bootstrapInstance->getDependencies() as $dependency) {
                        if (isset($mapDeactivated[$dependency])) {
                            $mapDeactivated[$bootstrapClass] = true;
                            break;
                        }
                    }
                }
            }
        }
        // topsort $pluginsMap by dependencies
        $sorter = new StringSort();
        foreach ($pluginsMap as $bootstrapClass => $plugin) {
            $bootstrapInstance = $plugin->getBootstrapInstance();
            $sorter->add($bootstrapClass, $bootstrapInstance->getDependencies());
        }
        $sortedList = $sorter->sort();
        foreach ($sortedList as $bootstrapClass) {
            /** @var Plugin $plugin */
            $plugin = $pluginsMap[$bootstrapClass];
            /** @var AbstractBootstrap $bootstrapInstance */
            $bootstrapInstance = $plugin->getBootstrapInstance();
            if (isset($mapDeactivated[$bootstrapClass])) {
                continue;
            }
            if ($plugin->isActive()) {
                $serviceManager->register($bootstrapInstance->getServices());
                /** @var EndpointService $endpointService */
                $endpointService = $serviceManager->get('endpoint');
                $endpointService->register($bootstrapInstance->getEndpoints());
                // /** @var MiddlewareService $middlewareService */
                $middlewareService = $serviceManager->get('middleware');

                $middlewares = $middlewareService->register($bootstrapInstance->getMiddleware(), $plugin);
                $plugin->setMiddlewares($middlewares);
                $bootstrapInstance->load();
                $this->activePlugins[] = $plugin;
            } else {
                unset($pluginsMap[$bootstrapClass]);
            }
            if (!$this->entityManager->contains($plugin)) {
                $this->entityManager->merge($plugin);
            }
        }
        $this->entityManager->flush();
        $this->pluginRepository->clear();
    }

}
