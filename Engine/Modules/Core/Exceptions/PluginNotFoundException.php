<?php

namespace Oforge\Engine\Modules\Core\Exceptions;

/**
 * Class PluginNotFoundException
 *
 * @package Oforge\Engine\Modules\Core\Exceptions
 */
class PluginNotFoundException extends NotFoundException {

    /**
     * PluginNotFoundException constructor.
     *
     * @param string $classname
     */
    public function __construct(string $classname) {
        parent::__construct("Plugin with name '$classname' not found!");
    }

}
