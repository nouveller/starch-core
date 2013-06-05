<?php

namespace Starch\Core;

class Template
{
    private $values = array();

    public function set($name, $value)
    {
        $this->values[$name] = $value;
    }

    public function get($name)
    {
        if (array_key_exists($name, $this->values)) {
            return $this->values[$name];
        }

        return null;
    }

    public function get_values()
    {
        return $this->values;
    }
}