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

        if (ENV === 'development') {
            $this->display();
            exit;
        } else {
            Log::log($e->getMessage(), $e->getTraceAsString());
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
}