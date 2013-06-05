<?php

namespace Starch\Core;

/**
 * Handles the rendering and display of templates
 * @package default
 */
class View
{
    /**
     * Returns a string with the content
     * @param String $path Path to the template file (within the 'views' directory)
     * @param Array $vars An array of variables to pass into the template
     * @return String The parsed content
     */
    public static function render($path, $vars = array())
    {
        /* Get page content */
        $path = APP . 'views/' . $path . '.php';

        if (!file_exists($path)) {
            throw new Exception('Template does not exist: ' . $path);
            return false;
        }

        // Start output buffering
        ob_start();

        // Extract the passed in variables so that they're available
        extract($vars);

        // Include the template (in same scope, so variables will be available)
        include($path);

        // Get the contents of the output buffer
        $content = ob_get_contents();

        // Clear the output buffer
        ob_end_clean();

        return $content;
    }

    /**
     * Outputs the rendered template directly
     * @param String $path Path to the template file (within the 'views' directory)
     * @param Array $vars An array of variables to pass into the template
     * @return void
     */
    public static function display($path, $vars = array())
    {
        echo self::render($path, $vars);
    }
}