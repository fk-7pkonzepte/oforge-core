<?php

namespace Oforge\Engine\Modules\Media\Services;

use Doctrine\ORM\ORMException;
use Imagick;
use ImagickException;
use Insertion\Models\InsertionMedia;
use Oforge\Engine\Modules\Core\Abstracts\AbstractDatabaseAccess;
use Oforge\Engine\Modules\Core\Exceptions\ServiceNotFoundException;
use Oforge\Engine\Modules\Media\Models\Media;

/**
 * Class ImageRotateService
 *
 * @package Oforge\Engine\Modules\Media\Services
 * @deprecated
 */
class ImageRotateService extends AbstractDatabaseAccess {

    public function __construct() {
        parent::__construct(['default' => Media::class, 'insertionMedia' => InsertionMedia::class]);
    }

    /**
     * @param string|null $path
     * @param int $width
     *
     * @return string|null
     * @throws ORMException
     * @throws ServiceNotFoundException
     */
    public function getPath(?string $path, int $width = 0) : ?string {
        if (!isset($path)) {
            return null;
        }
        /** @var MediaService $mediaService */
        $mediaService = Oforge()->Services()->get('media');
        $media        = $mediaService->getByPath($path);

        if (!isset($media)) {
            $media = $mediaService->getById($path);
            if (!isset($media)) {
                return $path;
            }
        }

        if ($width > 0) {
            $fileExtension = $this->getFileExtension($media);

            if (!empty($fileExtension)) {
                $cacheUrl = substr($media->getPath(), 0, -strlen($fileExtension) - 1) . '_' . $width . '.' . $fileExtension;
                //File is already compressed and stored
                if (file_exists(ROOT_PATH . $cacheUrl)) {
                    return $cacheUrl;
                }
                //File should be compressed
                if (extension_loaded('imagick')) {
                    $this->scale($media, $width, $cacheUrl);
                    $this->compress($cacheUrl);

                    if (file_exists(ROOT_PATH . $cacheUrl)) {
                        return $cacheUrl;
                    }
                }
            }
        }

        return $media->getPath();
    }

    public function getFileExtension(Media $media) {
        $fileExtension    = '';
        $tmpFileExtension = pathinfo($media->getPath(), PATHINFO_EXTENSION);
        switch ($media->getType()) {
            case 'image/jpeg':
            case 'image/jpg':
            case 'image/png':
                $fileExtension = $tmpFileExtension;
                break;
        }

        return $fileExtension;
    }

    /**
     * @param string $imagePath
     */
    public function compress(string $imagePath) {
        try {
            if (extension_loaded('imagick')) {
                $imagick     = new Imagick(ROOT_PATH . $imagePath);
                $image_types = getimagesize(ROOT_PATH . $imagePath);
                // Compress image

                // Set image as based its own type
                if ($image_types[2] === IMAGETYPE_JPEG) {
                    $imagick->setImageFormat('jpeg');
                    $imagick->setImageCompressionQuality(40);
                    $imagick->setSamplingFactors(['2x2', '1x1', '1x1']);
                    //$profiles = $imagick->getImageProfiles("icc", true);
                    // $imagick->stripImage();
                    // if (!empty($profiles)) {
                    //     $imagick->profileImage('icc', $profiles['icc']);
                    // }

                    $imagick->setInterlaceScheme(Imagick::INTERLACE_JPEG);
                    $imagick->setColorspace(Imagick::COLORSPACE_SRGB);

                } elseif ($image_types[2] === IMAGETYPE_GIF) {
                    $imagick->setImageFormat('gif');
                } elseif ($image_types[2] === IMAGETYPE_PNG) {
                    // $imagick->stripImage();
                    $imagick->setImageDepth(8);
                } else {
                    // not supported file type
                }

                $imagick->writeImage(ROOT_PATH . $imagePath);
            }
        } catch (ImagickException $e) {
            Oforge()->Logger()->get()->error('ImagickException', $e->getTrace());
        }
    }

    public function rotate(Media $media, int $rotate) {
        if (extension_loaded('imagick')) {
            try {
                $imagick = new Imagick(ROOT_PATH . $media->getPath());
                $imagick->rotateImage('#00000000', $rotate);

            } catch (ImagickException $e) {
                Oforge()->Logger()->get()->error('ImagickException', $e->getTrace());
            }
        }
    }
}
