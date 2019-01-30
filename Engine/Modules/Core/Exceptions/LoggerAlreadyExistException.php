<?php

namespace Oforge\Engine\Modules\Core\Exceptions;

/**
 * Class LoggerAlreadyExistException
 *
 * @package Oforge\Engine\Modules\Core\Exceptions
 */
class LoggerAlreadyExistException extends AlreadyExistException {

    /**
     * LoggerAlreadyExistException constructor.
     *
     * @param string $name
     */
    public function __construct(string $name) {
        parent::__construct('Logger', $name);
    }

}
