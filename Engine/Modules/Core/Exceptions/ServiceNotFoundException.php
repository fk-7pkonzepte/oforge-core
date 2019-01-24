<?php

namespace Oforge\Engine\Modules\Core\Exceptions;

/**
 * Class ServiceNotFoundException
 *
 * @package Oforge\Engine\Modules\Core\Exceptions
 */
class ServiceNotFoundException extends NotFoundException {

    /**
     * ServiceNotFoundException constructor.
     *
     * @param string $name
     */
    public function __construct(string $name) {
        parent::__construct("Service with name '$name' not found!");
    }

}
