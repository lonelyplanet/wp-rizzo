<?php

namespace LonelyPlanet\Rizzo;
use LonelyPlanet\DataStore\DataStore;

class Rizzo {

    protected $endpoints;
    protected $args;
    protected $storage;
    protected $options;

    public function __construct(DataStore $storage, array $args = array())
    {
        $this->options = array_merge(
            array(
                'head_endpoint'   => 'http://rizzo.lonelyplanet.com/modern/head',
                'body_endpoint'   => 'http://rizzo.lonelyplanet.com/modern/body-header',
                'footer_endpoint' => 'http://rizzo.lonelyplanet.com/modern/body-footer',
                'timeout'         => 10,
            ),
            get_option('rizzo', array())
        );

        $this->options = apply_filters('rizzo-options', $this->options);

        $this->endpoints = array(
            'head_endpoint'   => &$this->options['head_endpoint'],
            'body_endpoint'   => &$this->options['body_endpoint'],
            'footer_endpoint' => &$this->options['footer_endpoint'],
        );

        $this->storage = $storage;
 
        $this->args = array_merge(
            array(
                'timeout' => $this->options['timeout']
            ),
            $args
        );
    }

    public function __destruct()
    {
    }

    public function get($key, array $args = array())
    {

        if (isset($this->storage[$key])) {
            printf('<!-- Getting %s section from cache -->', $key);
            return $this->storage[$key];
        }

        $key = strtolower($key);
        $api = null;

        if ( isset($this->endpoints[$key])) {
            $api = $this->endpoints[$key];
        } else {
            throw new \Exception('Unknown $key');
        }

        $args = array_merge($this->args, $args);

        $response = wp_remote_get($api, $args);
        
        printf('<!-- Getting %s section from api -->', $key);

        if (\is_wp_error($response)) {

            // var_dump($response);

        } elseif ((int)$response['response']['code'] === 200) {

            $this->storage[$key] = $response['body'];

            // var_dump($this->storage);

            return $this->storage[$key];

        } else {

            // non 200 status code

        }

    }
}