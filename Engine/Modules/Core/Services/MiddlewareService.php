<?php

namespace Oforge\Engine\Modules\Core\Services;

use Oforge\Engine\Modules\Core\Abstracts\AbstractBootstrap;
use Oforge\Engine\Modules\Core\Models\Plugin\Middleware;
use Oforge\Engine\Modules\Core\Models\Plugin\Plugin;

class MiddlewareService
{
    /**
     * @param $name
     *
     * @return array|Middleware[]
     */
    public function getActive($name)
    {
        $em = Oforge()->DB()->getEntityManager();
        $repo = $em->getRepository(Middleware::class);

        $middlewares = $repo->findBy(["name" => [$name, "*", $name . "*"], "active" => 1], ['order' => 'DESC']);

        return $middlewares;
    }
    
    /**
     * @param $options
     * @param $plugin
     *
     * @return Middleware[]
     */
    public function register($options, $plugin)
    {
        /**
         * @var $result Middleware[]
         */
        $result = [];
        if (is_array($options)) {

            foreach ($options as $key => $option) {
                if ($this->isValid($option)) {
                    /**
                     * Check if the element is already within the system
                     */
                    $repo = Oforge()->DB()->getEntityManager()->getRepository(Middleware::class);

                    $element = $repo->findOneBy(["class" => $option["class"]]);
                    if(!isset($element)) {
                        $element = Middleware::create(["name" => $key,  "class" => $option["class"], "order" => $option["order"]]);
                        $element->setPlugin($plugin);
                    }

                    array_push($result, $element);
                }
            }
        }

        return $result;
    }
    
    /**
     * @param $options
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function registerFromModule($options)
    {
        if (is_array($options)) {
            /**
             * Check if the element is already within the system
             */
            $repo = Oforge()->DB()->getEntityManager()->getRepository(Middleware::class);

            foreach ($options as $key => $option) {
                if ($this->isValid($option)) {

                    $element = $repo->findOneBy(["class" => $option["class"]]);
                    if(!isset($element)) {
                        $element = Middleware::create(["name" => $key,  "class" => $option["class"], "active" => 1, "order" => $option["order"]]);
                        Oforge()->DB()->getEntityManager()->persist($element);
                    }
                }
            }
        }

        Oforge()->DB()->getEntityManager()->flush();
    }
    
    /**
     * @param array $options
     *
     * @return bool
     */
    private function isValid(Array $options)
    {
        /**
         * Check if required keys are within the options
         */
        $keys = ["class"];
        foreach ($keys as $key) {
            if (!array_key_exists($key, $options)) throw new ConfigOptionKeyNotExists($key);
        }

        /*
         * Check if correct type are set
         */
        if (isset($options["order"]) && !is_integer($options["order"])) {
            throw new \InvalidArgumentException("Order value should be of type integer. ");
        }
        return true;
    }
}
