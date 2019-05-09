<?php

namespace Oforge\Engine\Modules\Core\Manager\Modules;

use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use MJS\TopSort\CircularDependencyException;
use MJS\TopSort\ElementNotFoundException;
use MJS\TopSort\Implementations\StringSort;
use Oforge\Engine\Modules\Core\Abstracts\AbstractBootstrap;
use Oforge\Engine\Modules\Core\Bootstrap;
use Oforge\Engine\Modules\Core\Bootstrap as CoreBootstrap;
use Oforge\Engine\Modules\Core\Exceptions\ConfigElementAlreadyExistsException;
use Oforge\Engine\Modules\Core\Exceptions\ConfigOptionKeyNotExistsException;
use Oforge\Engine\Modules\Core\Exceptions\CouldNotInstallModuleException;
use Oforge\Engine\Modules\Core\Exceptions\ServiceAlreadyDefinedException;
use Oforge\Engine\Modules\Core\Exceptions\ServiceNotFoundException;
use Oforge\Engine\Modules\Core\Helper\FileSystemHelper;
use Oforge\Engine\Modules\Core\Helper\Helper;
use Oforge\Engine\Modules\Core\Helper\Statics;
use Oforge\Engine\Modules\Core\Manager\Services\ServiceManager;
use Oforge\Engine\Modules\Core\Models\Module\Module;
use Oforge\Engine\Modules\Core\Services\EndpointService;
use Oforge\Engine\Modules\Core\Services\MiddlewareService;

/**
 * Class ModuleManager
 *
 * @package Oforge\Engine\Modules\Core\Manager\Modules
 */
class ModuleManager {
    /** @var ModuleManager $instance */
    protected static $instance = null;
    /** @var EntityManager */
    private $entityManager;
    /** @var EntityRepository $moduleRepository */
    private $moduleRepository;
    /** @var Module[] $activeModules */
    private $activeModules = [];
    /** @var ServiceManager $serviceManager */
    private $serviceManager;

    protected function __construct() {
        $this->entityManager    = Oforge()->DB()->getEntityManager();
        $this->moduleRepository = $this->entityManager->getRepository(Module::class);
        $this->serviceManager   = $serviceManager = Oforge()->Services();
    }

    /** @return ModuleManager */
    public static function getInstance() : ModuleManager {
        if (is_null(self::$instance)) {
            self::$instance = new ModuleManager();
        }

        return self::$instance;
    }

    /** @return Module[] */
    public function getActiveModules() {
        return $this->activeModules;
    }

    /**
     * Initialize all modules
     *
     * @throws AnnotationException
     * @throws CircularDependencyException
     * @throws ConfigOptionKeyNotExistsException
     * @throws ElementNotFoundException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws ServiceAlreadyDefinedException
     * @throws ServiceNotFoundException
     */
    public function init() {
        /**
         * @var string $bootstrapClass
         * @var Module $module
         * @var Module[] $moduleList
         * @var array $modulesMap BootstrapClass=>Module map.
         * @var array $tmpDeactivated Temporary map (BootstrapClass=>true) for recursive deactivation process.
         * @var array $mapDeactivated Map (BootstrapClass=>true) of deactivated modules.
         * @var array $bootstrapData Module data of BootstrapManager
         * @var EndpointService|null $endpointService
         * @var MiddlewareService|null $middlewareService
         */
        $serviceManager       = Oforge()->Services();
        $endpointService      = null;
        $middlewareService    = null;
        $bootstrapManager     = Oforge()->getBootstrapManager();
        $modulesBootstrapData = $bootstrapManager->getModuleBootstrapData();

        $moduleList     = $this->moduleRepository->findBy([], ['order' => 'ASC']);
        $modulesMap     = [];
        $tmpDeactivated = [];
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
        // create new missing modules
        foreach ($modulesBootstrapData as $bootstrapClass => $bootstrapData) {
            if (!is_subclass_of($bootstrapClass, AbstractBootstrap::class)) {
                Oforge()->Logger()->get()->warning("Class '$bootstrapClass' is not subclass of " . AbstractBootstrap::class);
                continue;
            }
            /** @var AbstractBootstrap $bootstrapInstance */
            $bootstrapInstance = $bootstrapManager->getBootstrapInstance($bootstrapClass);
            if (!isset($bootstrapInstance)) {
                continue;
            }
            $isCoreBootstrap = ($bootstrapClass === CoreBootstrap::class);
            if ($isCoreBootstrap) {
                $serviceManager->register($bootstrapInstance->getServices());
                $endpointService   = $this->serviceManager->get('endpoint');
                $middlewareService = $this->serviceManager->get('middleware');
            }
            if (!isset($modulesMap[$bootstrapClass])) {
                Oforge()->DB()->initModelSchema($bootstrapInstance->getModels());
                // if ($isCoreBootstrap) {
                $endpointService->install($bootstrapInstance->getEndpoints());
                $middlewareService->install($bootstrapInstance->getMiddlewares());
                // }
                $bootstrapInstance->install();
                // if ($isCoreBootstrap) {
                $endpointService->activate($bootstrapInstance->getEndpoints());
                $middlewareService->activate($bootstrapInstance->getMiddlewares());
                $bootstrapInstance->activate();
                // }

                $name   = str_replace('\\', ' - ', strtr($bootstrapClass, [
                    '\\Bootstrap'               => '',
                    'Oforge\\Engine\\Modules\\' => '',
                ]));
                $module = Module::create([
                    'name'           => $name,
                    'bootstrapClass' => $bootstrapClass,
                    'order'          => $bootstrapInstance->getOrder(),
                    'installed'      => true,
                    'active'         => true,
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
        // auto deactivate modules with dependencies of removed modules
        // while (!empty($tmpDeactivated)) {TODO muss überarbeitet werdeb, deactivate erst nach hinzugefügten(core) modulen
        //     reset($tmpDeactivated);
        //     $searchedDependent = key($tmpDeactivated);#= array_key_first($tmpDeactivated); // TODO erst ab PHP 7.3
        //     unset($tmpDeactivated[$searchedDependent]);
        //     foreach ($modulesMap as $bootstrapClass => $module) {
        //         $bootstrapInstance = $module->getBootstrapInstance();
        //         if (is_null($bootstrapInstance)) {
        //             continue;
        //         }
        //         $moduleDependencies = $bootstrapInstance->getDependencies();
        //         if (in_array($searchedDependent, $moduleDependencies)) {
        //             // (one) module dependency is deactivated, deactivate this module
        //             $bootstrapInstance->deactivate();
        //             $tmpDeactivated[$bootstrapClass] = true;
        //             $mapDeactivated[$bootstrapClass] = true;
        //         }
        //     }
        // }
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
                $this->installEndpointsAndMiddleWare($bootstrapInstance);
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
     * Initialize all modules
     *
     * @throws CouldNotInstallModuleException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws ServiceAlreadyDefinedException
     * @throws ServiceNotFoundException
     * @throws ConfigOptionKeyNotExistsException
     * @deprecated
     */
    public function init2() {
        $startTime = microtime(true) * 1000;

        $files = FileSystemHelper::getBootstrapFiles(ROOT_PATH . DIRECTORY_SEPARATOR . Statics::ENGINE_DIR . DIRECTORY_SEPARATOR);

        // init core module
        $this->initCoreModule(Bootstrap::class);

        // register all modules
        foreach ($files as $key => $dir) {
            // TODO: Check if suppressing error here is ok
            $fileMeta = @Helper::getFileMeta($dir);
            $this->register($fileMeta['namespace'] . "\\" . $fileMeta['class_name']);
        }
        // save all db changes
        $this->entityManager->flush();

        // find all modules order by "order"
        $modules = $this->moduleRepository()->findBy(["active" => 1], ['order' => 'ASC']);

        // create working bucket with all modules that should be started
        $bucket = [];
        // add all modules except of the core bootstrap file
        foreach ($modules as $module) {
            /**
             * @var $module Module
             */
            $classname = $module->getName();
            $instance  = new $classname();
            if (get_class($instance) != Bootstrap::class) {
                array_push($bucket, $instance);
            }
        }

        // create array with all installed module bootstrap classes
        $installed = [Bootstrap::class => true];

        // installed bootstrap
        $count = 0;
        do {
            $trash = [];
            for ($i = 0; $i < sizeof($bucket); $i++) {
                /**
                 * @var $instance AbstractBootstrap
                 */
                $instance = $bucket[$i];

                $startTime = microtime(true) * 1000;

                if (sizeof($instance->getDependencies()) > 0) {
                    $found = true;

                    foreach ($instance->getDependencies() as $dependency) {
                        if (!array_key_exists($dependency, $installed) || !$installed[$dependency]) {
                            $found = false;
                            break;
                        }
                    }

                    if ($found) {
                        $classname = get_class($instance);
                        $this->initModule(get_class($instance));
                        $installed[$classname] = true;
                    } else {
                        array_push($trash, $instance);
                    }

                } else {
                    $classname = get_class($instance);
                    $this->initModule(get_class($instance));
                    $installed[$classname] = true;
                }
            }

            $bucket = $trash;
            if ($count++ > 10) {
                break;
            }

        } while (sizeof($bucket) > 0);  // do it until everything is installed

        if (sizeof($bucket) > 0) {
            throw new CouldNotInstallModuleException(get_class($bucket[0]), $bucket[0]->getDependencies());
        }
    }

    /**
     * Register a module.
     * This means: if a module isn't found in the db table, insert it
     *
     * @param $className
     *
     * @throws ORMException
     * @deprecated
     */
    protected function register2($className) {
        if (is_subclass_of($className, AbstractBootstrap::class)) {
            /**
             * @var $instance AbstractBootstrap
             */
            $instance = new $className();

            $moduleEntry = $this->moduleRepository()->findBy(["name" => get_class($instance)]);
            if (isset($moduleEntry) && sizeof($moduleEntry) > 0) {
                //found -> nothing to do;
            } else { // if not put the data into the database
                $newEntry = Module::create(["name" => get_class($instance), "order" => $instance->getOrder(), "active" => 1, "installed" => 0]);
                $this->entityManger()->persist($newEntry);
                $this->entityManger()->flush();
            }
        }
    }

    /**
     * Initialize a module
     *
     * @param $className
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws ServiceAlreadyDefinedException
     * @throws ServiceNotFoundException
     * @throws ConfigOptionKeyNotExistsException
     * @deprecated
     */
    protected function initModule2($className) {
        if (is_subclass_of($className, AbstractBootstrap::class)) {
            /**
             * @var $instance AbstractBootstrap
             */
            $instance = new $className();

            Oforge()->DB()->initModelSchema($instance->getModels());

            $services = $instance->getServices();
            Oforge()->Services()->register($services);

            $endpoints = $instance->getEndpoints();
            Oforge()->Services()->get('endpoint')->install($endpoints);
            Oforge()->Services()->get('endpoint')->activate($endpoints);

            $middlewares = $instance->getMiddlewares();
            /** @var MiddlewareService $middlewareService */
            $middlewareService = Oforge()->Services()->get("middleware");
            $middlewareService->install($middlewares, true);

            /**
             * @var $entry Module
             */
            $entry = $this->moduleRepository()->findOneBy(["name" => $className]);

            if (isset($entry) && !$entry->getInstalled()) {
                try {
                    $instance->install();
                } catch (ConfigElementAlreadyExistsException $e) {
                }
                $this->entityManger()->persist($entry->setInstalled(true));
            }

            $instance->activate();

            $this->entityManger()->flush();
        }
    }

    /**
     * Initialize the core module
     *
     * @param $className
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws ServiceAlreadyDefinedException
     * @throws ServiceNotFoundException
     * @deprecated
     */
    private function initCoreModule2($className) {
        $startTime = microtime(true) * 1000;

        if (is_subclass_of($className, AbstractBootstrap::class)) {
            /**
             * @var $instance AbstractBootstrap
             */
            $instance = new $className();

            Oforge()->DB()->initModelSchema($instance->getModels());

            $services = $instance->getServices();
            Oforge()->Services()->register($services);

            $endpoints = $instance->getEndpoints();
            Oforge()->Services()->get('endpoint')->install($endpoints);
            Oforge()->Services()->get('endpoint')->activate($endpoints);

            /**
             * @var $entry Module
             */
            $entry = $this->moduleRepository()->findOneBy(["name" => $className]);

            $needFlush = false;
            if (isset($entry) && !$entry->getInstalled()) {
                try {
                    $instance->install();
                } catch (ConfigElementAlreadyExistsException $e) {
                }
                $this->entityManger()->persist($entry->setInstalled(true));
                $needFlush = true;
            } elseif (!isset($entry)) {
                $this->register($className);
                try {
                    $instance->install();
                } catch (ConfigElementAlreadyExistsException $e) {
                }
                $entry = $this->moduleRepository()->findOneBy(["name" => $className]);
                $this->entityManger()->persist($entry->setInstalled(true));
                $needFlush = true;
            }

            $instance->activate();

            if ($needFlush) {
                $this->entityManger()->flush();
            }
        }
    }
}
