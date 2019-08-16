<?php

namespace Oforge\Engine\Modules\Media2\Twig;

use Exception;
use Oforge\Engine\Modules\Core\Exceptions\ServiceNotFoundException;
use Oforge\Engine\Modules\Media2\Services\ImageUploadService;
use Twig_Extension;
use Twig_ExtensionInterface;
use Twig_Function;

/**
 * Class UploadsExtension
 *
 * @package Oforge\Engine\Modules\Uploads\Twig
 */
class UploadsExtension extends Twig_Extension implements Twig_ExtensionInterface {

    /** @inheritDoc */
    public function getFunctions() {
        return [
            new Twig_Function('image', [$this, 'getImageUrl']),
        ];
    }

    public function getImageUrl($imageID, $options) {
        try {
            /** @var ImageUploadService $imageUploadService */
            $imageUploadService = Oforge()->Services()->get('uploads.image');
            return $imageUploadService->get($imageID, $options);
        } catch (Exception $exception) {
            Oforge()->Logger()->logException($exception);
            return '';
        }
    }

}
