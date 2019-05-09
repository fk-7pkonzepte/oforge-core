<?php

namespace Oforge\Engine\Modules\Core\Services;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Oforge\Engine\Modules\Core\Abstracts\AbstractDatabaseAccess;
use Oforge\Engine\Modules\Core\Exceptions\ConfigOptionKeyNotExistsException;
use Oforge\Engine\Modules\Core\Models\Plugin\Middleware;
use Oforge\Engine\Modules\Core\Models\Plugin\Plugin;

/**
 * Class MiddlewareService
 *
 * @package Oforge\Engine\Modules\Core\Services
 */
class MiddlewareService extends AbstractDatabaseAccess {

    public function __construct() {
        parent::__construct(Middleware::class);
    }

    /**
     * Get all active middlewares.
     *
     * @param string $name
     *
     * @return Middleware[]
     * @throws ORMException
     */
    public function getActive(string $name) {
        // $middlewares = $this->repository->findBy([
        //     'name'   => [$name, '*', $name . '*'],
        //     'active' => true,
        // ], [
        //     'order' => 'DESC',
        // ]);
        $queryBuilder = $this->entityManager()->createQueryBuilder();
        $result       = $queryBuilder->select(['m'])->from(Middleware::class, 'm')->where($queryBuilder->expr()->orX($queryBuilder->expr()->eq('m.name', '?1')))
                                     ->andWhere($queryBuilder->expr()->eq('m.active', 1))->orderBy('m.order', 'DESC')->setParameters([1 => $name])->getQuery();
        $middlewares  = $result->execute();
        // var_dump(array_map(function ($e) { return $e->getName();
        // }, $middlewares));

        return $middlewares;
    }

    /**
     * get all active middlewares
     *
     * @return array|null
     * @throws ORMException
     */
    public function getAllDistinctActiveNames() {
        $queryBuilder = $this->entityManager()->createQueryBuilder();
        $result       = $queryBuilder->select(['m.name'])->from(Middleware::class, 'm')->where($queryBuilder->expr()->eq('m.active', 1))
                                     ->andWhere($queryBuilder->expr()->neq('m.name', '?1'))->groupBy("m.name")->setParameters([1 => '*'])->getQuery();
        $middlewares  = $result->execute();

        $names = [];

        foreach ($middlewares as $middleware) {
            array_push($names, $middleware['name']);
        }

        return $names;
    }

    /**
     * Install middlewares by bootstrap middleware config.
     *
     * @param array $middlewareConfigs
     * @param Plugin|null $plugin
     *
     * @return Middleware[]
     * @throws ConfigOptionKeyNotExistsException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function install(array $middlewareConfigs, ?Plugin $plugin = null) {
        /** @var Middleware[] $result */
        $result = [];

        if (is_array($middlewareConfigs) && !empty($middlewareConfigs)) {
            $flush = false;
            foreach ($middlewareConfigs as $pathName => $middlewareConfig) {
                if ($this->isValid($middlewareConfig)) {
                    // Check if the element is already within the system
                    $middleware = $this->repository()->findOneBy([
                        'class' => $middlewareConfig['class'],
                    ]);
                    if (!isset($middleware)) {
                        $middleware = Middleware::create([
                            'name'   => $pathName,
                            'active' => false,
                            'class'  => $middlewareConfig['class'],
                            'order'  => $middlewareConfig['order'],
                        ]);
                        if (isset($plugin)) {
                            $middleware->setPlugin($plugin);
                        }
                        $this->entityManager()->persist($middleware);
                        $flush = true;
                    }
                    $result[] = $middleware;
                }
            }
            if ($flush) {
                $this->entityManager()->flush();
            }
        }

        return $result;
    }

    /**
     * Deinstall middlewares by bootstrap middleware config.
     *
     * @param array $middlewareConfigs
     *
     * @throws ConfigOptionKeyNotExistsException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function deinstall(array $middlewareConfigs) {
        $this->iterateMiddlewares($middlewareConfigs, function (Middleware $middleware) {
            $this->entityManager()->remove($middleware);
            //TODO Plugin???
        });
    }

    /**
     * Activate middlewares by bootstrap middleware config.
     *
     * @param array $middlewareConfigs
     *
     * @throws ConfigOptionKeyNotExistsException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function activate(array $middlewareConfigs) {
        $this->iterateMiddlewares($middlewareConfigs, function (Middleware $middleware) {
            $middleware->setActive(true);
        });
    }

    /**
     * Deactivate middlewares by bootstrap middleware config.
     *
     * @param array $middlewareConfigs
     *
     * @throws ConfigOptionKeyNotExistsException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function dectivate(array $middlewareConfigs) {
        $this->iterateMiddlewares($middlewareConfigs, function (Middleware $middleware) {
            $middleware->setActive(false);
        });
    }

    /**
     * @param string $middlewareName
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @deprecated
     */
    public function activateMiddleware(string $middlewareName) {
        $this->changeActiveState($middlewareName, true);
    }

    /**
     * @param string $middlewareName
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @deprecated
     */
    public function deactivateMiddleware(string $middlewareName) {
        $this->changeActiveState($middlewareName, false);
    }

    /**
     * Iterate through middlewares by bootstrap middleware config.
     *
     * @param array $middlewareConfigs
     *
     * @throws ConfigOptionKeyNotExistsException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function iterateMiddlewares(array $middlewareConfigs, callable $callable) : void {
        if (is_array($middlewareConfigs) && !empty($middlewareConfigs)) {
            $pathNames = [];
            foreach ($middlewareConfigs as $pathName => $middlewareConfig) {
                if ($this->isValid($middlewareConfig)) {
                    $pathNames[] = $pathName;
                }
            }
            if (!empty($pathNames)) {
                /** @var Middleware[] $middlewares */
                $middlewares   = $this->repository()->findBy([
                    'name' => $pathNames,
                ]);
                $entityManager = $this->entityManager();
                foreach ($middlewares as $middleware) {
                    if (!$entityManager->contains($middleware)) {
                        $entityManager->merge($middleware);
                    }
                    $callable($middleware);
                }
                $entityManager->flush();
            }
        }
    }

    /**
     * @param string $middlewareName
     * @param bool $active
     *
     * @throws ORMException
     * @throws OptimisticLockException
     * @deprecated
     */
    private function changeActiveState(string $middlewareName, bool $active) {
        $middleware = $this->repository()->findOneBy(['class' => $middlewareName]);
        $middleware->setActive($active);
        //$this->entityManager()->persist($middleware);
        $this->entityManager()->flush($middleware);
    }

    /**
     * @param array $options
     *
     * @return bool
     * @throws ConfigOptionKeyNotExistsException
     */
    private function isValid(array $options) {
        // Check if required keys are within the options
        $keys = ['class'];
        foreach ($keys as $key) {
            if (!isset($options[$key])) {
                throw new ConfigOptionKeyNotExistsException($key);
            }
        }
        // Check if correct type are set
        if (isset($options['order']) && !is_integer($options['order'])) {
            throw new \InvalidArgumentException('Order value should be of type integer.');
        }

        return true;
    }

}
