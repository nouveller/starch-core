<?php

namespace Starch\Core;

class Controller
{
    protected $post;
    protected $content;
    protected $_error = false;

    public function __construct(&$post = null)
    {
        $this->post = $post;
        $this->content = new Content();
    }

    public function before() {}

    public function after() {}

    public function display()
    {
        if (!isset($this->content)) {
            throw new Exception('Content not set');
        }

        echo $this->content;
    }

    public function error($type)
    {
        $this->_error = true;
        Router::error($type);
    }

    public function ready()
    {
        return !$this->_error;
    }

    public function page_number()
    {
        global $paged;
        return $paged ? $paged : 1;
    }

    public function loop($func)
    {
        if (have_posts()) {
            while (have_posts()) {
                the_post();
                global $post;
                $class = '\Starch\Model\\' . ucwords($post->post_type);
                $p = new $class($post);

                $func($p);
            }
        }
    }
}
