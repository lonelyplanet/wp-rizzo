<?php

if ( ! defined('WP_UNINSTALL_PLUGIN') ) {
    exit;
}

wp_clear_scheduled_hook('rizzo-cron');

$options = array(
    'wp-rizzo-version',
    'rizzo-api',
    'rizzo-api-endpoints',
    'rizzo-api-args',
    'rizzo-cron',
    'rizzo-fetch-count',
    'rizzo-fetch-time',
    'rizzo-theme-hooks',
    'rizzo-cron-last-run',
    'rizzo-cron-run-count',
    'rizzo_html_head-endpoint',
    'rizzo_html_body-endpoint',
    'rizzo_html_pre-header-endpoint',
    'rizzo_html_post-header-endpoint',
    'rizzo_html_footer-endpoint',
);

foreach ( $options as $option ) {
    delete_option( $option );
}
