<?php
/*
Plugin Name: WP Rizzo
Plugin Group: Lonely Planet
Plugin URI: http://lonelyplanet.com/
Author: Eric King
Author URI: http://webdeveric.com/
Description: This plugin fetches the three HTML chunks provided by Rizzo and then automatically inserts them into the theme output.
Version: 0.3
*/

defined('ABSPATH') || exit;

if (version_compare(PHP_VERSION, '5.3.0', '<')) {

    if (is_admin()) {

        function wp_rizzo_requirements_not_met()
        {
            echo '<div class="error"><p>PHP 5.3+ is required for WP Rizzo. You have PHP ', PHP_VERSION, ' installed. This plugin has been deactivated.</p></div>';
            deactivate_plugins(plugin_basename(__FILE__));
            unset($_GET['activate']);
        }
        add_action( 'admin_notices', 'wp_rizzo_requirements_not_met' );

    }

    return;

}

define('WP_RIZZO_FILE', __FILE__);
define('WP_RIZZO_VERSION', '0.3');

include dirname(__FILE__) . '/wp-rizzo.php';
