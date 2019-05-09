<?php

namespace Test;

use Oforge\Engine\Modules\Core\Abstracts\AbstractBootstrap;
use Test\Models\Test\Test;
use Test\Services\TestService;

class Bootstrap extends AbstractBootstrap {
    public function __construct() {
        
        $this->middlewares = [
            "home2" => ["class" => \Test\Middleware\HomeMiddleware::class, "order" => 0]
        ];
        
        $this->models = [
            Test::class
        ];
        
        $this->dependencies = [
            \Test2\Bootstrap::class
        ];
        
        $this->services = [
            "test.test" => TestService::class
        ];
    }
    
    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Oforge\Engine\Modules\Core\Exceptions\ServiceNotFoundException
     */
    public function install() {
        /**
         * @var $testService TestService
         */
        $testService = Oforge()->Services()->get("test.test");
        $testService->addTestData();
    }
}
