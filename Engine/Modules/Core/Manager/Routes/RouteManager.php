<?php

namespace Oforge\Engine\Modules\Core\Manager\Routes;

use Oforge\Engine\Modules\Core\Helper\StringHelper;
use Oforge\Engine\Modules\Core\Middleware\DebugModeMiddleware;
use Oforge\Engine\Modules\Core\Models\Endpoints\Endpoint;
use Oforge\Engine\Modules\Core\Models\Plugin\Middleware;
use Oforge\Engine\Modules\Core\Services\MiddlewareService;
use Oforge\Engine\Modules\Session\Middleware\SessionMiddleware;

class RouteManager {
    const SLIM_HTTP_METHODS = [ 'any', 'get', 'post', 'put', 'patch', 'delete', 'options' ];
    protected static $instance = null;
    
    public static function getInstance() {
        if ( ! isset( self::$instance ) ) {
            self::$instance = new RouteManager();
        }
        
        return self::$instance;
    }

    /**
     * Make all routes, that come from the database, work
     *
     * @throws \Oforge\Engine\Modules\Core\Exceptions\ServiceNotFoundException
     * @throws \Doctrine\ORM\ORMException
     */
    public function init() {
        $entityManager = Oforge()->DB()->getManager();
        $repository    = $entityManager->getRepository( Endpoint::class );

        /** @var MiddlewareService $middlewareService */
        $middlewareService = Oforge()->Services()->get( 'middleware' );

        /** @var Endpoint[] $endpoints */
        $endpoints = $repository->findBy( array("active" => 1 ), array( 'order' => 'ASC' ) );

        $activeMiddlewareNames = $middlewareService->getAllDistinctActiveNames();

        $container = Oforge()->App()->getContainer();

        foreach ( $endpoints as $endpoint ) {
            
            if ( ! in_array( $endpoint->getHttpMethod(), self::SLIM_HTTP_METHODS ) ) {
                continue;
            }

            $className = StringHelper::substringBefore( $endpoint->getController(), ':' );
            if ( ! $container->has( $className ) ) {
                $container[ $className ] = function () use ( $className ) {
                    return new $className;
                };
            }

            $httpMethod = $endpoint->getHttpMethod();
            /**
             * @var \Slim\Interfaces\RouteInterface $slimRoute
             */
            $slimRoute = Oforge()->App()->$httpMethod(#
                $endpoint->getPath(), $endpoint->getController()#
            )->setName( $endpoint->getName() );
            
            /**
             * @var $activeMiddlewares Middleware[]
             */
            $activeMiddlewares = [];
            //$activeMiddlewares = $middlewareService->getActive( $endpoint->getName() );
            //$slimRoute->add( new MiddlewarePluginManager( $activeMiddlewares ) );
    
            $endpointName = $endpoint->getName();

            foreach($activeMiddlewareNames as $middlewareName) {
                $pattern = "/^" . $middlewareName . "/";

                if ( preg_match( $pattern, $endpointName ) ) {
                    $activeMiddlewares = $middlewareService->getActive( $middlewareName );
                    $slimRoute->add( new MiddlewarePluginManager( $activeMiddlewares ) );
                }
            }

            $activeMiddlewares = $middlewareService->getActive( '*' );
            $slimRoute->add( new MiddlewarePluginManager( $activeMiddlewares ) );
            $slimRoute->add( new RenderMiddleware() );
            $slimRoute->add( new RouteMiddleware( $endpoint ) );
            $slimRoute->add( new DebugModeMiddleware);
            $slimRoute->add( new SessionMiddleware());
        }
    }
}
