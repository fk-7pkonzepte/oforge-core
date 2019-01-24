<?php

namespace Oforge\Engine\Modules\Core;

use Slim\App as SlimApp;
use Slim\Http\Response;

/**
 * Class App
 * An extension of the SlimApp Container.
 * See https://www.slimframework.com/
 *
 * @package Oforge\Engine\Modules\Core
 */
class App extends SlimApp {
    /** @var App $instance */
    protected static $instance = null;

    /**
     * App constructor.
     * Defines the slim default error behaviour.
     */
    public function __construct() {
        parent::__construct();
        $container = $this->getContainer();

        $container['errorHandler'] = function ($container) {
            return function ($request, $response, $exception) use ($container) {
                /** @var Response $response */
                return $response->withStatus(500)->withHeader('Content-Type', 'text/html')->write($exception);
            };
        };

        $container['phpErrorHandler'] = function ($container) {
            return function ($request, $response, $exception) use ($container) {
                /** @var Response $response */
                return $response->withStatus(500)->withHeader('Content-Type', 'text/html')->write($exception);
            };
        };

        $container['cookie'] = function ($container) {
            return new \Slim\Http\Cookies();
        };
    }

    /**
     * @return App
     */
    public static function getInstance() : App {
        if (!isset(self::$instance)) {
            self::$instance = new App();
        }

        return self::$instance;
    }

}
