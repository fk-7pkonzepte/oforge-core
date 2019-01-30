<?php
/**
 * Created by PhpStorm.
 * User: Alexander Wegner
 * Date: 06.12.2018
 * Time: 11:11
 */

namespace Oforge\Engine\Modules\CRUD\Services;

use Oforge\Engine\Modules\Core\Abstracts\AbstractModel;
use Oforge\Engine\Modules\Core\Exceptions\ConfigElementAlreadyExistException;
use Oforge\Engine\Modules\Core\Exceptions\NotFoundException;


/**
 * Class GenericCrudService
 * @package Oforge\Engine\Modules\CRUD\Services;
 */
class GenericCrudService
{
    /**
     * GenericCrudService constructor.
     */
    public function __construct()
    {
        $this->em = Oforge()->DB()->getEntityManager();
    }

    public function list($class, $params = [])
    {
        $repo = $this->getRepo($class);

        /**
         * @var AbstractModel[] $items
         */
        $items = [];
        $items = $repo->findAll();

        if (sizeof($params) > 0) {
            //TODO
        } else {
            $items = $repo->findAll();
        }

        $result = [];
        foreach ($items as $item) {
            array_push($result, $item->toArray());
        }

        return $result;
    }


    public function definition($class)
    {
        return $class::definition();
    }

    /**
     * @param int $id
     *
     * @return object|null
     */
    public function getById($class, int $id)
    {
        $repo = $this->getRepo($class);
        $result = $repo->findOneBy(["id" => $id]);
        return $result;
    }

    /**
     * @param $class
     * @param array $options
     *
     * @throws ConfigElementAlreadyExistException
     * @throws \Doctrine\ORM\ORMException
     */
    public function create($class, array $options)
    {
        $repo = $this->getRepo($class);

        if (isset($options["id"])) {
            $element = $repo->findOneBy(["id" => $options["id"]]);
            if (isset($element)) {
                throw new ConfigElementAlreadyExistException("Element with id " . $options["id"] . " already exists!" );
            }
        }

        /**
         * @var $instance AbstractModel
         */
        $instance = new $class();
        $instance = $instance->fromArray($options);

        $this->em->persist($instance);
        $this->em->flush();
    }

    public function update($class, array $options)
    {
        $objects = $this->structure($options);

        $repo = $this->getRepo($class);

        foreach ($objects as $id => $el) {
            $element = $repo->findOneBy(["id" => $id]);

            if (!isset($element)) {
                throw new NotFoundException("Element with id " . $id . " not found!");
            }

            $element->fromArray($el);
            $this->em->persist($element);
        }

        $this->em->flush();
    }

    private function structure($options) {
        $result = array();
        foreach ($options as $key => $value) {
            $ex = explode("_", $key);
            if(sizeof($ex) == 2) {
                if(!isset($result[$ex[0]])) {
                    $result[$ex[0]] = [];
                }

                $result[$ex[0]][$ex[1]] = $value;
            }
        }

        return $result;
    }

    public function delete($class, int $id)
    {
        $repo = $this->getRepo($class);

        $element = $repo->findOneBy(["id" => $id]);

        $this->em->remove($element);
        $this->em->flush();
    }

    /**
     * @param $class
     * @return \Doctrine\Common\Persistence\ObjectRepository|\Doctrine\ORM\EntityRepository
     */
    private function getRepo($class)
    {
        return $this->em->getRepository($class);
    }
}
