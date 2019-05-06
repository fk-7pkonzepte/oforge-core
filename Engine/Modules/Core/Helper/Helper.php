<?php

namespace Oforge\Engine\Modules\Core\Helper;

use Oforge\Engine\Modules\Core\Abstracts\AbstractBootstrap;
use Oforge\Engine\Modules\Core\Exceptions\InvalidClassException;

/**
 * Class Helper
 *
 * @package Oforge\Engine\Modules\Core\Helper
 * @deprecated
 */
class Helper {
    protected static $omitFolders = [".", "..", "vendor", "var"];

    /**
     * get the instance of a module/plugin based on the bootstrap file
     *
     * @param $pluginName
     *
     * @return mixed
     * @throws InvalidClassException
     */
    public static function getBootstrapInstance($pluginName) : AbstractBootstrap {
        $className = $pluginName . "\\Bootstrap";

        if (is_subclass_of($className, AbstractBootstrap::class)) {
            return new $className();
        }
        throw new InvalidClassException($className, AbstractBootstrap::class);
    }

    /**
     * Get some metadata from a file.
     * If the file is a psr4 conform class, the function finds the namespace and classname and returns them as an array.
     * Otherwise null.
     *
     * @param string $fileName
     *
     * @return array|null
     */
    public static function getFileMeta(string $fileName) {
        $file      = fopen($fileName, 'r');
        $class     = '';
        $namespace = '';
        $buffer    = '';
        $i         = 0;
        $fileMeta  = [];

        while (!$class) {
            if (feof($file)) {
                break;
            }

            $buffer .= fread($file, 512);
            $tokens = token_get_all($buffer);

            if (strpos($buffer, '{') === false) {
                continue;
            }

            for (; $i < count($tokens); $i++) {
                if ($tokens[$i][0] === T_NAMESPACE) {
                    for ($j = $i + 1; $j < count($tokens); $j++) {
                        if ($tokens[$j][0] === T_STRING) {
                            $namespace .= '\\' . $tokens[$j][1];
                        } elseif ($tokens[$j] === '{' || $tokens[$j] === ';') {
                            break;
                        }
                    }
                }

                if ($tokens[$i][0] === T_CLASS) {
                    for ($j = $i + 1; $j < count($tokens); $j++) {
                        if ($tokens[$j] === '{') {
                            $class = $tokens[$i + 2][1];
                        }
                    }
                }
            }
        }

        if (StringHelper::startsWith($namespace, "\\")) {
            $namespace = ltrim($namespace, "\\");
        }

        if (strlen($class) > 0) {
            $fileMeta['class_name'] = $class;
        }

        if (strlen($namespace) > 0) {
            $fileMeta['namespace'] = $namespace;
        }

        if (sizeof($fileMeta) < 2) {
            return null;
        }

        return $fileMeta;
    }

}
