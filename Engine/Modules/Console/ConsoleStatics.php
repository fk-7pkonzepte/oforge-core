<?php

namespace Oforge\Engine\Modules\Console;

use Oforge\Engine\Modules\Core\Statics;

/**
 * Class ConsoleStatics
 *
 * @package Oforge\Engine\Modules\Console
 */
class ConsoleStatics {
    /**
     * Relative path: /var/console/
     */
    public const CONSOLE_LOGS_DIR = Statics::VAR_DIR . DIRECTORY_SEPARATOR . 'console';
    /**
     * Absolute path: ROOT/var/console/
     */
    public const CONSOLE_LOGS_DIR_ABS = ROOT_PATH . self::CONSOLE_LOGS_DIR;

    /**
     * Prevent instance.
     */
    private function __construct() {
    }

}
