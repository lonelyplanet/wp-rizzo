<?php
if ( !defined('WP_UNINSTALL_PLUGIN'))
    exit();

wp_clear_scheduled_hook('rizzo-cron');

$options = array(
    'rizzo',
    'rizzo-cron-last-run',
    'rizzo-cron-run-count',
    'rizzo_html_head',
    'rizzo_html_body',
    'rizzo_html_footer',
    'rizzo_html_head-endpoint',
    'rizzo_html_body-endpoint',
    'rizzo_html_footer-endpoint',
);

foreach ($options as $option) {
    delete_option($option);
}
