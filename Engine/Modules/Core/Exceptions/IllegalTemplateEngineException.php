<?php

namespace Oforge\Engine\Modules\Core\Exceptions;

/**
 * Class IllegalTemplateEngineException
 *
 * @package Oforge\Engine\Modules\Core\Exceptions
 */
class IllegalTemplateEngineException extends \Exception {

    /**
     * IllegalTemplateEngineException constructor.
     *
     * @param string $engineType
     */
    public function __construct(string $engineType) {
        parent::__construct("Config key $engineType exists but the call to $engineType is illegal.");
    }

}
