<?php

namespace Starch\Core;

class User
{
    /**
     * Static Methods
     */

    private static $defaults = array('ID', 'user_login', 'user_pass', 'user_nicename', 'user_email', 'user_url', 'user_registered', 'display_name');
    protected static $currentUser = null;

    public static function current()
    {
        if (self::$currentUser === null) {
            $currentUser = wp_get_current_user();

            if ($currentUser->ID) {
                $type = get_called_class();
                self::$currentUser = new $type($currentUser->ID);
            } else {
                self::$currentUser = false;
            }
        }

        return self::$currentUser;
    }

    public static function get($username)
    {
        $user = get_user_by_email($username);

        if (!$user) {
            $user = get_userdatabylogin($username);
        }

        if ($user) {
            $class = get_called_class();
            $user = new $class($user->ID);
        }

        return $user;
    }

    public static function login($username, $password)
    {
        if (is_email($username)) {
            $user = get_user_by('email', $username);
            $username = $user->user_login;
        }

        $loggedIn = wp_signon(array(
            'user_login' => $username,
            'user_password' => $password,
            'remember' => $true
        ), is_ssl());

        if (get_class($loggedIn) == 'WP_Error') {
            return false;
        }

        return $loggedIn;
    }

    public static function create($username, $email, $password)
    {
        $id = wp_insert_user(array(
            'user_login' => $username,
            'user_email' => $email
        ));

        if (gettype($id) === 'WP_Error') {
            return false;
        }

        $user = new User($id);
        $user->user_pass = $password;

        return $user;
    }


    /**
     * Instance Methods
     */

    private $data = array();
    private $password_hash = '';

    public function __construct($id)
    {
        $user = get_userdata($id);

        $data['id'] = $user->ID;
        $data['email'] = $user->user_email;
        $data['username'] = $user->user_login;
        $this->password_hash = $user->user_pass;

        $this->data = $data;
    }

    public function __get($name)
    {
        if (array_key_exists($name, $this->data)) {
            return $this->data[$name];
        } else {
            return get_user_meta($this->id, $name, true);
        }
    }

    public function __set($name, $value)
    {
        if ($name == 'email') {
            $array = array(
                'ID' => $this->data['id'],
                'user_email' => $value
            );
            wp_update_user($array);
        } else if (in_array($name, self::$defaults)) {
            $array = array(
                'ID' => $this->data['id'],
                $name => $value
            );
            wp_update_user($array);
        } else {
            update_user_meta($this->id, $name, $value);
        }

        $this->data[$name] = $value;
    }

    public function check_key($key)
    {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT ID FROM $wpdb->users WHERE user_activation_key = %s AND user_login = %s", $key, $this->username));
    }

    public function get_key()
    {
        global $wpdb;
        $key = $wpdb->get_var($wpdb->prepare("SELECT user_activation_key FROM $wpdb->users WHERE user_login = %s", $this->username));

        if (!$key) {
            $key = $this->new_key();
        }

        return $key;
    }

    public function new_key()
    {
        global $wpdb;
        $key = wp_generate_password(20, false);
        $wpdb->update($wpdb->users, array('user_activation_key' => $key), array('user_login' => $this->username));

        return $key;
    }

    public function change_password($new, $key = false)
    {
        if ($key) {
            if ($this->check_key($key)) {
                wp_set_password($new, $this->id);
                $this->new_key();
                return true;
            }
        } else {
            wp_set_password($new, $this->id);
            wp_set_auth_cookie($this->id, false, is_ssl());
            return true;
        }

        return false;
    }

    public function check_password($password)
    {
        return wp_check_password($password, $this->password_hash, $this->id);
    }

    public function send_forgotten_password_email($view, $headers = '')
    {
        global $wpdb;

        $username = $this->username;
        $email = $this->email;

        $key = $this->get_key();

        $site_name = get_bloginfo('name');
        $site_url = get_option('siteurl');
        $url_query = "action=reset_password&key=$key&username=" . rawurlencode($username);

        $message = View::render($view, array('url_query' => $url_query, 'username' => $username, 'site_name' => $site_name, 'site_url' => $site_url));

        if ($message && !wp_mail($email, 'Password Reset Request', $message, $headers)) {
            return false;
        }

        return true;
    }
}