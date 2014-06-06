<?php
namespace LonelyPlanet\DataStore;

// This default implementation stores in memory only and will not persist.
// Override this class to provide your own storage options.

class DataStore implements \ArrayAccess, \IteratorAggregate {

    protected $data;
    protected $loaded;
    protected $save_data;

    public function __construct()
    {
        $this->loaded = false;
        $this->save_data = false;
        $this->load();
    }

    public function __destruct()
    {
        $this->save();
    }

    public function load()
    {
        // Override this in your own class.
        // This should load $this->data from some storage (cache, file system, etc.)
        $this->data = array();
        $this->loaded = true;
    }

    public function loaded()
    {
        return $this->loaded;
    }

    public function save()
    {
        // Override this in your own class.
    }

    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->data);
    }

    public function offsetGet($offset)
    {
        if (array_key_exists($offset, $this->data)) {
            return $this->data[$offset];
        }
        throw new \Exception('Undefined $offset');
    }
    
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
        $this->save_data = true;
    }

    public function offsetUnset($offset)
    {
        if ($this->offsetExists($offset)) {
            unset($this->data[$offset]);
            $this->save_data = true;
        }
    }

    public function __isset($offset)
    {
        return isset($this->data[$offset]);
    }

    public function __unset($offset)
    {
        unset($this->data[$offset]);
        $this->save_data = true;
    }

    public function __get($offset)
    {
        return $this->offsetGet($offset);
    }

    public function __set($offset, $value)
    {
        $this->data[$offset] = $value;
        $this->save_data = true;
    }

    public function getIterator()
    {
        return new ArrayIterator($this->data);
    }

}