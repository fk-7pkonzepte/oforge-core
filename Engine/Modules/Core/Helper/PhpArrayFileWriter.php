<?php

namespace Oforge\Engine\Modules\Core\Helper;

/**
 * PhpArrayFileWriter
 * Writes a php array return file.
 *
 * @package Oforge\Engine\Modules\Core\Helper
 */
class PhpArrayFileWriter {

    /**
     * @param string $filepath
     * @param array $data
     *
     * @return bool True on success.
     */
    public static function write(string $filepath, array $data) {
        $text = '<?php return [';
        self::recrusive($text, $data, 1);
        $text .= PHP_EOL . '];';

        return false !== file_put_contents($filepath, $text, LOCK_EX);
    }

    /**
     * @param string $text
     * @param array $data
     */
    private static function recrusive(string &$text, array $data, $indent) {
        $isAssoc = ArrayHelper::isAssoc($data);
        foreach ($data as $key => $value) {
            $text .= PHP_EOL . str_repeat(' ', 4 * $indent);
            if ($isAssoc) {
                if (is_int($key)) {
                    $text .= "$key => ";
                } else {
                    $text .= "'$key' => ";
                }
            }
            if (is_array($value)) {
                $text .= '[';
                self::recrusive($text, $value, $indent + 1);
                $text .= PHP_EOL . str_repeat(' ', 4 * $indent) . ']';
            } else {
                $text .= var_export($value, true);
            }
            $text .= ',';
            // $text .= PHP_EOL . str_repeat(' ', 4 * $indent);
        }
    }

}
