<?php
namespace LonelyPlanet\DataStore;

class TransientStore extends DataStore {

    protected $key;
    protected $expiration;

    private $wp_object_cache;
    private $wpdb;

    public function __construct($key, $group = '', $expiration = 0)
    {
        $this->key = $key . $group;
        $this->expiration = $expiration;

        // Keep a reference to this object so I can use it in the destructor.        
        if (isset($GLOBALS['wp_object_cache'])) {
            $this->wp_object_cache = &$GLOBALS['wp_object_cache'];
        }

        if (isset($GLOBALS['wpdb'])) {
            $this->wpdb = &$GLOBALS['wpdb'];
        }

        parent::__construct();
    }

    public function load()
    {
        $data = get_transient($this->key);

        if (is_array($data) && ! empty($data)) {
            $this->loaded = true;
            $this->data = $data;
        } else {
            $this->loaded = false;
            $this->data = array();            
        }
    }

    public function save()
    {
        if ($this->save_data) {
            set_transient($this->key, $this->data, $this->expiration);
        }
    }
}
