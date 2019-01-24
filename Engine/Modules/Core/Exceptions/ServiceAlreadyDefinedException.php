<?php

namespace Oforge\Engine\Modules\Core\Exceptions;

/**
 * Class ServiceAlreadyDefinedException
 *
 * @package Oforge\Engine\Modules\Core\Exceptions
 */
class ServiceAlreadyDefinedException extends \Exception {

    /**
     * ServiceAlreadyDefinedException constructor.
     *
     * @param string $name
     */
    public function __construct(string $name) {
        parent::__construct("Service with name '$name' already defined!");
    }

}
