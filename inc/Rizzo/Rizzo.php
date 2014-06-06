<?php

namespace LonelyPlanet\Rizzo;
use LonelyPlanet\DataStore\DataStore;

class Rizzo {

    protected $endpoints;
    protected $args;
    protected $storage;

    public function __construct(DataStore $storage, array $args = array())
    {
        $this->endpoints = apply_filters(
            'rizzo-endpoints',
            array(
                'head'   => 'http://rizzo.lonelyplanet.com/modern/head',
                'body'   => 'http://rizzo.lonelyplanet.com/modern/body-header',
                'footer' => 'http://rizzo.lonelyplanet.com/modern/body-footer'
            )
        );

        $this->args = $args;

        $this->storage = $storage;
 
        /*
        $this->args = array(
            'timeout'     => 5,
            'redirection' => 5,
            'httpversion' => '1.0',
            'user-agent'  => 'WordPress/' . $wp_version . '; ' . get_bloginfo( 'url' ),
            'blocking'    => true,
            'headers'     => array(),
            'cookies'     => array(),
            'body'        => null,
            'compress'    => false,
            'decompress'  => true,
            'sslverify'   => true,
            'stream'      => false,
            'filename'    => null
        );
        */
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