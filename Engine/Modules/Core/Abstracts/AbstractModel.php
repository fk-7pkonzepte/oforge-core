<?php

namespace Oforge\Engine\Modules\Core\Abstracts;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Cache\FilesystemCache;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\Tools\Setup;

class AbstractModel
{

    /*
     * @param array $array
     * @param array $fillable optional property whitelist for mass-assignment
     *
     * @return ModelEntity
     */
    public function fromArray(array $array = [], array $fillable = [])
    {
        foreach ($array as $key => $value) {
            if (count($fillable) && !in_array($key, $fillable)) {
                continue;
            }
            $keys = explode("_", $key);
            $method = "set";

            foreach ($keys as $keyPart) {
                $method .= ucfirst($keyPart);
            }

            if (method_exists($this, $method)) {
                $this->$method($value);
            }
        }
        return $this;
    }


    /*
     *
     * @return array
     */
    public function toArray($maxLevel = 2, $currentLevel = 1)
    {
        $methods = get_class_methods($this);
        $result = [];
        foreach ($methods as $method) {
            if (substr($method, 0, 3) === 'get') {
                $param = lcfirst(substr($method, 3));
                $result[$param] = $this->assignArray($this->$method(), $maxLevel, $currentLevel);
            } elseif (substr($method, 0, 2) === 'is') {
                $param = lcfirst(substr($method, 2));
                $result[$param] = $this->assignArray($this->$method(), $maxLevel, $currentLevel);
            }
        }

        return $result;
    }

    private function assignArray($result, $maxLevel, $currentLevel)
    {
        if (is_subclass_of($result, AbstractModel::class)) {
            if ($maxLevel >= $currentLevel) {
                return $result->toArray($maxLevel, $currentLevel + 1);
            } else if (method_exists($result, "getId")) {
                return $result->getId();
            }
            return null;
        } else if ((is_array($result) || is_a($result, PersistentCollection::class)) && $maxLevel >= $currentLevel) {
            $t = [];
            foreach ($result as $item) {
                array_push($t, $item->assignArray($item, $maxLevel, $currentLevel + 1));
            }

            return $t;
        }

        return $result;
    }


    /*
     * @param array $array
     * @param array $fillable optional property whitelist for mass-assignment
     *
     * @return ModelEntity
     */
    public static function create(array $array = [], array $fillable = [])
    {
        $object = new static;
        $object->fromArray($array, $fillable);
        return $object;
    }
}
