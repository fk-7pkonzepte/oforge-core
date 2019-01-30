<?php

namespace Oforge\Engine\Modules\Core\Exceptions;

/**
 * Class AlreadyExistException
 *
 * @package Oforge\Engine\Modules\Core\Exceptions
 */
class AlreadyExistException extends \Exception {

    /**
     * AlreadyExistException constructor.
     * Message = $prefix '$name' already exist!
     *
     * @param string $prefix Prefix of message
     * @param string $name
     */
    public function __construct(string $prefix, string $name) {
        parent::__construct("$prefix '$name' already exists!");
    }

}
