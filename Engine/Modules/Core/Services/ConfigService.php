<?php

namespace Oforge\Engine\Modules\Core\Services;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oforge\Engine\Modules\Core\Exceptions\ConfigElementNotFoundException;
use Oforge\Engine\Modules\Core\Exceptions\ConfigOptionKeyNotExistException;
use Oforge\Engine\Modules\Core\Helper\ArrayHelper;
use Oforge\Engine\Modules\Core\Models\Config\Config;
use Oforge\Engine\Modules\Core\Models\Config\Value;

/**
 * Class ConfigService
 *
 * @package Oforge\Engine\Modules\Core\Services
 */
class ConfigService {
    /** @var EntityManager $entityManager */
    private $entityManager;
    /** @var EntityRepository $repository */
    private $repository;

    public function __construct() {
        $this->entityManager = Oforge()->DB()->getEntityManager();
        $this->repository    = $this->entityManager->getRepository(Config::class);
    }

    /**
     * Insert a config entry into the database<br/>Options keys:<br/>
     * 'name' => '' (Required),<br/>
     * 'group' => '' (Required),<br/>
     * 'type' => 'boolean' | 'string' | 'password' | 'number' | 'integer' | 'select' (Required),<br/>
     * 'label' => '' (Required),<br/>
     * 'required' => true | false,<br/>
     * 'options' => ['', ...],<br/>
     * 'order' => [1337],<br/>
     * 'description' => ''[string],<br/>
     * 'default' => ...<br/>
     * 'value' => ...<br/>
     * 'values' => ... [array]<br/>
     *
     * @param array $options
     *
     * @throws ConfigOptionKeyNotExistException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function add(array $options) : void {
        if ($this->isValid($options)) {
            $config = $this->getConfig($options['name']);
            if (is_null($config)) {
                $defaultValue = ArrayHelper::get($options, 'default');
                if (isset($options['values']) && is_array($options['values'])) {
                    $options['values'] = array_map(function ($entry) use ($defaultValue) {
                        return Value::create([
                            'value' => ArrayHelper::get($entry, 'value', $defaultValue),
                            'scope' => ArrayHelper::get($entry, 'scope'),
                        ]);
                    }, $options['values']);
                } else {
                    $options['values'] = [
                        Value::create([
                            'value' => ArrayHelper::get($options, 'value', $defaultValue),
                        ]),
                    ];
                }
                $config = Config::create($options);
                foreach ($config->getValues() as $value) {
                    $value->setConfig($config);
                }
                $this->entityManager->persist($config);
                $this->entityManager->flush($config);
            }
            $this->repository->clear();
        }
    }

    /**
     * Remove configuration by name or if scope is set, only the scoped value.
     *
     * @param string $name
     * @param string|null $scope
     *
     * @throws ConfigElementNotFoundException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function remove(string $name, ?string $scope = null) {//TODO ungetestet
        $config = $this->getConfig($name);
        if (isset($config)) {
            foreach ($config->getValues() as $value) {
                if (is_null($scope) || $value->getScope() === $scope) {
                    $this->entityManager->remove($value);
                }
            }
            if (is_null($scope)) {
                $this->entityManager->remove($config);
            }
            $this->entityManager->flush();
            $this->repository->clear();
        } else {
            throw new ConfigElementNotFoundException($name, $scope);
        }
    }

    /**
     * Get a specific configuration value.
     *
     * @param string $name
     * @param string|null $scope
     *
     * @return mixed
     * @throws ConfigElementNotFoundException
     */
    public function get(string $name, ?string $scope = null) {
        $config = $this->getConfig($name);

        if (!is_null($config)) {
            foreach ($config->getValues() as $value) {
                if ($value->getScope() === $scope) {
                    return $value->getValue();
                }
            }
        }

        throw new ConfigElementNotFoundException($name, $scope);
    }

    /**
     * Set a specific configuration value.
     *
     * @param string $name
     * @param mixed $value
     * @param string|null $scope
     *
     * @return bool
     * @throws ConfigElementNotFoundException
     * @throws \Doctrine\Common\Persistence\Mapping\MappingException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function set(string $name, $value, ?string $scope = null) : bool {
        $config = $this->getConfig($name);

        if (!isset($config)) {
            throw new ConfigElementNotFoundException($name, $scope);
        }
        foreach ($config->getValues() as $configValue) {
            if ($configValue->getScope() == $scope) {
                $configValue->setValue($value);
                $this->entityManager->flush($configValue);
                $this->entityManager->clear();

                return true;
            }
        }
        throw new ConfigElementNotFoundException($name, $scope);
    }

    /**
     * Get distinct configuration group names.
     *
     * @return string[]
     */
    public function getConfigGroups() {
        return $this->repository->createQueryBuilder('c')->select('c.group')->distinct(true)->getQuery()->getArrayResult();
    }

    /**
     * Get all configurations by group name.
     *
     * @param string $groupName
     *
     * @return Config[]
     */
    public function getGroupConfigs(string $groupName) {
        return $this->repository->findBy(['group' => $groupName]);
    }

    /**
     * Get configuration by name from database.
     *
     * @param string $name
     *
     * @return Config|null
     */
    protected function getConfig(string $name) : ?Config {
        return $this->repository->findOneBy(['name' => strtolower($name)]);
    }

    /**
     * Check if the options are valid
     *
     * @param array $options
     *
     * @return bool
     * @throws ConfigOptionKeyNotExistException
     */
    protected function isValid(array $options) : bool {
        // Check if required keys are within the options
        $keys = ['name', 'group', 'label', 'type'];
        foreach ($keys as $key) {
            if (!array_key_exists($key, $options)) {
                throw new ConfigOptionKeyNotExistException($key);
            }
        }
        // Check if correct data type are set
        if (isset($options['required']) && !is_bool($options['required'])) {
            throw new \InvalidArgumentException('Required value should be of type bool.');
        }
        if (isset($options['order']) && !is_integer($options['order'])) {
            throw new \InvalidArgumentException('Position value should be of type integer.');
        }
        if (isset($options['options']) && !is_array($options['options'])) {
            throw new \InvalidArgumentException('Options value should be of type array.');
        }
        $keys = ['name', 'label', 'description', 'group', 'type'];
        foreach ($keys as $key) {
            if (isset($options[$key]) && !is_string($options[$key])) {
                throw new \InvalidArgumentException("Option '$key' value should be of type string.");
            }
        }
        // Check type values
        $types = ['boolean', 'string', 'password', 'number', 'integer', 'select'];
        $type  = $options['type'];
        if (!in_array($type, $types)) {
            throw new \InvalidArgumentException("Type '$type' is not a valid type.");
        }

        return true;
    }
}
