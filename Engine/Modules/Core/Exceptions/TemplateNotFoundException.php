<?php

namespace Oforge\Engine\Modules\Core\Exceptions;

/**
 * Class TemplateNotFoundException
 *
 * @package Oforge\Engine\Modules\Core\Exceptions
 */
class TemplateNotFoundException extends NotFoundException {

    /**
     * TemplateNotFoundException constructor.
     *
     * @param string $name
     */
    public function __construct(string $name) {
        parent::__construct("Template with name '$name' not found.");
    }

}
