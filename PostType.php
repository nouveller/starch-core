<?php

namespace Starch\Core;

/**
 * Provides easy interaction with WordPress post types
 * Post types models should inherit from Post
 * @package default
 */
class PostType {
    // Reserved property names that can't be used for meta boxes
    private static $reserved_properties = array(
        'id',
        'title',
        'link',
        'type',
        'date',
        'modified',
        'slug',
        'edit_link',
        'excerpt',
        'unfiltered_content',
        'content',
        'attachments',
        'featured_image',
        'next',
        'previous',
        'is_last',
        'is_first'
    );

    // Built in post types
    private static $built_in = array('Post', 'Page', 'Attachment');
    protected static $__built_in = false;

    // Keeps track of what type uses what model
    private static $models = array();

    /**
     * Load in custom post types
     * @return void
     */
    public static function load()
    {
        // Load post types from general config
        $types = Config::get('post_types') ? : array();
        $types = array_merge($types, self::$built_in);

        // Register each post type
        foreach ($types as $type) {
            $class = '\Starch\Model\\' . $type;

            if (!class_exists($class)) {
                throw new \Exception("$class could not be found");
            }

            $class::register();

            self::$models[$class::$post_type] = $class;
            Router::add_type($class::$post_type, $type);
        }

        // Create a hash of the post types
        $types_hash = sha1(serialize(self::$models));

        // Check if post types have changed
        if (get_option('types_hash') !== $types_hash) {
            update_option('types_hash', $types_hash);
            Router::flush();
        }
    }

    /**
     * Register custom post types
     * @param String $type The post type name
     * @param String $class The namespaced class
     * @return void
     */
    protected static final function register()
    {
        if (static::$__built_in) {
            return;
        }

        $properties = array(
            'labels' => array(
                'name' => static::$post_name['plural'],
                'singular_name' => static::$post_name['singular'],
                'add_new' => 'Add ' . static::$post_name['singular'],
                'add_new_item' => 'Add New ' . static::$post_name['singular'],
                'edit_item' => 'Edit ' . static::$post_name['singular'],
                'view_item' => 'View ' . static::$post_name['singular'],
                'new_item' => 'New ' . static::$post_name['singular'],
                'search_items' => 'Search ' . static::$post_name['plural']
            ),
            'has_archive' => isset(static::$archive) ? static::$archive : true,
            'rewrite' => array(
                'slug' => isset(static::$slug) ? static::$slug : static::$post_type
            ),
            'menu_position' => isset(static::$menu_position) ? static::$menu_position : 20,
            'supports' => static::$supports,
            'public' => true,
            'exclude_from_search' => (isset(static::$public) && !static::$public) ? true : false,
            'publicly_queryable' => (isset(static::$public) && !static::$public) ? false : true
        );

        // Check if admin menu icon exists
        $icon_path = 'admin/icons/' . static::$post_type . '.png';

        if (file_exists(PATH . 'assets/' . $icon_path)) {
            $properties['menu_icon'] = ASSETS . $icon_path;
        }

        // Register post type with WordPress
        register_post_type(static::$post_type, $properties);

        // Register meta boxes
        if (isset(static::$custom_fields)) {
            new MetaBoxes(static::$post_type, static::$custom_fields);
        }
    }

    /**
     * Check if a given field name is reserved
     * @param String $name Field name
     * @return void
     */
    public static function valid_property($name)
    {
        if (in_array($name, self::$reserved_properties)) {
            throw new \Exception('Cannot use "' . $name . '": custom field property names cannot include: ' . implode(', ', self::$reserved_properties));
        }
    }

    /**
     * Create a Starch post object from a WordPress object
     * @param WP_Post &$post A WordPress post
     * @return Object A post object of the appropriate class
     */
    public static function create(&$post)
    {
        $class = self::$models[$post->post_type];

        if (!class_exists($class)) {
            throw new \Exception('Class "' . $class . '" not found - for post type "' . $post->post_type . '"');
        }

        return new $class($post);
    }

    /**
     * Creates Starch posts from an array of WordPress posts
     * @param Array &$posts An array of WordPress posts
     * @return Array An array of Starch post objects
     */
    protected static function create_array(&$posts)
    {
        $return = array();

        foreach ($posts as &$post) {
            $return[] = static::create($post);
        }

        return $return;
    }

    /**
     * Returns all of a particular post type
     * @return Array An array of all posts
     */
    public static function all()
    {
        $posts = get_posts(array(
            'post_type' => static::$post_type,
            'numberposts' => -1
        ));

        return static::create_array($posts);
    }

    /**
     * Get a post by numeric id
     * @param Integer $id The WordPress id
     * @return Object A post object
     */
    public static function post($id)
    {
        $post = get_post($id);
        return $post ? static::create($post) : null;
    }

    /**
     * Get a post by name
     * @param String $id The post name
     * @return Object A post object
     */
    public static function named($attr)
    {
        $posts = get_posts(array(
            'post_type' => static::$post_type,
            'name' => $attr,
            'numberposts' => 1,
            'post_status' => 'any'
        ));

        return isset($posts[0]) ? static::create($posts[0]) : null;
    }

    /**
     * Get a post/posts
     * @param Array $attr Selector query
     * @param Boolean $single Whether to return a single result
     * @return Array/Object An array of post objects or a single post object
     */
    public static function get($attr, $single = false)
    {
        $type = static::$post_type;

        $defaults = array(
            'post_type' => $type
        );

        $posts = get_posts(array_merge($attr, $defaults));

        $return = static::create_array($posts);

        if ($single) {
            return count($return) ? $return[0] : null;
        } else {
            return $return;
        }
    }



    /**
     * Object Functions & Variables
     */

    // Has the post object been populated
    public $populated = false;

    // Data store - uses magic method __get to access
    protected $post = array();

    /**
     * Creates a new post object
     * @param WP_Post/null &$post A WordPress post or null
     * @return void
     */
    public final function __construct(&$post = null)
    {
        if ($post) {
            $this->populate($post);
        }
    }

    /**
     * Populates the post with values. Empty posts can be useful for lazy loading
     * @param WP_Post &$post A WordPress post
     * @return void
     */
    public final function populate(&$post)
    {
        if (get_class($post) !== 'WP_Post') {
            throw new \Exception('Must be a WordPress Post object (WP_Post)');
        }

        $id = $post->ID;

        $this->post['id'] = $id;
        $this->post['title'] = $post->post_title;
        $this->post['link'] = get_permalink($id);
        $this->post['type'] = $post->post_type;
        $this->post['date'] = $post->post_date;
        $this->post['modified'] = $post->post_modified;
        $this->post['slug'] = $post->post_name;
        $this->post['edit_link'] = current_user_can('edit_post', $id) ? get_edit_post_link($id) : false;
        $this->post['excerpt'] = $post->post_excerpt;
        $this->post['unfiltered_content'] = $post->post_content;

        $this->populated = true;
    }

    /**
     * The magic method __get. Handles access to and lazy loading of the post data
     * @param String $name Property name
     * @return Mixed The requested property
     */
    public final function __get($name)
    {
        // If data not populated yet
        if (!$this->populated) {
            return null;
        }

        // Lazy load featured image
        if ($name === 'featured_image' && !array_key_exists('featured_image', $this->post)) {
            $this->load_featured_image();
        }

        // Lazy load attachments
        if ($name === 'attachments' && !array_key_exists('attachments', $this->post)) {
            $this->load_attachments();
        }

        // Lazy load next post
        if (
            ($name === 'next' && !array_key_exists('next', $this->post)) ||
            ($name === 'is_last' && !array_key_exists('is_last', $this->post))
        ) {
            $this->load_next();
        }

        // Lazy load previous post
        if (
            ($name === 'previous' && !array_key_exists('previous', $this->post)) ||
            ($name === 'is_first' && !array_key_exists('is_first', $this->post))
        ) {
            $this->load_previous();
        }

        // Try $post values
        if (array_key_exists($name, $this->post)) {
            return $this->post[$name];
        }

        // Get the filtered content
        if ($name === 'content') {
            // Lazy load content
            if (!array_key_exists('content', $this->post)) {
                $this->post['content'] = apply_filters('the_content', $this->post['unfiltered_content']);
            }

            return $this->post['content'];
        }

        // Try Meta Data
        $data = get_post_meta($this->id, $name, true);

        // Lazy load in
        if ($data) {
            $this->post[$name] = $data;
            return $data;
        }

        return null;
    }

    /**
     * Output a date with the specified format
     * @param String $format The date format (standard PHP date formatting)
     * @return String The formatted date
     */
    public function date($format = 'd/m/y') {
        $date = strtotime($this->post['date']);
        return date($format, $date);
    }

    /**
     * Output last modified date with the specified format
     * @param String $format The date format (standard PHP date formatting)
     * @return String The formatted date modified
     */
    public function modified($format = 'd/m/y') {
        $date = strtotime($this->post['modified']);
        return date($format, $date);
    }


    /**
     * Lazy Loading Functions
     */

    /**
     * Loads in the post's attachments (if any)
     * @return void
     */
    protected function load_attachments()
    {
        $attachments = get_posts(array(
            'numberposts' => -1,
            'post_type' => 'attachment',
            'post_parent' => $this->post['id']
        ));

        $return = array();

        foreach ($attachments as $attachment) {
            $return[] = new \Starch\Model\Attachment($attachment);
        }

        $this->post['attachments'] = $return;
    }

    /**
     * Loads in the post's featured image (if any)
     * @return void
     */
    protected function load_featured_image()
    {
        $id = get_post_thumbnail_id($this->post['id']);
        $post = (!$id || $id === $this->post['id']) ? $post = null : $post = get_post($id);
        $this->post['featured_image'] = new \Starch\Model\Attachment($post);;
    }

    /**
     * Loads in the next post
     * @return void
     */
    protected function load_next()
    {
        $this->load_adjacent(true);
    }

    /**
     * Loads in the previous post
     * @return void
     */
    protected function load_previous()
    {
        $this->load_adjacent(false);
    }

    /**
     * Handles finding adjacent posts. Loops if nothing found
     * @param Boolean $next Next post - looks for previous is false
     * @return void
     */
    protected function load_adjacent($next = true)
    {
        // Setup correct data keys
        $key = $next ? 'next' : 'previous';
        $place = $next ? 'is_last' : 'is_first';

        // See if an adjacent post exists
        $has_looped = false;
        $post = get_adjacent_post(false, false, $next ? false : true);

        // Loop to first/last post if not
        if (!$post) {
            $has_looped = true;
            $posts = get_posts(array(
                'post_type' => $this->post['type'],
                'numberposts' => 1,
                'order' => $next ? 'ASC' : 'DESC'
            ));
            $post = $posts[0];
        }

        // Make sure there is something to return
        if (!$post || $post->id === $this->post['id']) {
            $post = null;
        }

        // Set the values
        $this->post[$key] = PostType::create($post);
        $this->post[$place] = $has_looped;
    }
}