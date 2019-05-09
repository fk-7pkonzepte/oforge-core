<?php

namespace Oforge\Engine\Modules\Core\Models\Endpoint;

use Doctrine\ORM\Mapping as ORM;
use Oforge\Engine\Modules\Core\Abstracts\AbstractModel;
use Oforge\Engine\Modules\Core\Helper\Statics;

/**
 * @ORM\Entity
 * @ORM\Table(name="oforge_core_endpoints")
 */
class Endpoint extends AbstractModel {
    /**
     * @var int $id
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;
    /**
     * @var bool $active
     * @ORM\Column(name="active", type="boolean", nullable=false, options={"default":false})
     */
    private $active = false;
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
     * @var string $controllerClass
     * @ORM\Column(name="controller_class", type="string", nullable=false)
     */
    private $controllerClass;
    /**
     * @var string $controllerMethod
     * @ORM\Column(name="controller_method", type="string", nullable=false)
     */
    private $controllerMethod;
    /**
     * @var string $httpMethod
     * @ORM\Column(name="http", type="string", nullable=false, options={"default":EndpointMethod::ANY})
     */
    private $httpMethod = EndpointMethod::ANY;
    /**
     * @var string $assetScope
     * @ORM\Column(name="asset_scope", type="string", nullable=false, options={"default":"frontend"})
     */
    private $assetScope = 'frontend';
    /**
     * @var int $order
     * @ORM\Column(name="orderby", type="integer", nullable=false, options={"default":Statics::DEFAULT_ORDER})
     */
    private $order = Statics::DEFAULT_ORDER;

    /**
     * @return int
     */
    public function getId() : int {
        return $this->id;
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
     * @return Endpoint
     */
    public function setActive(bool $active) : Endpoint {
        $this->active = $active;

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
     * @return Endpoint
     */
    public function setName(string $name) : Endpoint {
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
     * @return Endpoint
     */
    public function setPath(string $path) : Endpoint {
        $this->path = $path;

        return $this;
    }

    /**
     * @return string
     */
    public function getControllerClass() : string {
        return $this->controllerClass;
    }

    /**
     * @param string $controllerClass
     */
    protected function setControllerClass(string $controllerClass) : void {
        $this->controllerClass = $controllerClass;
    }

    /**
     * @return string
     */
    public function getControllerMethod() : string {
        return $this->controllerMethod;
    }

    /**
     * @param string $controllerMethod
     */
    protected function setControllerMethod(string $controllerMethod) : void {
        $this->controllerMethod = $controllerMethod;
    }

    /**
     * @return string
     */
    public function getHttpMethod() : string {
        return $this->httpMethod;
    }

    /**
     * @param string $httpMethod
     *
     * @return Endpoint
     */
    public function setHttpMethod(string $httpMethod) : Endpoint {
        $this->httpMethod = $httpMethod;

        return $this;
    }

    /**
     * @return string
     */
    public function getAssetScope() {
        return $this->assetScope;
    }

    /**
     * @param string $assetScope
     *
     * @return Endpoint
     */
    public function setAssetScope(string $assetScope) : Endpoint {
        $this->assetScope = $assetScope;

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
     */
    public function setOrder(int $order) : void {
        $this->order = $order;
    }

}
