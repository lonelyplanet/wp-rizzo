<?php
namespace LonelyPlanet\DataStore;

class WPCacheStore extends DataStore {

    protected $key;
    protected $expiration;
    protected $group;

    private $wp_object_cache;

    public function __construct($key, $group = '', $expiration = 0)
    {
        $this->key = $key;
        $this->group = $group;
        $this->expiration = $expiration;

        // Keep a reference to this object so I can use it in the destructor.        
        if (isset($GLOBALS['wp_object_cache'])) {
            $this->wp_object_cache = &$GLOBALS['wp_object_cache'];
        }

        parent::__construct();
    }

    public function load()
    {
        $data = wp_cache_get($this->key, $this->group);

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
            wp_cache_set($this->key, $this->data, $this->group, $this->expiration);
        }
    }
}
