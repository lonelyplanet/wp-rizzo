<?php
namespace LonelyPlanet\Rizzo;
use LonelyPlanet\Autoloader;


defined('ABSPATH') || exit;


// Don't run on command line.
if ( php_sapi_name() === 'cli' )
    return;


include __DIR__ . '/inc/functions.php';
include __DIR__ . '/inc/Autoloader.php';


$rizzo_autoloader = new Autoloader();
$rizzo_autoloader->addNamespace('LonelyPlanet', __DIR__ . '/inc/');
$rizzo_autoloader->register();


add_action('plugins_loaded', function () {

    global $wprizzo;
    $wprizzo = new RizzoPlugin(WP_RIZZO_FILE);

}, 100);


/**
If you don't want to use output buffering, you can place this in your theme after the <body> tag:

if (function_exists('\LonelyPlanet\Rizzo\print_headers'))
    \LonelyPlanet\Rizzo\print_headers();
*/
function print_headers($print_pre = true, $print_post = true)
{
    global $wprizzo;

    // Don't do anything if the user preference is to auto insert the body headers.
    if (isset($wprizzo) && $wprizzo->insert_header() === false) {

        if ($print_pre)
            echo $wprizzo->get('pre-header-endpoint');

        $wprizzo->open_wrapper();

        if ($print_post)
            echo $wprizzo->get('post-header-endpoint');

    }
}
