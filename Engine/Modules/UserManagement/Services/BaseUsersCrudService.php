<?php
/**
 * Created by PhpStorm.
 * User: Alexander Wegner
 * Date: 17.12.2018
 * Time: 09:58
 */

namespace Oforge\Engine\Modules\UserManagement\Services;

use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use Oforge\Engine\Modules\Auth\Enums\InvalidPasswordFormatException;
use Oforge\Engine\Modules\Auth\Services\PasswordService;
use Oforge\Engine\Modules\Core\Abstracts\AbstractModel;
use Oforge\Engine\Modules\Core\Exceptions\NotFoundException;
use Oforge\Engine\Modules\Core\Exceptions\ServiceNotFoundException;
use Oforge\Engine\Modules\CRUD\Services\GenericCrudService;

class BaseUsersCrudService {

    /**
     * @var $passwordService PasswordService
     */
    protected $passwordService;

    /**
     * @var $crudService GenericCrudService
     */
    protected $crudService;

    /**
     * @var $userModel string
     */
    protected $userModel;

    /**
     * UserCrudService constructor.
     *
     * @throws ServiceNotFoundException
     */
    public function __construct() {
        $this->passwordService = Oforge()->Services()->get("password");
        $this->crudService     = Oforge()->Services()->get("crud");
    }

    /**
     * @param $userData
     *
     * @return bool
     * @throws InvalidPasswordFormatException
     */
    public function create($userData) : bool {
        if (isset($userData['password'])) {
            $userData['password'] = $this->passwordService->validateFormat($userData['password'])->hash($userData['password']);
            try {
                $this->crudService->create($this->userModel, $userData);

                return true;
            } catch (Exception $exception) {
                $msg = $exception->getPrevious();
                if (isset($msg)) {
                    $msg = $msg->getMessage();
                } else {
                    $msg = $exception->getMessage();
                }
                Oforge()->Logger()->get()->addWarning('Error trying to create a new user. ', ['exception' => $msg]);
            }
        }

        return false;
    }

    /**
     * @param $userData array
     *
     * @return bool
     * @throws NotFoundException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws InvalidPasswordFormatException
     */
    public function update($userData) {
        if (isset($userData['password'])) {
            $userData['password'] = $this->passwordService->validateFormat($userData["password"])->hash($userData["password"]);
        }
        $this->crudService->update($this->userModel, $userData);

        return true;
    }

    /**
     * @param $userId
     */
    public function delete($userId) {
        $this->crudService->delete($this->userModel, $userId);
    }

    /**
     * TODO: Check if the user data is valid. What data has to be validated?
     *
     * @param $userData array
     *
     * @return bool
     */
    public function isValid($userData) {
        // TODO: validation
    }

    /**
     * @param array $params
     *
     * @return array
     */
    public function list(array $params) {
        $entities = $this->crudService->list($this->userModel, $params);
        $entities = array_map(function ($entity) {
            /** @var AbstractModel $entity */
            return $entity->toArray();
        }, $entities);

        return $entities;
    }

    /**
     * @param int $id
     *
     * @return array
     * @throws ORMException
     */
    public function getById(int $id) {
        $entity = $this->crudService->getById($this->userModel, $id);

        return isset($entity) ? $entity->toArray() : [];
    }
}
