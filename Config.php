<?php

namespace Starch\Core;

/**
 * Provides access to the configuration files
 * @package default
 */
class Config
{
    // Previously loaded configuration settings
    private static $config = array();

    /**
     * Loads the appropriate config files into memory
     * @param String $key The config file
     * @return void
     */
    private static function load($key)
    {
        $path = APP . 'config/';
        $file = $key . '.php';

        if (file_exists($path . $file)) {
            $config = include_once($path . $file);

            if (is_array($config)) {
                self::$config[$key] = $config;
                return;
            }
        }

        self::$config[$key] = null;
    }

    /**
      * Get a congifuration settings
      * @param String $key Either a value name (if in general settings) or a file name
      * @param String $name A value name (if file name is provided)
      * @return Mixed The value of requested configuration
      */
    public static function get($key, $name = false)
    {
        if (!$name) {
            $name = $key;
            $key = 'general';
        }

        // If this file hasn't already been loaded
        if (!array_key_exists($key, self::$config)) {
            self::load($key);
        }

        if (array_key_exists($key, self::$config)) {
            if ($name === true) {
                return self::$config[$key];
            } else if (array_key_exists($name, self::$config[$key])) {
                return self::$config[$key][$name];
            }
        }

        return null;
    }

    /**
     * Setup various configuration options
     * @return void
     */
    public static function setup() {
        add_action('after_setup_theme', function () {
            // Add excerpts to pages
            if (\Starch\Core\Config::get('page_excerpts')) {
                add_post_type_support('page', 'excerpt');
            }

            // Featured image
            if (\Starch\Core\Config::get('post_thumbnails')) {
                add_theme_support('post-thumbnails');
            }

            // Add Featured Image support
            $thumbnails = \Starch\Core\Config::get('thumbnails');

            if ($thumbnails) {
                foreach ($thumbnails as $thumbnail) {
                    call_user_func_array('add_image_size', $thumbnail);
                }
            }

            // Deregister jQuery - add local version in template controller
            if (\Starch\Core\Config::get('deregister_jquery')) {
                add_action('init', function() {
                    if (!is_admin()) {
                        wp_deregister_script('jquery');
                    }
                });
            }

            // Load admin.js
            $admin_scripts = \Starch\Core\Config::get('admin_scripts');

            if ($admin_scripts) {
                add_action('admin_enqueue_scripts', function () use ($admin_scripts) {
                    foreach ($admin_scripts as $script) {
                        wp_enqueue_script($script, ASSETS . 'admin/' . $script . 'js');
                    }
                });
            }

            // Admin stylesheets
            $admin_css = \Starch\Core\Config::get('admin_css');

            if ($admin_css) {
                $add_css = function () use ($admin_css) {
                    foreach ($admin_css as $css) {
                        echo '<link rel="stylesheet" href="' . ASSETS . 'admin/' . $css . '.css">';
                    }
                };

                add_action('admin_head', $add_css);
                add_action('login_head', $add_css);
            }

            // Hide the slug/url box on edit pages
            if (\Starch\Core\Config::get('hide_slug_box')) {
                add_action('admin_head', function () {
                    if (!current_user_can('administrator')) {
                        echo '<style>#edit-slug-box { display: none; }</style>';
                    }
                });
            }

            // Don't show admin bar for non-admins
            if (\Starch\Core\Config::get('hide_admin_bar')) {
                if (!current_user_can('edit_posts')) {
                    add_filter('show_admin_bar', '__return_false');
                }
            }

            // Don't allow non-admins to go to /wp-admin/ pages
            $redirect_wp_admin = \Starch\Core\Config::get('redirec_wp_admin');

            if ($redirect_wp_admin) {
                add_action('admin_init', function () {
                    if (!current_user_can('edit_posts')) {
                        Router::redirect($redirect_wp_admin);
                    }
                });
            }

            // Hide various bits and bobs from non-admin users
            $hide = \Starch\Core\Config::get('hide');

            if ($hide && !empty($hide)) {
                // Hides admin menu options from non-Admin users
                add_action('admin_menu', function () use ($hide) {
                    if (!current_user_can('administrator')) {
                        // Posts
                        if (in_array('posts', $hide)) {
                            remove_menu_page('edit.php');
                        }

                        // Settings
                        if (in_array('settings', $hide)) {
                            remove_menu_page('options-general.php');
                        }

                        // Tools
                        if (in_array('tools', $hide)) {
                            remove_menu_page('tools.php');
                        }

                        // Profile
                        if (in_array('profile', $hide)) {
                            remove_menu_page('profile.php');
                        }

                        // Plugins
                        if (in_array('plugins', $hide)) {
                            remove_menu_page('plugins.php');
                        }

                        // Media
                        if (in_array('media', $hide)) {
                            remove_menu_page('upload.php');
                        }

                        // Comments
                        if (in_array('comments', $hide)) {
                            remove_menu_page('edit-comments.php');
                        }

                        // Links
                        if (in_array('links', $hide)) {
                            remove_menu_page('link-manager.php');
                        }

                        // Appearance
                        if (in_array('appearance', $hide)) {
                            remove_menu_page('themes.php');
                        }

                        // Users
                        if (in_array('users', $hide)) {
                            remove_menu_page('users.php');
                        }
                    }
                });

                // Hide options from Admin bar
                add_action('wp_before_admin_bar_render', function () use ($hide) {
                    if (!current_user_can('administrator')) {
                        global $wp_admin_bar;

                        // New Post
                        if (in_array('posts', $hide)) {
                            $wp_admin_bar->remove_menu('new-post');
                        }

                        // Profile
                        if (in_array('profile', $hide)) {
                            $wp_admin_bar->remove_menu('edit-profile');
                        }

                        // Comments
                        if (in_array('comments', $hide)) {
                            $wp_admin_bar->remove_menu('comments');
                        }

                        // New Media
                        if (in_array('media', $hide)) {
                            $wp_admin_bar->remove_menu('new-media');
                        }

                        // New Link
                        if (in_array('links', $hide)) {
                            $wp_admin_bar->remove_menu('new-link');
                        }

                        // Appearance
                        if (in_array('appearance', $hide)) {
                            $wp_admin_bar->remove_menu('appearance');
                            $wp_admin_bar->remove_menu('new-theme');
                        }

                        // New User
                        if (in_array('users', $hide)) {
                            $wp_admin_bar->remove_menu('new-user');
                        }

                        // New Plugin
                        if (in_array('plugins', $hide)) {
                            $wp_admin_bar->remove_menu('new-plugin');
                        }
                    }
                });
            }
        });
    }
}