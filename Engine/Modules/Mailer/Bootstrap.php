<?php

namespace Oforge\Engine\Modules\Mailer;

use Oforge\Engine\Modules\Core\Abstracts\AbstractBootstrap;
use Oforge\Engine\Modules\Core\Services\ConfigService;
use Oforge\Engine\Modules\Mailer\Services\MailService;

/**
 * Class Mailer-Bootstrap
 *
 * @package Oforge\Engine\Modules\Mailer
 */
class Bootstrap extends AbstractBootstrap {

    public function __construct() {
        $this->services = [
            'mail' => MailService::class,
        ];
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Oforge\Engine\Modules\Core\Exceptions\ConfigOptionKeyNotExistException
     * @throws \Oforge\Engine\Modules\Core\Exceptions\ServiceNotFoundException
     */
    public function install() {
        /** @var $configService ConfigService */
        $configService = Oforge()->Services()->get('config');
        $configService->add([
            'name'     => 'mailer_host',
            'label'    => 'mailer_host',
            // 'label'    => 'E-Mail Server',
            'type'     => 'string',
            'required' => true,
            'default'  => '',
            'group'    => 'mailer',
        ]);
        $configService->add([
            'name'     => 'mailer_username',
            'label'    => 'mailer_username',
            // 'label'    => 'E-Mail Username',
            'type'     => 'string',
            'required' => true,
            'default'  => '',
            'group'    => 'mailer',
        ]);
        $configService->add([
            'name'     => 'mailer_port',
            'label'    => 'mailer_port',
            // 'label'    => 'E-Mail Server Port',
            'type'     => 'integer',
            'required' => true,
            'default'  => 587,
            'group'    => 'mailer',
        ]);
        $configService->add([
            'name'     => 'mailer_exceptions',
            'label'    => 'mailer_exceptions',
            // 'label'    => 'E-Mail Exceptions',
            'type'     => 'boolean',
            'required' => true,
            'default'  => true,
            'group'    => 'mailer',
        ]);
        $configService->add([
            'name'     => 'mailer_smtp_password',
            'label'    => 'mailer_smtp_password',
            // 'label'    => 'SMTP Password',
            'type'     => 'password',
            'required' => true,
            'default'  => '',
            'group'    => 'mailer',
        ]);
        $configService->add([
            'name'     => 'mailer_smtp_debug',
            'label'    => 'mailer_smtp_debug',
            // 'label'    => 'STMP Debug',
            'type'     => 'integer',
            'required' => true,
            'default'  => 2,
            'group'    => 'mailer',
        ]);
        $configService->add([
            'name'     => 'mailer_smtp_auth',
            'label'    => 'mailer_smtp_auth',
            // 'label'    => 'SMTP Auth',
            'type'     => 'boolean',
            'required' => true,
            'default'  => true,
            'group'    => 'mailer',
        ]);
        $configService->add([
            'name'     => 'mailer_smtp_secure',
            'label'    => 'mailer_smtp_secure',
            // 'label'    => 'Enable TLS encryption',
            'type'     => 'boolean',
            'required' => true,
            'default'  => true,
            'group'    => 'mailer',
        ]);
        $configService->add([
            'name'     => 'mailer_from',
            'label'    => 'mailer_from',
            // 'label'    => 'Mailer From',
            'type'     => 'string',
            'required' => true,
            'default'  => '',
            'group'    => 'mailer',
        ]);

        // TODO: Implement install() method.
    }

}
