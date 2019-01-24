<?php

namespace Oforge\Engine\Modules\Core\Forge;

use Slim\App as SlimApp;
use Slim\Http\Response;

/**
 * Class App
 * An extension of the SlimApp Container.
 * See https://www.slimframework.com/
 *
 * @package Oforge\Engine\Modules\Core
 */
class ForgeSlimApp extends SlimApp {
    /** @var ForgeSlimApp $instance */
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
     * @return ForgeSlimApp
     */
    public static function getInstance() : ForgeSlimApp {
        if (!isset(self::$instance)) {
            self::$instance = new ForgeSlimApp();
        }

        return self::$instance;
    }

}
