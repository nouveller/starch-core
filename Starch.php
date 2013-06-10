<?php

namespace Starch\Core;

/**
 * Handles the final loading stages
 * Kept in Starch\Core so that it can be easily edited
 * @package default
 */
class Starch {
    /**
     * Loads in the necessary files
     * @return void
     */
    public static function go()
    {
        define('ENV', Config::get('environment') ? : 'production');
        Error::setup();

        try {
            Config::setup();
            PostType::load();
            Router::load();
            include_once APP . 'theme.php';
        } catch (\Exception $e) {
            new Error($e);
        }
    }
}