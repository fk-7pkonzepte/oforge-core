<?php

namespace Oforge\Engine\Modules\Core\Exceptions;

/**
 * Class CouldNotInstallModuleException
 *
 * @package Oforge\Engine\Modules\Core\Exceptions
 */
class CouldNotInstallModuleException extends CouldNotInstallException {

    /**
     * CouldNotInstallModuleException constructor.
     *
     * @param string $classname
     * @param string[] $dependencies
     */
    public function __construct(string $classname, $dependencies) {
        parent::__construct('module', $classname, $dependencies);
    }

}
