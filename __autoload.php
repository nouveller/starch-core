<?php

namespace Starch\Core
{
    /**
     * Handles auto-loading of classes
     * @package default
     */
    class AutoLoad
    {
        // The classname
        private $classname;

        // The file path
        private $path;

        /**
         * Sets the class name and trims the initial back-slash
         * @param String $classname The fully qualified class name
         * @return void
         */
        public function __construct($classname)
        {
            $classname = ltrim($classname, '\\');
            $this->classname = $classname;
        }

        /**
         * Gets the correct file path for the given class name
         * @return String/void Returns the path or null if none is found
         */
        public function get_path()
        {
            if ($this->is_starch()) {
                $this->get_starch_dir();
            } else {
                $this->get_vendor_dir();
            }

            if (file_exists($this->path)) {
                return $this->path;
            } else {
                return null;
            }
        }

        /**
         * Detects whether a class name is part of Starch
         * @return Boolean
         */
        private function is_starch()
        {
            if (strpos($this->classname, 'Starch\\') === 0) {
                return true;
            } else {
                return false;
            }
        }

        /**
         * Creates a Starch path
         * @return void
         */
        private function get_starch_dir()
        {
            $classname = ltrim($this->classname, 'Starch\\');
            $this->make_path('classes/' . str_replace('\\', '/', $classname));
        }

        /**
         * Creates a vendor path
         * @return void
         */
        private function get_vendor_dir()
        {
            $this->make_path('vendor/' . str_replace('\\', '/', $this->classname));
        }

        /**
         * Sets the $this->path variable to the correct value
         * @param String $path The class's path
         * @return void
         */
        private function make_path($path) {
            $this->path = APP . $path . '.php';
        }
    }
}

namespace
{
    /**
     * Setup Class Auto-Loading
     */
    spl_autoload_register(function($name)
        {
            $loader = new Starch\Core\AutoLoad($name);
            $path = $loader->get_path();

            if ($path) {
                include_once $path;
            }
        }
    );

    $composer = APP . 'vendor/autoload.php';

    if (file_exists($composer)) {
        include_once($composer);
    }
}