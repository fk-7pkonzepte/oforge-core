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
class DevClearTruncateDatabaseTablesCommand extends AbstractCommand {

    /**
     * DevClearDatabaseCommand constructor.
     */
    public function __construct() {
        parent::__construct('oforge:dev:clear:db:truncate', self::TYPE_DEVELOPMENT);
        $this->setDescription('Truncate database tables');
    }

    /**
     * @inheritdoc
     */
    public function handle(Input $input, Logger $output) : void {
        /** @var Connection $entityManagerConnection */
        $entityManagerConnection = Oforge()->DB()->getEntityManager()->getConnection();
        // $entityManagerConnection->getConfiguration()->setSQLLogger(null);

        try {
            $entityManagerConnection->prepare('SET FOREIGN_KEY_CHECKS = 0;')->execute();
            $tableNames = $entityManagerConnection->getSchemaManager()->listTableNames();
            foreach ($tableNames as $tableName) {
                $sql = 'TRUNCATE ' . $tableName;
                $entityManagerConnection->prepare($sql)->execute();
            }
            $entityManagerConnection->prepare('SET FOREIGN_KEY_CHECKS = 1;')->execute();
        } catch (DBALException $exception) {
            $output->emergency($exception->getMessage(), $exception->getTrace());
        }
    }

}
