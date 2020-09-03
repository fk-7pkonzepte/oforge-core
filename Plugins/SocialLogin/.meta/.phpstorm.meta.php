<?php

namespace PHPSTORM_META {

    if (function_exists('override')) {
        override(\Oforge\Engine\Modules\Core\Manager\Services\ServiceManager::get(0), map([
            'sociallogin'           => \SocialLogin\Services\UserLoginService::class,
            'sociallogin.login'     => \SocialLogin\Services\LoginProviderService::class,
            'sociallogin.providers' => \SocialLogin\Services\LoginConnectService::class,
        ]));
    }

}
