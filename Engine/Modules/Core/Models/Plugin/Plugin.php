<?php

namespace Oforge\Engine\Modules\Core\Models\Plugin;

use Doctrine\ORM\Mapping as ORM;
use Oforge\Engine\Modules\Core\Abstracts\AbstractBootstrap;
use Oforge\Engine\Modules\Core\Abstracts\AbstractModel;
use Oforge\Engine\Modules\Core\Statics;

/**
 * @ORM\Entity
 * @ORM\Table(name="oforge_core_plugins")
 */
class Plugin extends AbstractModel {
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
     * @var string $bootstrapClass
     * @ORM\Column(name="bootstrap_class", type="string", nullable=false, unique=true)
     */
    private $bootstrapClass;
    /**
     * @var AbstractBootstrap $bootstrapInstance
     */
    private $bootstrapInstance;
    /**
     * @var bool $installed
     * @ORM\Column(name="installed", type="boolean", options={"default":false})
     */
    private $installed = false;
    /**
     * @var bool $active
     * @ORM\Column(name="active", type="boolean", options={"default":false})
     */
    private $active = false;
    /**
     * @var int $order
     * @ORM\Column(name="orderby", type="integer", options={"default":Statics::DEFAULT_ORDER})
     */
    private $order = Statics::DEFAULT_ORDER;
    /**
     * @var Middleware[] $middlewares
     * @ORM\OneToMany(targetEntity="Middleware", mappedBy="plugin", cascade={"all"})
     * @ORM\JoinColumn(name="id", referencedColumnName="plugin_id")
     */
    private $middlewares;

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
     * @return Plugin
     */
    public function setName(string $name) : Plugin {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getBootstrapClass() : string {
        return $this->bootstrapClass;
    }

    /**
     * @param string $bootstrapClass
     *
     * @return Plugin
     */
    protected function setBootstrapClass(string $bootstrapClass) : Plugin {
        $this->bootstrapClass = $bootstrapClass;

        return $this;
    }

    /**
     * @return AbstractBootstrap|null
     */
    public function getBootstrapInstance() : ?AbstractBootstrap {
        if (is_null($this->bootstrapInstance) && class_exists($this->bootstrapClass)) {
            $this->bootstrapInstance = new $this->bootstrapClass();
        }

        return $this->bootstrapInstance;
    }

    /**
     * @param AbstractBootstrap $bootstrapInstance
     *
     * @return Plugin
     */
    public function setBootstrapInstance(AbstractBootstrap $bootstrapInstance) : Plugin {
        if (is_null($this->bootstrapInstance)) {
            $this->bootstrapInstance = $bootstrapInstance;
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isInstalled() : bool {
        return $this->installed;
    }

    /**
     * @param bool $installed
     *
     * @return Plugin
     */
    public function setInstalled(bool $installed = true) : Plugin {
        $this->installed = $installed;

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
     * @return Plugin
     */
    public function setActive(bool $active = true) : Plugin {
        $this->active = $active;

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
     * @return Plugin
     */
    public function setOrder(int $order) : Plugin {
        $this->order = $order;

        return $this;
    }

    /**
     * @return Middleware[]
     */
    public function getMiddlewares() {
        return $this->middlewares;
    }

    /**
     * @param Middleware[] $middlewares
     *
     * @return Plugin
     */
    public function setMiddlewares($middlewares) : Plugin {
        $this->middlewares = $middlewares;

        return $this;
    }

}
