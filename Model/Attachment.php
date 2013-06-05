<?php

namespace Starch\Core\Model;
use Starch\Core\PostType;

/**
 * Attachment specific methods
 * @package default
 */
class Attachment extends PostType
{
    protected static $__built_in = true;
    public static $post_type = 'attachment';

    protected $meta = null;

    /**
     * Return the attachment file url
     * @return String File url
     */
    public function url()
    {
        if ($this->id) {
            return wp_get_attachment_url($this->id);
        }

        return null;
    }

    /**
     * Return the attachment meta data
     * @return Array The meta data
     */
    public function meta($name)
    {
        if ($this->id) {
            if ($this->meta === null) {
                $this->meta = wp_get_attachment_metadata($this->id);
            }

            return $this->meta;
        }

        return null;
    }

    /**
     * Returns the filename of the attachment
     * @return String The file name
     */
    public function filename()
    {
        if ($this->id) {
            $meta = $this->meta();
            return basename($meta['file']);
        }

        return null;
    }

    /**
     * Returns the thumbnail url
     * @param String/Array $size Thumbnail size
     * @return String URL of the thumbnail
     */
    public function thumbnail($size = 'thumbnail')
    {
        if ($this->id) {
            $img = wp_get_attachment_image_src($this->id, $size);
            return $img[0];
        }

        return null;
    }
}