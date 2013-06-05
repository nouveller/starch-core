<?php

namespace Starch\Core;

/**
 * Provides an abstraction for strings
 * @package default
 */
class Content
{
    // The current content
    private $content = '';

    /**
     * Sets the content
     * @param String $string The content
     * @return void
     */
    public function set($string)
    {
        $this->content = $string;
    }

    /**
     * Appends a string
     * @param String $string Content to append
     * @return void
     */
    public function append($string)
    {
        $this->content .= $string;
    }

    /**
     * Gets the content
     * @return String The content
     */
    public function get()
    {
        return $this->content;
    }

    /**
     * Outputs the content
     * @return String The content
     */
    public function __toString()
    {
        return $this->get();
    }
}