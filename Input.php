<?php

namespace Starch\Core;

/**
 * Provides easy access to $_POST, $_GET, and $_SESSION
 * Returns null if no value found and parses strings if magic quotes is on
 * @package default
 */
class Input
{
    /**
     * Removes slashes if magic quotes is turned on
     * @param String $string String to parse
     * @return String The parsed string
     */
    private static function parse($string)
    {
        if (is_string($string) && get_magic_quotes_gpc()) {
            return stripslashes($string);
        } else {
            return $string;
        }
    }

    /**
     * Gets a variable from $_GET
     * @param String $name The requested property
     * @return Mixed The value
     */
    public static function get($name)
    {
        if (isset($_GET[$name])) {
            return self::parse($_GET[$name]);
        }

        return null;
    }

    /**
     * Gets a variable from $_POST
     * @param String $name The requested property
     * @return Mixed The value
     */
    public static function post($name)
    {
        if (isset($_POST[$name])) {
            return self::parse($_POST[$name]);
        }

        return null;
    }

    /**
     * Gets a variable from $_SESSION
     * @param String $name The requested property
     * @return Mixed The value
     */
    public static function session($name)
    {
        if (isset($_SESSION[$name])) {
            return $_SESSION[$name];
        }

        return null;
    }
}