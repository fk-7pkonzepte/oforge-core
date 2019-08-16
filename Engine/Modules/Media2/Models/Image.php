<?php

namespace Oforge\Engine\Modules\Media2\Models;

use Doctrine\ORM\Mapping as ORM;
use Oforge\Engine\Modules\Core\Abstracts\AbstractModel;

/**
 * Class Image
 *
 * @package Oforge\Engine\Modules\Uploads\Models
 * @ORM\Entity
 * @ORM\Table(name="oforge_upload_image")
 */
class Image extends AbstractModel {
    /**
     * @var int $id
     * @ORM\Column(name="id", type="bigint", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;
    /**
     * @var string $mimeType
     * @ORM\Column(name="mime_type", type="string", nullable=false)
     */
    private $mimeType;
    /**
     * @var string $name
     * @ORM\Column(name="name", type="string", nullable=false)
     */
    private $name;
    /**
     * @var string $path
     * @ORM\Column(name="path", type="string", nullable=false)
     */
    private $path;

    /**
     * @return int
     */
    public function getId() : int {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return Image
     */
    public function setId(int $id) : Image {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string
     */
    public function getMimeType() : string {
        return $this->mimeType;
    }

    /**
     * @param string $mimeType
     *
     * @return Image
     */
    public function setMimeType(string $mimeType) : Image {
        $this->mimeType = $mimeType;

        return $this;
    }

    /**
     * @return string
     */
    public function getName() : string {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return Image
     */
    public function setName(string $name) : Image {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getPath() : string {
        return $this->path;
    }

    /**
     * @param string $path
     *
     * @return Image
     */
    public function setPath(string $path) : Image {
        $this->path = $path;

        return $this;
    }

}
