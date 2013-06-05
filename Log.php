<?php

namespace Starch\Core;

/**
 * Logs input to a file
 * @package default
 */
class Log
{
    /**
     * Logs arguments (Mixed / one per line) to app/log.txt
     * @return void
     */
    public static function log()
    {
        self::output(APP . 'log.txt', func_get_args());
    }

    /**
     * Logs each item in $strings (one per line) to a given file
     * @param String $file An absolute path to the output file (use PATH/APP constants to make it local to the Starch directory)
     * @param Array/Mixed $strings A single variable or array of variables to output
     * @return void
     */
    public static function log_to($file, $strings = array())
    {
        if (is_array($strings)) {
            self::output($file, $strings);
        } else if (is_string($strings)) {
            self::output($file, array($strings));
        }
    }

    /**
     * Outputs an array to a file - used by the two public methods
     * @param String $file The file path to output to
     * @param Array $strings An array of variables to output
     * @return void
     */
    private static function output($file, $strings)
    {
        $output = "\n\n" . date('d-m-Y h:i:s');

        foreach ($strings as $string) {
            if (is_bool($string)) {
                if ($string) {
                    $string = 'true';
                } else {
                    $string = 'false';
                }
            }

            $output .= "\n" . print_r($string, true);
        }

        file_put_contents($file, $output, FILE_APPEND);
    }
}