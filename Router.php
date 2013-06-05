<?php

namespace Starch\Core;

/**
 * Handles the loading of the correct Controller and Model objects as well as general redirects
 * @package default
 */
class Router
{
    // Array of routes
    private static $routes = array();

    // Array of post types
    private static $types = array();

    // Maximum number of variables used in a redirect
    private static $maxVars = 0;

    // Whether the rewrite rules need flushing
    private static $flush = false;

    /**
     * Loads in custom routes from the config/routes file
     * @return void
     */
    public static function load()
    {
        $routes = Config::get('routes', true) ? : array();

        krsort($routes, SORT_STRING);

        $routesHash = sha1(serialize($routes));

        if (get_option('routes_hash') !== $routesHash) {
            self::$flush = true;
            update_option('routes_hash', $routesHash);
        }

        foreach ($routes as $url => $options) {
            self::add_route($url, $options);
        }

        add_action('init', function () {
            \Starch\Core\Router::setup_routes();
        });
    }

    /**
     * Adds a new post type
     * @param String $post_type The post type (generally a lowercase version of the name)
     * @param String $name The post type name
     * @return void
     */
    public static function add_type($post_type, $name)
    {
        self::$types[$post_type] = $name;
    }

    /**
     * Adds a new route
     * @param String $url The URL to redirect
     * @param Array $options Redirect options (controller, method, variables)
     * @return type
     */
    protected static function add_route($url, $options)
    {
        if (count($options) < 2) {
            throw new \Exception('Routes require at least two arguments ($controller, $method, $vars)');
        }

        $controller = $options[0];
        $method = $options[1];
        $vars = isset($options[2]) ? $options[2] : 0;

        self::$routes[$url] = array(
            'controller' => $controller,
            'method' => $method,
            'vars' => $vars
        );
    }

    /**
     * Sets up the WordPress rewrite rules
     * @return void
     */
    public static function setup_routes()
    {
        if (!empty(self::$routes)) {
            add_rewrite_tag('%rewritten%', '([^&]+)');

            foreach (self::$routes as $url => $data) {
                if ($data['vars'] < 1) {
                    add_rewrite_rule('^' . $url . '/?','index.php?rewritten=' . $url, 'top');
                } else {
                    $args = '';
                    $extra = '';

                    for ($i = 1; $i < $data['vars'] + 1; $i++) {
                        if ($i > self::$maxVars) {
                            add_rewrite_tag('%argument_' . $i . '%', '([^&]+)');
                            self::$maxVars = $i;
                        }

                        $args .= '&argument_' . $i . '=$matches[' . $i . ']';
                        $extra .= '/([^/]+)';
                    }

                    $original = '^' . $url . $extra . '/?';
                    $rewrite = 'index.php?rewritten=' . $url . $args;

                    add_rewrite_rule($original, $rewrite, 'top');
                }
            }
        }

        if (self::$flush) {
            self::flush();
            self::$flush = false;
        }
    }

    /**
     * Handles the routing from index.php
     * @param Object Reference &$post The WordPress $post object
     * @return void
     */
    public static function route(&$post)
    {
        // The controller to call
        $controller = '';

        // The method on the controller
        $action = '';

        // Any variables to pass in (used with routes)
        $pass = array();

        // Has the URL been routed?
        $rewrite = get_query_var('rewritten');

        // If the URL has been routed
        if ($rewrite && array_key_exists($rewrite, self::$routes)) {
            $data = self::$routes[$rewrite];

            $controller = '\Starch\Controller\\' . $data['controller'];
            $action = $data['method'];

            if ($data['vars']) {
                for ($i = 1; $i < $data['vars'] + 1; $i++) {
                    $name = 'argument_' . $i;
                    $pass[] = get_query_var($name);
                }
            }

        // If the URL causes a 404 error
        } else if (is_404()) {
            $controller = '\Starch\Controller\Main';
            $action = '404';

        // If it's the index page
        } else if (is_front_page()) {
            $is_page = (get_option('show_on_front') === 'page') ? true : false;

            if ($is_page) {
                // Get the ID of the front page
                $page_id = get_option('page_on_front');

                // Load the front page
                $post = get_post($page_id);

                $controller = '\Starch\Controller\Page';
                $action = 'index';
            } else {
                $controller = '\Starch\Controller\Post';
                $action = 'archive';
            }

        // If it's the search page
        } else if (is_search()) {
            $controller = '\Starch\Controller\Main';
            $action = 'search';

        // If it's a page
        } else if (is_page()) {
            $controller = '\Starch\Controller\Page';
            $action = 'single';

        // If it's a post
        } else {
            // On archive pages the $post variable isn't set, so have to get post_type elsewhere
            $post_type = get_query_var('post_type') ? get_query_var('post_type') : $post->post_type;
            $controller = '\Starch\Controller\\' . static::$types[$post_type];

            if (!class_exists($controller)) {
                if (ENV === 'development') {
                    throw new \Exception("{$controller} does not exist");
                }
            }

            if (have_posts()) {
                if (is_archive()) {
                    $action = 'archive';
                } else {
                    $action = 'single';
                }
            } else {
                // Controllers can have post type specific nothing found handlers
                if (method_exists($controller, 'action_nothing_found')) {
                    $action = 'nothing_found';
                } else {
                    $controller = '\Starch\Controller\Main';
                    $action = 'nothing_found';
                }
            }
        }

        // Check that the class and method exist, if not respond with a 500 error
        if (!class_exists($controller) || !method_exists($controller, 'action_' . $action)) {
            if (ENV === 'development') {
                throw new \Exception("{$controller}::{$action} does not exist");
            } else {
                self::error(500);
                return;
            }
        }

        if ($post) {
            $page = new $controller(PostType::create($post));
        } else {
            $page = new $controller();
        }

        // Called methods are prepended with 'action_' to keep naming clean
        $action = 'action_' . $action;

        // All Controllers have a before function, for initial setup
        $page->before();

        // Run the Controller and method, passing any variables (only from rewrites)
        call_user_func_array(array($page, $action), $pass);

        // All Controllers have a before function, for tidying up
        $page->after();

        // If an error occurs on a page it shouldn't be displayed (as something else will need to be)
        if ($page->ready()) {
            $page->display();
        }
    }

    /**
     * Loads an error page with the appropriate response code
     * @param Integer $type The response code (generally 404 or 500)
     * @return void
     */
    public static function error($type = 404)
    {
        header(':', true, $type);

        $page = new \Starch\Controller\Main();
        $page->before();

        if ($type === 404) {
            $page->action_404();
        } else {
            $page->action_error($type);
        }

        $page->after();
        $page->display();
    }

    /**
     * Flush the WordPress rewrite rules
     * @return void
     */
    public static function flush()
    {
        global $wp_rewrite;
        $wp_rewrite->flush_rules();

        flush_rewrite_rules();
        Log::log('Flushed Rewrite Rules');

        self::reload();
    }

    /**
     * Redirect to a specified URL
     * @param String $url The url to redirect to (can be relative or absolute)
     * @return void
     */
    public static function redirect($url)
    {
        header('Location: ' . $url, true, 302);
        exit;
    }

    /**
     * Reloads the current page
     * @return void
     */
    public static function reload()
    {
        $redirect= $_SERVER['REQUEST_URI'];
        header("Location:$redirect");
        exit;
    }

    /**
     * Loads the previous page if there was one
     * @return void
     */
    public static function previous()
    {
        $prev = $_SERVER['HTTP_REFERER'];

        if ($prev) {
            header('Location: ' . $prev, true);
            exit;
        }

        return false;
    }

    /**
     * Loads the http:// version of a page
     * @return void
     */
    public static function http()
    {
        if (!empty($_SERVER['HTTPS'])) {
            $redirect= "http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
            header("Location:$redirect", true, 301);
            exit;
        }
    }

    /**
     * Loads the https:// version of a page
     * @return void
     */
    public static function https()
    {
        if (empty($_SERVER['HTTPS'])) {
            $redirect= "https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
            header("Location:$redirect", true, 301);
            exit;
        }
    }
}