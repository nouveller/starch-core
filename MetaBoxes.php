<?php

namespace Starch\Core;

/**
 * Handles the creation of meta boxes on post types
 * @package default
 */
class MetaBoxes
{
    private $custom_fields;
    private $post_type;

    /**
     * Creates a new meta box
     * @param String $post_type The post type
     * @param Array $custom_fields The array of custom fields
     * @return void
     */
    public function __construct($post_type, $custom_fields)
    {
        $this->custom_fields = $custom_fields;
        $this->post_type = $post_type;

        add_action('add_meta_boxes', array($this, 'register_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
    }

    /**
     * Registers each custom field
     * @return void
     */
    public function register_meta_boxes()
    {
        foreach ($this->custom_fields as $id => $field) {
            add_meta_box(
                $id,
                $field['name'],

                function () use ($id, $field)
                {
                    $pass = array();
                    $uploads = false;

                    if (isset($_GET['post'])) {
                        $post = PostType::create(get_post($_GET['post']));
                    }

                    foreach ($field['field_names'] as $var) {
                        if (is_array($var) && isset($var['name'])) {
                            \Starch\Core\PostType::valid_property($var['name']);

                            switch ($var['type']) {
                            case 'upload':
                                $uploads = true;
                                $pass[$var['name']] = \Starch\Core\MetaBoxes::upload_input($var, $post);
                                break;
                            case 'editor':
                                $pass[$var['name']] = \Starch\Core\MetaBoxes::editor_input($var, $post);
                                break;
                            }
                        } else if (!is_array($var)) {
                            \Starch\Core\PostType::valid_property($var);

                            if (isset($post)) {
                                $pass[$var] = $post->$var;
                            } else {
                                $pass[$var] = '';
                            }
                        }
                    }

                    \Starch\Core\View::display($field['template'], $pass);

                    if (isset($field['js'])) {
                        echo '<script src="' . ASSETS . 'js/' . $field['js'] . '.js"></script>';
                    }

                    if ($uploads || isset($field['js'])) {
                        echo '<p class="js-warning"><b>Warning:</b> JavaScript is required</p>';
                    }

                    wp_nonce_field($id, $id . '_nonce');
                },

                $this->post_type,

                (isset($field['context'])) ? $field['context'] : 'normal',
                (isset($field['priority'])) ? $field['priority'] : 'default'
            );
        }
    }

    /**
     * Saves meta boxes and checks for nonce
     * @param Integer $post_id The post id
     * @return void
     */
    public function save_meta_boxes($post_id)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_page', $post_id)) {
            return;
        }

        if (empty($_POST)) {
            return;
        }

        foreach ($this->custom_fields as $id => $field) {
            if (!isset($_POST[$id . '_nonce'])) {
                return;
            }

            if (!wp_verify_nonce($_POST[$id . '_nonce'], $id)) {
                return;
            }

            foreach ($field['field_names'] as $var) {
                if (is_array($var)) {
                    if ($var['name']) {
                        $var = $var['name'];
                    } else {
                        continue;
                    }
                }

                if (isset($_POST[$var])) {
                    $val = $_POST[$var];
                    add_post_meta($post_id, $var, $val, true) or update_post_meta($post_id, $var, $val);
                } else {
                    // If not set then assume a checkbox and set to false - value should always exist in other situations
                    add_post_meta($post_id, $var, false, true) or update_post_meta($post_id, $var, false);
                }
            }
        }
    }

    /**
     * Static Functions
     */

    private static $editor_id;
    private static $upload_script_included = false;

    /**
     * Sets up an editor box
     * @param Array $var Array of variables
     * @param Object &$post The post object
     * @return String Returns the editor code
     */
    public static function editor_input($var, &$post)
    {
        $media_buttons = isset($var['media']) ? $var['media'] : false;

        ob_start();

        wp_editor($post ? $post->$var['name'] : null, 'editor' . self::$editor_id++, array('textarea_name' => $var['name'], 'media_buttons' => $media_buttons, 'teeny' => true));

        $return = ob_get_contents();

        ob_end_clean();

        return $return;
    }

    /**
     * Sets up a file upload box
     * @param Array $var An array of variables
     * @param Object &$post A post object
     * @return String The code of the file upload box
     */
    public static function upload_input($var, &$post)
    {
        $name = $var['name'];
        $show_thumb = isset($var['thumb']) ? $var['thumb'] : true;

        $value = $post ? $post->$name : null;

        ob_start();

        if (!static::$upload_script_included) { ?>
            <script src="<?= ASSETS ?>admin/upload.js"></script>
        <?php
            static::$upload_script_included = true;
        }

        ?><div class="media-upload<?= $show_thumb ? ' show-thumb' : '' ?>"><?php

        if ($value && preg_match('/Attachment\(([0-9]+)\)/', $value, $matches)) {
            $id = $matches[1];
            $attach = \Starch\Model\Attachment::post($id);
            $title = $attach->title;
            $thumb = $attach->thumbnail();

            if ($show_thumb && $thumb) { ?>

            <img class="thumb" src="<?= $thumb ?>" />

            <?php } ?>

            <p class="title"><?= $title ?></p>

        <?php } ?>

            <input class="file-upload" type="hidden" name="<?= $name ?>" value="<?= $value ?>" />
        </div>
        <?php

        $return = ob_get_contents();
        ob_end_clean();

        return $return;
    }
}