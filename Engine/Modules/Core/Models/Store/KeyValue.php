<?php

namespace Oforge\Engine\Modules\Core\Models\Store;

use Doctrine\ORM\Mapping as ORM;
use Oforge\Engine\Modules\Core\Abstracts\AbstractModel;

/**
 * @ORM\Entity
 * @ORM\Table(name="oforge_core_store_key_value")
 */
class KeyValue extends AbstractModel {
    /**
     * @var int $id
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;
    /**
     * @var string $name
     * @ORM\Column(name="name", type="string", nullable=false, unique=true)
     */
    private $name;
    /**
     * @var string $value
     * @ORM\Column(name="value", type="string", nullable=false)
     */
    private $value;

    /**
     * @return int
     */
    public function getId() : int {
        return $this->id;
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
     * @return KeyValue
     */
    protected function setName(string $name) : KeyValue {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getValue() : string {
        return $this->value;
    }

    /**
     * @param string $value
     *
     * @return KeyValue
     */
    public function setValue(string $value) : KeyValue {
        $this->value = $value;

        return $this;
    }

}
