<?php

namespace Oforge\Engine\Modules\Core\Exceptions;

/**
 * Class CouldNotInstallPluginException
 *
 * @package Oforge\Engine\Modules\Core\Exceptions
 */
class CouldNotInstallPluginException extends CouldNotInstallException {

    /**
     * CouldNotInstallModuleException constructor.
     *
     * @param string $classname
     * @param string[] $dependencies
     */
    public function __construct(string $classname, $dependencies) {
        parent::__construct('plugin', $classname, $dependencies);
    }

}
