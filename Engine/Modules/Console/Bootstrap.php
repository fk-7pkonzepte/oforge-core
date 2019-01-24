<?php

namespace Oforge\Engine\Modules\Console;

use Oforge\Engine\Modules\Console\Commands\Cleanup\CleanupLogfilesCommand;
use Oforge\Engine\Modules\Console\Commands\Console\CommandListCommand;
use Oforge\Engine\Modules\Console\Commands\Core\PingCommand;
use Oforge\Engine\Modules\Console\Commands\Dev\Clear\DevClearBatchCommand;
use Oforge\Engine\Modules\Console\Commands\Dev\Clear\DevClearDoctrineOrmCacheCommand;
use Oforge\Engine\Modules\Console\Commands\Dev\Clear\DevClearDropDatabaseTablesCommand;
use Oforge\Engine\Modules\Console\Commands\Dev\Clear\DevClearTruncateDatabaseTablesCommand;
use Oforge\Engine\Modules\Console\Commands\Doctrine\DoctrineOrmWrapperCommand;
use Oforge\Engine\Modules\Console\Commands\Example\ExampleBatchCommand;
use Oforge\Engine\Modules\Console\Commands\Example\ExampleCommandOne;
use Oforge\Engine\Modules\Console\Commands\Example\ExampleCommandThree;
use Oforge\Engine\Modules\Console\Commands\Example\ExampleCommandTwo;
use Oforge\Engine\Modules\Console\Commands\Example\ExampleGroupCommand;
use Oforge\Engine\Modules\Console\Commands\Service\ServiceListCommand;
use Oforge\Engine\Modules\Console\Commands\Service\ServiceRunCommand;
use Oforge\Engine\Modules\Console\Services\ConsoleService;
use Oforge\Engine\Modules\Core\Abstracts\AbstractBootstrap;

/**
 * Class Console-Bootstrap
 *
 * @package Oforge\Engine\Modules\Console
 */
class Bootstrap extends AbstractBootstrap {

    /**
     * Console-Bootstrap constructor.
     */
    public function __construct() {
        $this->commands = [
            CleanupLogfilesCommand::class,
            DevClearBatchCommand::class,
            DevClearDoctrineOrmCacheCommand::class,
            DevClearDropDatabaseTablesCommand::class,
            DevClearTruncateDatabaseTablesCommand::class,
            CommandListCommand::class,
            DoctrineOrmWrapperCommand::class,
            ExampleBatchCommand::class,
            ExampleGroupCommand::class,
            ExampleCommandOne::class,
            ExampleCommandTwo::class,
            ExampleCommandThree::class,
            PingCommand::class,
            ServiceListCommand::class,
            ServiceRunCommand::class,
        ];
        $this->services = [
            'console' => ConsoleService::class,
        ];
    }

}
