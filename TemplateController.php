<?php

namespace Starch\Core;

class TemplateController extends Controller
{
    protected $template;
    protected $path = 'template';
    protected $blank_canvas = false;

    public function before()
    {
        parent::before();

        $this->template = new Template();
        $this->template->set('class', implode(' ', get_body_class()));
    }

    public function display()
    {
        if ($this->blank_canvas) {
            parent::display();
        } else {
            $pass = array_merge($this->template->get_values(), array('content' => $this->content->get()));
            View::display($this->path, $pass);
        }
    }

    protected function blank_canvas()
    {
        $this->blank_canvas = true;
    }
}