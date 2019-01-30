<?php

namespace Oforge\Engine\Modules\Core\Helper;

/**
 * StringHelper
 *
 * @package Oforge\Engine\Modules\Core\Helper
 */
class StringHelper {

    /**
     * Prevent instance.
     */
    private function __construct() {
    }

    /**
     * Check if a string ends with a given value. If needle empty then true.
     *
     * @param string $haystack
     * @param string $needle
     *
     * @return bool
     */
    public static function endsWith(string $haystack, string $needle) {
        $length = strlen($needle);
        if ($length == 0) {
            return true;
        }

        return (substr($haystack, -$length) === $needle);
    }

    /**
     * Check if a string starts with a given value
     *
     * @param string $haystack
     * @param string $needle
     *
     * @return bool
     */
    public static function startsWith(string $haystack, string $needle) : bool {
        $length = strlen($needle);

        return (substr($haystack, 0, $length) === $needle);
    }

    /**
     * Check if a given value is inside a string.
     *
     * @param string $haystack
     * @param string $needle
     *
     * @return bool
     */
    public static function contains(string $haystack, string $needle) : bool {
        return (strpos($haystack, $needle) !== false);
    }

    /**
     * Check if a value is found before a given string / character.
     * If found return that value, otherwise return the haystack
     *
     * @param string $haystack The part where you search inside
     * @param string $needle The separator
     *
     * @return string
     */
    public static function substringBefore(string $haystack, string $needle) : string {
        if (StringHelper::contains($haystack, $needle)) {
            return explode($needle, $haystack)[0];
        }

        // TODO: Why u return haystack, if no value found?
        // TODO: Y U NO RETURN NULL °// ?
        return $haystack;
    }

}
