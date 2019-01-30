<?php

namespace Oforge\Engine\Modules\Core\Exceptions;

/**
 * Class ParentNotFoundException
 *
 * @package Oforge\Engine\Modules\Core\Exceptions
 */
class ParentNotFoundException extends NotFoundException {

    /**
     * ParentNotFoundException constructor.
     *
     * @param string $name
     */
    public function __construct(string $name) {
        parent::__construct("Parent element with name '$name' not found!");
    }

}
