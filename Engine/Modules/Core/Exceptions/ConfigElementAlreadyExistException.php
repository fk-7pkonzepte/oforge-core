<?php

namespace Oforge\Engine\Modules\Core\Exceptions;

/**
 * Class ConfigElementAlreadyExistException
 *
 * @package Oforge\Engine\Modules\Core\Exceptions
 */
class ConfigElementAlreadyExistException extends AlreadyExistException {

    /**
     * ConfigElementAlreadyExistException constructor.
     *
     * @param string $name
     */
    public function __construct(string $name) {
        parent::__construct('Config element', $name);
    }

}
