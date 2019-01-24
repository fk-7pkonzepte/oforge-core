<?php

namespace Oforge\Engine\Modules\Core\Exceptions;

/**
 * Class CouldNotActivatePluginException
 *
 * @package Oforge\Engine\Modules\Core\Exceptions
 */
class CouldNotActivatePluginException extends \Exception {

    /**
     * CouldNotActivatePluginException constructor.
     *
     * @param string $classname
     * @param string[] $dependencies
     */
    public function __construct(string $classname, $dependencies) {
        parent::__construct("The plugin $classname could not be activated due to missing / not installed / not activated dependencies. Missing plugins: "
                            . implode(', ', $dependencies));
    }

}
