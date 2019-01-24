<?php

namespace Oforge\Engine\Modules\Core\Exceptions;

/**
 * Class CouldNotInstallPluginException
 *
 * @package Oforge\Engine\Modules\Core\Exceptions
 */
class CouldNotInstallException extends \Exception {

    /**
     * CouldNotInstallPluginException constructor.
     *
     * @param string $type
     * @param string $classname
     * @param string[] $dependencies
     */
    public function __construct(string $type, string $classname, $dependencies) {
        parent::__construct("The $type $classname could not be installed due to missing dependencies. Missing ${type}s: " . implode(', ', $dependencies));
    }

}
