<?php

namespace Oforge\Engine\Modules\Core\Models\Plugin;

use Doctrine\ORM\Mapping as ORM;
use Oforge\Engine\Modules\Core\Abstracts\AbstractModel;
use Oforge\Engine\Modules\Core\Helper\Statics;

/**
 * @ORM\Entity
 * @ORM\Table(name="oforge_core_middleware")
 */
class Middleware extends AbstractModel {
    /**
     * @var int $id
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;
    /**
     * @var string $name
     * @ORM\Column(name="name", type="string", nullable=false)
     */
    private $name;
    /**
     * @var string $class
     * @ORM\Column(name="class", type="string", nullable=false)
     */
    private $class;
    /**
     * @var bool $active
     * @ORM\Column(name="active", type="boolean", options={"default":false})
     */
    private $active = false;
    /**
     * @var int $order
     * @ORM\Column(name="orderby", type="integer", nullable=true, options={"default":Statics::DEFAULT_ORDER})
     */
    private $order = Statics::DEFAULT_ORDER;
    /**
     * @var Plugin $plugin
     * @ORM\ManyToOne(targetEntity="Plugin", inversedBy="middlewares")
     * @ORM\JoinColumn(name="plugin_id", referencedColumnName="id")
     */
    private $plugin;

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
     * @return Middleware
     */
    public function setName(string $name) : Middleware {
        $this->name = $name;

        return $this;
    }

    /**
     * @return bool
     */
    public function isActive() : bool {
        return $this->active;
    }

    /**
     * @param bool $active
     *
     * @return Middleware
     */
    public function setActive(bool $active) : Middleware {
        $this->active = $active;

        return $this;
    }

    /**
     * @return string
     */
    public function getClass() : string {
        return $this->class;
    }

    /**
     * @param string $class
     *
     * @return Middleware
     */
    public function setClass(string $class) : Middleware {
        $this->class = $class;

        return $this;
    }

    /**
     * @return int
     */
    public function getOrder() : int {
        return $this->order;
    }

    /**
     * @param int $order
     *
     * @return Middleware
     */
    public function setOrder(int $order) : Middleware {
        $this->order = $order;

        return $this;
    }

    /**
     * @return Plugin
     */
    public function getPlugin() : Plugin {
        return $this->plugin;
    }

    /**
     * @param Plugin $plugin
     *
     * @return Middleware
     */
    public function setPlugin(Plugin $plugin) : Middleware {
        $this->plugin = $plugin;

        return $this;
    }

}
