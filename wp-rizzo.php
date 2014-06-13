<?php
/*
Plugin Name: WP Rizzo
Plugin Group: Lonely Planet
Plugin URI: http://lonelyplanet.com/
Author: Eric King
Author URI: http://webdeveric.com/
Description: This plugin fetches the three HTML chunks provided by Rizzo and then caches them for five minutes.
Version: 0.1
*/

namespace LonelyPlanet\WP\Rizzo;

use LonelyPlanet\Rizzo\Rizzo;
use LonelyPlanet\DataStore\DataStore;
use LonelyPlanet\DataStore\WPCacheStore;
use LonelyPlanet\DataStore\TransientStore;

include __DIR__ . '/inc/functions.php';
include __DIR__ . '/inc/Rizzo/Rizzo.php';
include __DIR__ . '/inc/DataStore/DataStore.php';
include __DIR__ . '/inc/DataStore/WPCacheStore.php';
include __DIR__ . '/inc/DataStore/TransientStore.php';

if (is_admin()) {
    include __DIR__ . '/inc/options.php';
}

add_action('init', function () {

    /*
    I'd rather not use global variables but since WP doesn't have a function like wp_head/footer
    for the body header content, this is what I'm stuck with for now.

    @todo Use ob_start and dynamically insert the body header code after the <body> tag.    
    */

    global $rizzo;

    $store = new WPCacheStore('html', 'rizzo', 0 );
    // $store = new TransientStore('html', 'rizzo', 60 );

    $rizzo = new Rizzo( $store );

    add_action('wp_head', function () use ($rizzo) {
        echo $rizzo->get('head_endpoint');
    } );

    add_action('wp_footer', function () use ($rizzo) {
        echo $rizzo->get('footer_endpoint');
    } );

} );

function rizzo_body()
{
    global $rizzo;
    if (isset($rizzo))
        echo $rizzo->get('body_endpoint');
}
