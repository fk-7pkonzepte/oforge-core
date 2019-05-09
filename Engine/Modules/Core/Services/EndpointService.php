<?php

namespace Oforge\Engine\Modules\Core\Services;

use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\IndexedReader;
use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Oforge\Engine\Modules\Core\Abstracts\AbstractDatabaseAccess;
use Oforge\Engine\Modules\Core\Annotation\Endpoint\EndpointAction;
use Oforge\Engine\Modules\Core\Annotation\Endpoint\EndpointClass;
use Oforge\Engine\Modules\Core\Helper\ArrayPhpFileStorage;
use Oforge\Engine\Modules\Core\Helper\Statics;
use Oforge\Engine\Modules\Core\Helper\StringHelper;
use Oforge\Engine\Modules\Core\Models\Endpoint\Endpoint as EndpointModel;
use Oforge\Engine\Modules\Core\Models\Endpoint\EndpointMethod;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

/**
 * Class EndpointService
 *
 * @package Oforge\Engine\Modules\Core\Services
 */
class EndpointService extends AbstractDatabaseAccess {
    /** @var array $configCache */
    private $configCache = [];

    /**
     * EndpointService constructor.
     */
    public function __construct() {
        parent::__construct(EndpointModel::class);
    }

    /**
     * Store endpoints in a database table
     *
     * @param array $endpoints
     *
     * @throws AnnotationException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function install(array $endpoints) {
        $endpointConfigs = $this->prepareEndpointConfigs($endpoints);

        $created = false;
        foreach ($endpointConfigs as $endpointConfig) {
            /** @var EndpointModel $endpoint */
            $endpoint = $this->repository()->findOneBy(['name' => $endpointConfig['name']]);
            if (!isset($endpoint)) {
                $endpoint = EndpointModel::create($endpointConfig);
                $this->entityManager()->persist($endpoint);
                $created = true;
            }
        }

        if ($created) {
            $this->entityManager()->flush();
            $this->repository()->clear();
        }
    }

    /**
     * Activation of endpoints.
     *
     * @param array $endpoints
     *
     * @throws AnnotationException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function activate(array $endpoints) {//TODO ungetestet
        $this->iterateEndpointModels($endpoints, function (EndpointModel $endpoint) {
            $endpoint->setActive(true);
        });
    }

    /**
     * Deactivation of endpoints.
     *
     * @param array $endpoints
     *
     * @throws AnnotationException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function deactivate(array $endpoints) {//TODO ungetestet
        $this->iterateEndpointModels($endpoints, function (EndpointModel $endpoint) {
            $endpoint->setActive(false);
        });
    }

    /**
     * Removing endpoints
     *
     * @param array $endpoints
     *
     * @throws AnnotationException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function deinstall(array $endpoints) {//TODO ungetestet
        $this->iterateEndpointModels($endpoints, function (EndpointModel $endpoint) {
            $this->entityManager()->remove($endpoint);
        });
    }

    /**
     * @param array $endpoints
     * @param callable $callable
     *
     * @throws AnnotationException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    protected function iterateEndpointModels(array $endpoints, callable $callable) {
        $endpointConfigs = $this->prepareEndpointConfigs($endpoints);

        foreach ($endpointConfigs as $endpointConfig) {
            /** @var EndpointModel[] $endpoints */
            $endpoints = $this->repository()->findBy(['name' => $endpointConfig['name']]);

            if (!empty($endpoints)) {
                foreach ($endpoints as $endpoint) {
                    $callable($endpoint);
                }
                $this->entityManager()->flush();
            }
        }
        $this->repository()->clear();
    }

    /**
     * @param array $endpoints
     *
     * @return array
     * @throws AnnotationException
     */
    protected function prepareEndpointConfigs(array $endpoints) : array {
        $isProductionMode = Oforge()->Settings()->isProductionMode();
        $endpointConfigs  = [];
        class_exists(EndpointClass::class);
        class_exists(EndpointAction::class);
        if (!file_exists(Statics::ENDPOINT_CACHE_DIR)) {
            @mkdir(Statics::ENDPOINT_CACHE_DIR, 0777, true);
        }

        $reader = new IndexedReader(new AnnotationReader());
        // $isDevMode = false;
        // $cacheDir  = ROOT_PATH . DIRECTORY_SEPARATOR . Statics::CACHE_DIR . DIRECTORY_SEPARATOR . 'endpoint';
        // $filesystemCache = new FilesystemCache($cacheDir);
        // $reader    = new CachedReader($reader, $filesystemCache, $isDevMode);

        foreach ($endpoints as $class) {
            if (!is_string($class)) {
                continue;
            }
            $fileName = $class;
            if (StringHelper::startsWith($fileName, 'Oforge\Engine\Modules')) {
                $fileName = explode('Modules', $class, 2)[1];
                $fileName = ltrim(str_replace('\\', '_', $fileName), '_');
            }
            if ($isProductionMode) {
                $cacheFile = Statics::ENDPOINT_CACHE_DIR . DIRECTORY_SEPARATOR . $fileName . '.cache';
                if (file_exists($cacheFile)) {
                    if (!isset($this->configCache[$fileName])) {
                        $this->configCache[$fileName] = ArrayPhpFileStorage::load($cacheFile);
                    }
                    $endpointConfigsForClass = $this->configCache[$fileName];
                } else {
                    $endpointConfigsForClass      = $this->getEndpointConfigFromClass($reader, $class);
                    $this->configCache[$fileName] = $endpointConfigsForClass;
                    ArrayPhpFileStorage::write($cacheFile, $endpointConfigsForClass);
                }
            } else {
                if (!isset($this->configCache[$fileName])) {
                    $this->configCache[$fileName] = $this->getEndpointConfigFromClass($reader, $class);
                }
                $endpointConfigsForClass = $this->configCache[$fileName];
            }
            if (empty($endpointConfigsForClass)) {
                Oforge()->Logger()->get()->addWarning("An endpoint was defined but the corresponding controller '$class' has no action methods.");
            } else {
                $endpointConfigs = array_merge($endpointConfigs, $endpointConfigsForClass);
            }
        }
        if (!empty($endpoints) && empty($endpointConfigs)) {
            Oforge()->Logger()->get()->addWarning('Endpoints were defined but the corresponding controllers has no action methods.', $endpoints);
        }

        return $endpointConfigs;
    }

    /**
     * Extract endpoint config from Class.
     *
     * @param Reader $reader
     * @param string $class
     *
     * @return array
     * @throws AnnotationException
     */
    protected function getEndpointConfigFromClass(Reader $reader, string $class) {
        $endpointConfigs = [];
        try {
            $reflectionClass = new ReflectionClass($class);
            /** @var EndpointClass $classAnnotation */
            $classAnnotation = $reader->getClassAnnotation($reflectionClass, EndpointClass::class);
            if (is_null($classAnnotation)) {
                Oforge()->Logger()->get()
                        ->addWarning("An endpoint was defined but the corresponding controller '$class' has no configurated annotation 'EndpointClass'.");

                return $endpointConfigs;
            }
            $classAnnotation->checkRequired($class);

            $classMethods = get_class_methods($class);
            if (is_null($classMethods)) {
                Oforge()->Logger()->get()->addWarning("Get class methods failed for '$class'. Maybe some namespace, class or method was defined wrong.");
                $classMethods = [];
            }
            foreach ($classMethods as $classMethod) {
                $isMethodActionPrefix = StringHelper::endsWith($classMethod, 'Action');
                if ($classAnnotation->isStrictActionSuffix() && !$isMethodActionPrefix) {
                    continue;
                }
                $reflectionMethod = new ReflectionMethod($class, $classMethod);
                /** @var EndpointAction $methodAnnotation */
                $methodAnnotation = $reader->getMethodAnnotation($reflectionMethod, EndpointAction::class);
                if (!$classAnnotation->isStrictActionSuffix() && is_null($methodAnnotation)) {
                    // skipping of methods without endpoint action annotation in disabled strict mode
                    continue;
                }
                $endpointConfig = $this->buildEndpointConfig($class, $classMethod, $isMethodActionPrefix, $classAnnotation, $methodAnnotation);
                if (!empty($endpointConfig)) {
                    $endpointConfigs[] = $endpointConfig;
                }
            }
        } catch (ReflectionException $exception) {
            Oforge()->Logger()->get()->addWarning('Reflection exception: ' . $exception->getMessage(), $exception->getTrace());
        }

        return $endpointConfigs;
    }

    /**
     * @param string $class
     * @param string $classMethod
     * @param bool $isMethodActionPrefix
     * @param EndpointClass $classAnnotation
     * @param EndpointAction $methodAnnotation
     *
     * @return array
     */
    protected function buildEndpointConfig(
        string $class,
        string $classMethod,
        bool $isMethodActionPrefix,
        EndpointClass $classAnnotation,
        ?EndpointAction $methodAnnotation
    ) : array {
        $name       = $classAnnotation->getName() . '_';
        $path       = $classAnnotation->getPath();
        $order      = null;
        $assetScope = null;
        $httpMethod = EndpointMethod::ANY;

        $actionName = $classMethod;
        if ($isMethodActionPrefix) {
            $actionName = explode('Action', $actionName)[0];
        }
        $isIndexAction = $actionName === 'index';

        if (isset($methodAnnotation)) {
            $order      = $methodAnnotation->getOrder();
            $assetScope = $methodAnnotation->getAssetScope();
            if (EndpointMethod::isValid($methodAnnotation->getMethod())) {
                $httpMethod = $methodAnnotation->getMethod();
            }
        }
        if (isset($methodAnnotation) && !empty($methodAnnotation->getPath())) {
            $path .= $methodAnnotation->getPath();
        } elseif (!$isIndexAction) {
            $path .= '/' . $actionName;
        }
        if (isset($methodAnnotation) && !empty($methodAnnotation->getName())) {
            $name .= $methodAnnotation->getName();
        } elseif (!$isIndexAction) {
            $name .= $actionName;
        }

        $name       = trim($name, '_');
        $path       = StringHelper::leading($path, '/');
        $order      = $order ?? $classAnnotation->getOrder() ?? Statics::DEFAULT_ORDER;
        $assetScope = $assetScope ?? $classAnnotation->getAssetScope() ?? 'Frontend';

        return [
            'name'             => $name,
            'path'             => $path,
            'controllerClass'  => $class,
            'controllerMethod' => $classMethod,
            'assetScope'       => $assetScope,
            'httpMethod'       => $httpMethod,
            'order'            => $order,
            // 'controllerAction' => $actionName ?? '-',
        ];
    }

}
