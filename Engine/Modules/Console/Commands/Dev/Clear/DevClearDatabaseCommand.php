<?php

namespace Oforge\Engine\Modules\Console\Commands\Dev\Clear;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Monolog\Logger;
use Oforge\Engine\Modules\Console\Abstracts\AbstractCommand;
use Oforge\Engine\Modules\Console\Lib\Input;

/**
 * Class DevClearDatabaseCommand
 *
 * @package Oforge\Engine\Modules\Console\Commands\Dev\Clear
 */
class DevClearDatabaseCommand extends AbstractCommand {

    /**
     * DevClearDatabaseCommand constructor.
     */
    public function __construct() {
        parent::__construct('oforge:dev:clear:db', self::TYPE_DEVELOPMENT);
        $this->setDescription('Truncate database');
    }

    /**
     * @inheritdoc
     */
    public function handle(Input $input, Logger $output) : void {
        /** @var Connection $entityManagerConnection */
        $entityManagerConnection = Oforge()->DB()->getManager()->getConnection();
        // $entityManagerConnection->getConfiguration()->setSQLLogger(null);

        try {
            $entityManagerConnection->prepare('SET FOREIGN_KEY_CHECKS = 0;')->execute();
            foreach ($entityManagerConnection->getSchemaManager()->listTableNames() as $tableName) {
                $sql = 'DROP TABLE ' . $tableName;
                $entityManagerConnection->prepare($sql)->execute();
            }
            $entityManagerConnection->prepare('SET FOREIGN_KEY_CHECKS = 1;')->execute();
        } catch (DBALException $exception) {
            $output->emergency($exception->getMessage(), $exception->getTrace());
        }
    }

}
