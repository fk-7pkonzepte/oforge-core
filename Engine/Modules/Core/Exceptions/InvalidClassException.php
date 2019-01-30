<?php

namespace Oforge\Engine\Modules\Core\Exceptions;

/**
 * Class InvalidClassException
 *
 * @package Oforge\Engine\Modules\Core\Exceptions
 */
class InvalidClassException extends \Exception {

    /**
     * InvalidClassException constructor.
     *
     * @param string $classname
     * @param string $expectedClassName
     */
    public function __construct(string $classname, string $expectedClassName) {
        parent::__construct("The class $classname is not a child of $expectedClassName.");
    }

}
