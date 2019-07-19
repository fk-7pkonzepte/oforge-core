<?php
/**
 * Created by PhpStorm.
 * User: Alexander Wegner
 * Date: 05.12.2018
 * Time: 15:49
 */

namespace Oforge\Engine\Modules\Media\Twig;

use Oforge\Engine\Modules\Core\Exceptions\ServiceNotFoundException;
use Oforge\Engine\Modules\Core\Helper\RouteHelper;
use Oforge\Engine\Modules\Media\Services\ImageCompressService;
use Twig_Extension;
use Twig_ExtensionInterface;
use Twig_Function;

/**
 * Class AccessExtension
 *
 * @package Oforge\Engine\Modules\TemplateEngine\Extensions\Twig
 */
class MediaExtension extends Twig_Extension implements Twig_ExtensionInterface {

    /**
     * @inheritDoc
     */
    public function getFunctions() {
        return [
            new Twig_Function('media', [$this, 'getMedia']),
        ];
    }

    /**
     * @param mixed ...$vars
     *
     * @return mixed|string
     * @throws ServiceNotFoundException
     */
    public function getMedia(...$vars) {
        $result    = '';
        $varLength = count($vars);
        if ($varLength > 0) {
            /** @var ImageCompressService $configService */
            $configService = Oforge()->Services()->get('image.compress');
            if ($varLength == 2) {
                $result = $configService->getPath($vars[0], $vars[1]);
            } else {
                $result = $configService->getPath($vars[0]);
            }
        }
        if (!empty($result)) {
            $result = RouteHelper::getUrlWithBasePath($result);
        }

        return $result;
    }

}
