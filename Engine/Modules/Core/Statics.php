<?php

namespace Oforge\Engine\Modules\Core;

/**
 * Class Statics
 *
 * @package Oforge\Engine\Modules\Core
 */
class Statics {
    /**
     * Default order value for all order properties.
     */
    public const DEFAULT_ORDER = 1337;
    /**
     * Name of assets directory.
     */
    public const ASSETS_DIR_NAME = '__assets';
    /**
     * Name of root plugins directory.
     */
    public const BOOTSTRAP_DIR_NAME = 'Bootstrap';
    /**
     * Name of imports directory.
     */
    public const IMPORTS_DIR_NAME = 'imports';
    /**
     * Name of views directory.
     */
    public const THEMES_DIR_NAME = "Themes";
    /**
     * Name of root plugins directory.
     */
    public const VIEW_DIR_NAME = "Views";
    /**
     * Name of assets scss root file.
     */
    public const ASSETS_ALL_SCSS_NAME = 'all.scss';
    /**
     * Name of assets scss directory.
     */
    public const ASSETS_SCSS_DIR_NAME = 'scss';
    /**
     * Name of assets js directory.
     */
    public const ASSETS_JS_DIR_NAME = 'js';
    /**
     * Name of assets js import file.
     */
    public const ASSETS_IMPORT_JS_FILE_NAME = 'imports.cfg';
    /**
     * Relative path: /Engine/
     */
    public const ENGINE_DIR = DIRECTORY_SEPARATOR . 'Engine';
    /**
     * Name of root plugins directory.
     */
    public const PLUGINS_DIR = DIRECTORY_SEPARATOR . 'Plugins';
    /**
     * Relative path: /Themes/
     */
    public const THEMES_DIR = DIRECTORY_SEPARATOR . self::THEMES_DIR_NAME;
    /**
     * Relative path: /var/
     */
    public const VAR_DIR = DIRECTORY_SEPARATOR . 'var';
    /**
     * Relative path: /var/public/
     */
    public const PUBLIC_DIR = Statics::VAR_DIR . DIRECTORY_SEPARATOR . 'public';
    /**
     * Relative path: /var/public/theme/
     */
    public const THEME_CACHE_DIR = Statics::PUBLIC_DIR . DIRECTORY_SEPARATOR . 'theme';
    /**
     * Relative path: /var/public/theme/
     */
    public const ASSET_CACHE_DIR = Statics::PUBLIC_DIR . DIRECTORY_SEPARATOR . Statics::ASSETS_DIR_NAME;
    /**
     * Relative path: /var/cache/
     */
    public const CACHE_DIR = Statics::VAR_DIR . DIRECTORY_SEPARATOR . 'cache';
    /**
     * Relative path: /var/cache/db/
     */
    public const DB_CACHE_DIR = Statics::CACHE_DIR . DIRECTORY_SEPARATOR . 'db';
    /**
     * Relative path /var/cache/db/db.cache
     */
    public const DB_CACHE_FILE = Statics::DB_CACHE_DIR . DIRECTORY_SEPARATOR . 'db.cache';
    /**
     * Relative path: /var/imports/
     */
    public const IMPORTS_DIR = Statics::VAR_DIR . DIRECTORY_SEPARATOR . self::IMPORTS_DIR_NAME;
    /**
     * Relative path: /var/logs/
     */
    public const LOGS_DIR = Statics::VAR_DIR . DIRECTORY_SEPARATOR . 'logs';

}
