<?php

namespace Oforge\Engine\Modules\Console\Commands\Dev\Clear;

use Oforge\Engine\Modules\Console\Abstracts\AbstractBatchCommand;

/**
 * Class DevClearBatchCommand
 * Run all oforge:dev:cleanup:* cleanup commands.
 *
 * @package Oforge\Engine\Modules\Console\Commands\Dev
 */
class DevClearBatchCommand extends AbstractBatchCommand {

    /**
     * DevClearBatchCommand constructor.
     *
     * @throws \Oforge\Engine\Modules\Core\Exceptions\ServiceNotFoundException
     */
    public function __construct() {
        parent::__construct('oforge:dev:clear', [], self::TYPE_DEVELOPMENT);
        $this->setDescription('Run all oforge:dev:cleanup:* cleanup commands.');
    }

}
