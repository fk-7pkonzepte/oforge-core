<?php

namespace Oforge\Engine\Modules\Core\Models\Config;

use Doctrine\ORM\Mapping as ORM;
use Oforge\Engine\Modules\Core\Abstracts\AbstractModel;

/**
 * @ORM\Entity
 * @ORM\Table(name="oforge_core_config_values")
 */
class Value extends AbstractModel {
    /**
     * @var int $id
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;
    /**
     * @var Config $config
     * @ORM\ManyToOne(targetEntity="Config", inversedBy="values")
     * @ORM\JoinColumn(name="config_id", referencedColumnName="id")
     */
    private $config;
    /**
     * @var mixed $value
     * @ORM\Column(name="value", type="object", nullable=true, options={"default":null})
     */
    private $value = null;
    /**
     * @var string $scope
     * @ORM\Column(name="scope", type="string", nullable=true, options={"default":null})
     */
    private $scope = null;

    /**
     * @return int
     */
    public function getId() : int {
        return $this->id;
    }

    /**
     * @return Config
     */
    public function getConfig() : Config {
        return $this->config;
    }

    /**
     * @param Config $config
     *
     * @return Value
     */
    public function setConfig(Config $config) : Value {
        $this->config = $config;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getValue() {
        return $this->value;
    }

    /**
     * @param mixed $value
     *
     * @return Value
     */
    public function setValue($value) : Value {
        $this->value = $value;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getScope() : ?string {
        return $this->scope;
    }

    /**
     * @param int $scope
     *
     * @return Value
     */
    protected function setScope(int $scope) : Value {
        $this->scope = $scope;

        return $this;
    }

}
