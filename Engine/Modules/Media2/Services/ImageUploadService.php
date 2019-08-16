<?php

namespace Oforge\Engine\Modules\Media2\Services;

use Doctrine\ORM\ORMException;
use Oforge\Engine\Modules\Core\Abstracts\AbstractDatabaseAccess;
use Oforge\Engine\Modules\Core\Exceptions\ServiceNotFoundException;
use Oforge\Engine\Modules\Core\Helper\FileSystemHelper;
use Oforge\Engine\Modules\Core\Helper\Statics;
use Oforge\Engine\Modules\Media2\Models\Image;

/**
 * Class ImageUploadService
 *
 * @package Oforge\Engine\Modules\Uploads\Services
 */
class ImageUploadService extends AbstractDatabaseAccess {

    /**
     * ImageUploadService constructor.
     *
     * @throws ServiceNotFoundException
     */
    public function __construct() {
        parent::__construct(Image::class);
    }

    public function create(string $relativeFilePath, string $fileName, array $options = []) : ?Image {
        $mimeType = mime_content_type(ROOT_PATH . $relativeFilePath);

        try {
            $image = Image::create([
                'mimeType' => $mimeType,
                'name'     => $fileName,
                'path'     => str_replace('\\', '/', $relativeFilePath),
            ]);
            $this->entityManager()->create($image);
            // $this->imagickService->process($image, $options);

            return $image;
        } catch (ORMException $exception) {
            Oforge()->Logger()->logException($exception);
        }

        return null;
    }

    /**
     * @param array $fileData
     *
     * @return Image|null
     */
    public function createFromFile(array $fileData, array $options = []) : ?Image {
        if (isset($fileData['error']) && $fileData['error'] === UPLOAD_ERR_OK && isset($fileData['size']) && $fileData['size'] > 0) {
            $filePath               = $fileData['name'];
            $fileName               = pathinfo($filePath, PATHINFO_FILENAME);
            $targetFileName         = $this->getSanitizedFileName($filePath, $options);
            $relativeTargetFilePath = $this->getRelativeTargetFilePath($targetFileName);
            $targetFilePath         = ROOT_PATH . $relativeTargetFilePath;

            if (move_uploaded_file($fileData['tmp_name'], $targetFilePath)) {
                return $this->create($relativeTargetFilePath, $fileName, $options);
            } else {
                //TODO exception
            }
        } else {
            //TODO
        }

        return null;
    }

    /**
     * @param string $filePath
     * @param array $options
     *
     * @return Image|null
     */
    public function createFromDisk(string $filePath, array $options = []) {
        if (empty($filePath)) {
            //TODO throw exception
        }
        if (!file_exists($filePath) || !is_writable($filePath)) {
            //TODO throw exception
        }
        $fileName               = pathinfo($filePath, PATHINFO_FILENAME);
        $targetFileName         = $this->getSanitizedFileName($filePath, $options);
        $relativeTargetFilePath = $this->getRelativeTargetFilePath($targetFileName);
        $targetFilePath         = ROOT_PATH . $relativeTargetFilePath;

        if (rename($filePath, $targetFilePath)) {
            return $this->create($relativeTargetFilePath, $fileName, $options);
        } else {
            //TODO exception
        }
    }

    public function replaceFromFile($imageID, array $fileData, array $options = []) {
        //TODO later
    }

    public function replaceFromDisk($imageID, string $filePath, array $options = []) {
        //TODO later
    }

    /**
     * @param string|int $imageID
     *
     * @return bool
     */
    public function delete($imageID) : bool {
        try {
            $image = $this->get($imageID);
            //TODO delete image: remove all resized images + empty folder
            // $this->entityManager()->remove($image);
        } catch (ORMException $exception) {
            Oforge()->Logger()->logException($exception);
        }

        return false;
    }

    /**
     * @param string|int $imageID
     *
     * @return Image|null
     * @throws ORMException
     */
    public function get($imageID) : ?Image {
        return $this->getBy([
            'id' => $imageID,
        ]);
    }

    /**
     * @param string $path
     *
     * @return Image|null
     * @throws ORMException
     */
    public function getByPath($path) : ?Image {
        return $this->getBy([
            'path' => $path,
        ]);
    }

    protected function getSanitizedFileName(string $filePath, array $options) {
        $pathInfo = pathinfo($filePath);
        $fileName = $pathInfo['fileName'];
        $fileName = strtolower($fileName);
        if (isset($options['prefix']) && !empty($options['prefix'])) {
            $fileName = $options['prefix'] . '_' . $fileName;
        }
        $fileName = filter_var($fileName, FILTER_SANITIZE_URL);
        $fileName = rawurlencode($fileName);

        return $fileName . '.' . $pathInfo['extension'];
    }

    /**
     * @param string $fileName
     *
     * @return string
     */
    protected function getRelativeTargetFilePath(string $fileName) : string {
        $relativeFilePath = Statics::IMAGES_DIR . DIRECTORY_SEPARATOR . substr(md5(rand()), 0, 2) . DIRECTORY_SEPARATOR . substr(md5(rand()), 0, 2)
                            . DIRECTORY_SEPARATOR . $fileName;
        FileSystemHelper::mkdir(dirname(ROOT_PATH . $relativeFilePath));

        return $relativeFilePath;
    }

    /**
     * @param array $criteria
     *
     * @return Image|null
     * @throws ORMException
     */
    protected function getBy(array $criteria) : ?Image {
        /** @var Image $image */
        $image = $this->repository()->findOneBy($criteria);

        return $image;
    }
    /**
     * @param Image $image
     * @param array $options
     */
    public function processOptions(Image $image, array $options = []) {
    }

    public function compress(string $filePath) {

    }

    public function scape(string $filePath, int $with, int $height = 0, $targetFilePath = null) {
        //TODO
    }

}
