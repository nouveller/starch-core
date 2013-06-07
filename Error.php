<?php

namespace Starch\Core;

/**
 * Exception handler
 * @package default
 */
class Error
{
    // The exception
    private $e;

    /**
     * Constructor
     * @param Exception $e The exception
     * @return void
     */
    public function __construct($e)
    {
        $this->e = $e;

        if (ENV === 'development' || $e->important) {
            $this->display();
            exit;
        } else {
            Log::log_to(APP . 'error.log', array($e->getMessage(), $e->getTraceAsString()));
            Router::error(500);
        }
    }

    /**
     * Displays a formatted error message
     * @return void
     */
    private function display()
    {
    ?>
    <div style="margin: 3em auto; width: 80%; border: 1px solid black; padding: 1em; background: #719aa0; color: #e7e4d4; font-family: sans-serif;">
        <pre style="font-size: 1.2em; white-space: pre-wrap; margin-bottom: 2em"><?= $this->e->getMessage(); ?></pre>
        <h3>Stack Trace</h3>
        <pre style="white-space: pre-wrap"><?= $this->e->getTraceAsString(); ?></pre>
    </div>
    <?php
    }

/*
    public static function setup() {
        set_error_handler(array('\Starch\Core\Error', 'handle_error'), E_ALL);
        register_shutdown_function(array('\Starch\Core\Error', 'handle_fatal_error'));
    }

    public static function handle_error($number, $string, $file, $line, $fatal = false)
    {
        if (ENV !== 'development') {
            Log::log_to(APP . 'error.log', array($string, "$line: $file"));

            if ($fatal) {
                Router::error(500);
            }
        } else {
        ?>
            <div style="margin: 3em auto; width: 80%; border: 1px solid black; padding: 1em; background: #719aa0; color: #e7e4d4; font-family: sans-serif;">
                <pre style="font-size: 1.2em; white-space: pre-wrap; margin-bottom: 2em"><?= $string ?></pre>
                <ul>
                    <li>File: <?= $file ?></li>
                    <li>Line: <?= $line ?></li>
                    <li>Error Number: <?= $number ?></li>
                </ul>
            </div>
        <?php
        }
    }

    public static function handle_fatal_error()
    {
        $number   = E_CORE_ERROR;
        $string  = "Shutdown";
        $file = "Unknown File";
        $line = 0;

        $error = error_get_last();

        if($error !== null) {
            $number   = $error["type"];
            $string  = $error["message"];
            $file = $error["file"];
            $line = $error["line"];
        }

        self::handle_error($number, $string, $file, $line, true);
    }
*/
}