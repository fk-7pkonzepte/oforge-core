<?php

namespace Oforge\Engine\Modules\Core\Manager\Modules;

use Doctrine\ORM\EntityRepository;
use MJS\TopSort\Implementations\StringSort;
use Oforge\Engine\Modules\Core\Abstracts\AbstractBootstrap;
use Oforge\Engine\Modules\Core\Bootstrap as CoreBootstrap;
use Oforge\Engine\Modules\Core\Models\Module\Module;
use Oforge\Engine\Modules\Core\Services\EndpointService;
use Oforge\Engine\Modules\Core\Services\MiddlewareService;

/**
 * Class ModuleManager
 *
 * @package Oforge\Engine\Modules\Core\Manager\Modules
 */
class ModuleManager {
    /**
     * @var ModuleManager $instance
     */
    protected static $instance = null;
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    private $entityManager;
    /**
     * @var EntityRepository $moduleRepository
     */
    private $moduleRepository;
    /**
     * @var Module[] $activeModules
     */
    private $activeModules = [];

    protected function __construct() {
        $this->entityManager    = Oforge()->DB()->getEntityManager();
        $this->moduleRepository = $this->entityManager->getRepository(Module::class);
    }

    /**
     * @return ModuleManager
     */
    public static function getInstance() : ModuleManager {
        if (is_null(self::$instance)) {
            self::$instance = new ModuleManager();
        }

        return self::$instance;
    }

    /**
     * @return Module[]
     */
    public function getActiveModules() {
        return $this->activeModules;
    }

    /**
     * Initialize all modules
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
        $modulesBootstrapData = $bootstrapManager->getModuleBootstrapData();

        /** @var Module[] $moduleList */
        $moduleList = $this->moduleRepository->findBy([], ['order' => 'ASC']);
        /** @var array $modulesMap BootstrapClass=>Module map. */
        $modulesMap = [];
        /** @var array $tmpDeactivated Temporary map (BootstrapClass=>true) for recursive deactivation process. */
        $tmpDeactivated = [];
        /** @var array $mapDeactivated Map (BootstrapClass=>true) of deactivated modules. */
        $mapDeactivated = [];
        $mapAdded       = [];

        foreach ($moduleList as $index => $module) {
            if (!isset($modulesBootstrapData[$module->getBootstrapClass()])) {
                // module does not exist anymore
                $tmpDeactivated[$module->getBootstrapClass()] = true;
                $mapDeactivated[$module->getBootstrapClass()] = true;
                $this->entityManager->remove($module);
                $this->entityManager->flush($module);
            } else {
                // module exist
                $modulesMap[$module->getBootstrapClass()] = $module;

                $bootstrapInstance = $bootstrapManager->getBootstrapInstance($module->getBootstrapClass());
                $module->setBootstrapInstance($bootstrapInstance);
            }
        }
        // auto deactivate modules with dependencies of removed modules
        while (!empty($tmpDeactivated)) {
            reset($tmpDeactivated);
            $searchedDependent = key($tmpDeactivated);
            // $searchedDependent = array_key_first($tmpDeactivated); // TODO PHP 7.3
            unset($tmpDeactivated[$searchedDependent]);
            foreach ($modulesMap as $bootstrapClass => $module) {
                /** @var string $bootstrapClass */
                /** @var Module $module */
                $bootstrapInstance = $module->getBootstrapInstance();
                if (is_null($bootstrapInstance)) {
                    continue;
                }
                $moduleDependencies = $bootstrapInstance->getDependencies();
                if (in_array($searchedDependent, $moduleDependencies)) {
                    // (one) module dependency is deactivated, deactivate this module
                    $bootstrapInstance->deactivate();
                    $tmpDeactivated[$bootstrapClass] = true;
                    $mapDeactivated[$bootstrapClass] = true;
                }
            }
        }
        // create new missing modules
        foreach ($modulesBootstrapData as $bootstrapClass => $bootstrapData) {
            if (!is_subclass_of($bootstrapClass, AbstractBootstrap::class)) {
                Oforge()->Logger()->get()->warning("Class '$bootstrapClass' is not subclass of " . AbstractBootstrap::class);
                continue;
            }
            /** @var AbstractBootstrap $bootstrapInstance */
            $bootstrapInstance = $bootstrapManager->getBootstrapInstance($bootstrapClass);
            if (is_null($bootstrapInstance)) {
                continue;
            }
            $isCoreBootstrap = ($bootstrapClass === CoreBootstrap::class);
            if ($isCoreBootstrap) {
                $serviceManager->register($bootstrapInstance->getServices());
            }
            if (!isset($modulesMap[$bootstrapClass])) {
                if ($isCoreBootstrap) {
                    $this->registerEndpointsAndMiddleWare($bootstrapInstance);
                } else {
                    Oforge()->DB()->initModelSchemata($bootstrapInstance->getModels());
                }
                $bootstrapInstance->install();
                if ($isCoreBootstrap) {
                    $bootstrapInstance->activate();
                }

                $name   = str_replace('\\', ' - ', strtr($bootstrapClass, [
                    '\\Bootstrap'               => '',
                    'Oforge\\Engine\\Modules\\' => '',
                ]));
                $module = Module::create([
                    'name'           => $name,
                    'bootstrapClass' => $bootstrapClass,
                    'order'          => $bootstrapInstance->getOrder(),
                    'installed'      => true,
                    'active'         => $isCoreBootstrap,
                ]);
                $this->entityManager->persist($module);
                $this->entityManager->flush($module);

                $modulesMap[$bootstrapClass] = $module;
                $mapAdded[$bootstrapClass]   = true;

                // add to map of deactivated modules if dependencies are deactivated
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
        // topsort $modulesMap by dependencies
        $sorter = new StringSort();
        foreach ($modulesMap as $bootstrapClass => $module) {
            $bootstrapInstance = $module->getBootstrapInstance();
            $sorter->add($bootstrapClass, $bootstrapInstance->getDependencies());
        }
        $sortedList = $sorter->sort();
        foreach ($sortedList as $bootstrapClass) {
            /** @var Module $module */
            $module = $modulesMap[$bootstrapClass];
            /** @var AbstractBootstrap $bootstrapInstance */
            $bootstrapInstance = $module->getBootstrapInstance();
            if ($bootstrapClass === CoreBootstrap::class) {
                $bootstrapInstance->load();
                continue;
            }
            if (isset($mapDeactivated[$bootstrapClass])) {
                continue;
            }
            $serviceManager->register($bootstrapInstance->getServices());
            // auto activate all inactive new modules
            if (!$module->isActive() && isset($mapAdded[$bootstrapClass])) {
                $bootstrapInstance->activate();
                $module->setActive();
            }
            if ($module->isActive()) {
                $this->registerEndpointsAndMiddleWare($bootstrapInstance);
                $bootstrapInstance->load();
                $this->activeModules[] = $module;
            } else {
                unset($modulesMap[$bootstrapClass]);
            }
            if (!$this->entityManager->contains($module)) {
                $this->entityManager->merge($module);
            }
        }
        $this->entityManager->flush();
        $this->moduleRepository->clear();
    }

    /**
     * @param AbstractBootstrap $bootstrapInstance
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Oforge\Engine\Modules\Core\Exceptions\ConfigOptionKeyNotExistException
     * @throws \Oforge\Engine\Modules\Core\Exceptions\ServiceAlreadyDefinedException
     * @throws \Oforge\Engine\Modules\Core\Exceptions\ServiceNotFoundException
     */
    protected function registerEndpointsAndMiddleWare(AbstractBootstrap $bootstrapInstance) {
        $serviceManager = Oforge()->Services();
        /** @var EndpointService $endpointService */
        $endpointService = $serviceManager->get('endpoint');
        $endpointService->register($bootstrapInstance->getEndpoints());
        /** @var MiddlewareService $middlewareService */
        $middlewareService = $serviceManager->get('middleware');
        $middlewareService->registerFromModule($bootstrapInstance->getMiddleware());
    }

}
