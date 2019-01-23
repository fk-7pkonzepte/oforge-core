<?php

namespace Oforge\Engine\Modules\Core\Services;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Oforge\Engine\Modules\Core\Models\Store\KeyValue;

/**
 * Class KeyValueStoreService
 *
 * @package Oforge\Engine\Modules\Core\Services
 */
class KeyValueStoreService {
    /** @var EntityManager $entityManager */
    private $entityManager;
    /** @var EntityRepository $repository */
    private $repository;

    /**
     * KeyValueStoreService constructor.
     */
    public function __construct() {
        $this->entityManager = Oforge()->DB()->getManager();
        $this->repository    = $this->entityManager->getRepository(KeyValue::class);
    }

    /**
     * Get the value of a specific key from the key-value table
     *
     * @param string $name
     *
     * @return string|null
     */
    public function get(string $name) : ?string {
        /** @var KeyValue $element */
        $element = $this->repository->findOneBy(['name' => $name]);

        return isset($element) ? $element->getValue() : null;
    }

    /**
     * Create or update a key-value entry
     *
     * @param string $name
     * @param string $value
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function set(string $name, string $value) : void {
        /** @var KeyValue $element */
        $element = $this->repository->findOneBy(['name' => $name]);
        if (isset($element)) {
            $element->setValue($value);
        } else {
            $element = KeyValue::create([
                'name'  => $name,
                'value' => $value,
            ]);
            $this->entityManager->persist($element);
        }
        $this->entityManager->flush();
        $this->repository->clear();
    }

}
