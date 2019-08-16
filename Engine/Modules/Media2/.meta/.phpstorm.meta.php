<?php

namespace PHPSTORM_META {

    if (function_exists('override')) {
        override(\Oforge\Engine\Modules\Core\Manager\Services\ServiceManager::get(0), map([
            'media2.image'         => \Oforge\Engine\Modules\Media2\Services\ImageUploadService::class,
            'media2.vimeo'         => \Oforge\Engine\Modules\Media2\Services\VimeoUploadService::class,
        ]));
    }

}
