<?php
/*
Plugin Name: WP Rizzo
Plugin Group: Lonely Planet
Plugin URI: http://lonelyplanet.com/
Author: Eric King
Author URI: http://webdeveric.com/
Description: This plugin fetches the three HTML chunks provided by Rizzo and then automatically inserts them into the theme output.
Version: 0.2
*/

namespace LonelyPlanet\Rizzo;

include __DIR__ . '/inc/functions.php';

class RizzoPlugin {

    protected $after_body;
    protected $options;
    protected $rules;
    protected $endpoints;
    protected $args;

    private $plugin_file;
    private $menu_slug;

    public function __construct($plugin_file)
    {
        $this->plugin_file = $plugin_file;

        $this->init_options();

        add_filter('cron_schedules',      array($this, 'add_cron_schedule'));

        add_action('rizzo-cron',          array($this, 'run_cron'));
        add_action('update_option_rizzo', array($this, 'rizzo_option_updated'), 10, 2);

        if (is_admin()) {

            add_action('admin_init', array($this, 'admin_init'));
            add_action('admin_menu', array($this, 'add_plugin_page'));

        } else {

            // Set the priority to 1 so that it is printed higher up in the head section.
            // This iwll allow other queued css to override it.
            if ($this->option('insert-head'))
                add_action('wp_head', array($this, 'head'), 1, 0);

            // This will automatically insert the body header content using ob_start().
            if ($this->option('insert-body'))
                add_action('init', array($this, 'buffer_template'), 1, 0);

            if ($this->option('insert-footer'))
                add_action('wp_footer', array($this, 'footer'), 1, 0);

        }   

        register_activation_hook($this->plugin_file,   array($this, 'activate'));
        register_deactivation_hook($this->plugin_file, array($this, 'deactivate'));

    }

    public function add_plugin_page()
    {
        add_options_page(
            'Rizzo Settings',
            'Rizzo Settings',
            'manage_options',
            $this->menu_slug = 'rizzo-settings',
            array( $this, 'create_admin_page' )
        );
    }

    public function create_admin_page()
    {
        ?>
        <div class="wrap">
            <?php screen_icon(); ?>
            <h2>Rizzo Settings</h2>           
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields('rizzo');   
                do_settings_sections( 'rizzo-settings' );
                submit_button(); 
            ?>
            </form>
        </div>
        <?php
    }

    public function init_options()
    {
        $this->options = array(
            'head-endpoint'   => 'http://rizzo.lonelyplanet.com/modern/head',
            'body-endpoint'   => 'http://rizzo.lonelyplanet.com/modern/body-header',
            'footer-endpoint' => 'http://rizzo.lonelyplanet.com/modern/body-footer',
            'timeout'         => 5,
            'cron-time'       => 300, // Five minutes.
            'insert-head'     => true,
            'insert-body'     => true,
            'insert-footer'   => true
        );

        $this->options = apply_filters(
            'rizzo-options',
            array_merge(
                $this->options,
                get_option('rizzo', array())
            )
        );

        $this->endpoints = array(
            'head'   => &$this->options['head-endpoint'],
            'body'   => &$this->options['body-endpoint'],
            'footer' => &$this->options['footer-endpoint'],
        );

        $this->args = array(
            'timeout' => &$this->options['timeout']
        );
    }

    protected function option($name, $default = '')
    {
        return isset($this->options[$name]) ? $this->options[$name] : $default;
    }

    public function admin_init()
    {
        register_setting('rizzo', 'rizzo', array($this, 'sanitize'));

        $labels = array(
            'head'            => 'Head Endpoint',
            'body'            => 'Body Endpoint',
            'footer'          => 'Footer Endpoint',
            'timeout'         => 'Connection Timeout (seconds)',
            'cron-time'       => 'Cron Interval (seconds)',
            'insert-head'     => 'Insert Head Content',
            'insert-body'     => 'Insert Body Content',
            'insert-footer'   => 'Insert Footer Content',
        );

        add_settings_section(
            'rizzo-api-endpoints',
            'API Endpoints',
            array( $this, 'print_api_endpoint_info' ),
            $this->menu_slug
        );

        foreach ($this->endpoints as $key => $endpoint) {
            // add_settings_field( $id, $title, $callback, $page, $section, $args );
            add_settings_field(
                $endpoint,
                $labels[$key],
                array($this, 'input'),
                $this->menu_slug,
                'rizzo-api-endpoints',
                array(
                    'label_for'  => $key,
                    'value'      => $this->options[$key],
                    'attributes' => array(
                        'id'    => $key,
                        'class' => 'widefat',
                        'type'  => 'url'
                    )
               )
            );
        }

        add_settings_section(
            'rizzo-cron',
            'Cron Settings',
            array( $this, 'print_cron_section_info'),
            $this->menu_slug
        );

        add_settings_field(
            'timeout',
            $labels['timeout'],
            array($this, 'input'),
            $this->menu_slug,
            'rizzo-cron',
            array(
                'label_for'  => 'timeout',
                'attributes' => array(
                    'id'    => 'timeout',
                    'type'  => 'number',
                    'value' => $this->options['timeout'],

                )
            )
        );

        add_settings_field(
            'cron-time',
            $labels['cron-time'],
            array($this, 'input'),
            $this->menu_slug,
            'rizzo-cron',
            array(
                'label_for'  => 'cron-time',
                'attributes' => array(
                    'id'    => 'cron-time',
                    'type'  => 'number',
                    'value' => $this->options['cron-time'],
                )
            )
        );

        add_settings_section(
            'rizzo-html-output',
            'HTML Output',
            null, //array( $this, 'print_api_endpoint_info' ),
            $this->menu_slug
        );

        foreach (array('insert-head','insert-body','insert-footer') as $key) {

            add_settings_field(
                $key,
                $labels[$key],
                array($this, 'input'),
                $this->menu_slug,
                'rizzo-html-output',
                array(
                    'label_for'   => $key,
                    'attributes'  => array(
                        'id'      => $key,
                        'type'    => 'checkbox',
                        'value'   => 1,
                        'checked' => $this->options[$key] == 1
                    )
                )
            );

        }
    }

    public function sanitize($input)
    {
        $new_input = array();

        /*
        var_dump($input);
        wp_die();
        */

        foreach ($this->endpoints as $endpoint => $label) {
            if (isset($input[$endpoint]) && ! empty($input[$endpoint])) {
                $new_input[$endpoint] = filter_var($input[$endpoint], FILTER_VALIDATE_URL);
            }
        }

        if (isset($input['timeout'])) {
            $new_input['timeout'] = \LonelyPlanet\Func\number_in_range($input['timeout'], 0, 60);
        }

        if (isset($input['cron-time'])) {
            $new_input['cron-time'] = \LonelyPlanet\Func\number_in_range($input['cron-time'], 60, 3600);
        }

        foreach (array('insert-head','insert-body','insert-footer') as $key) {
            if (isset($input[$key]) && (int)$input[$key] == 1)
                $new_input[$key] = true;
            else
                $new_input[$key] = false;
        }

        return $new_input;
    }

    public function print_api_endpoint_info()
    {
        echo '<p>Enter your custom API endoints below. If you don&#8217;t fill this in, the default endpoints will be used.</p>';
    }

    public function print_cron_section_info()
    {
        $last_run = get_option('rizzo-cron-last-run', array());
        printf(
            '<p>Cron run count: %1$d | Last run: %2$s ago',
            get_option('rizzo-cron-run-count', 0),
            human_time_diff($last_run['stop'], time())
        );
    }


    public function rizzo_option_updated($old_value, $value)
    {
        // After the option has been updated, run the cron again to fetch content from the API.
        do_action('rizzo-cron');
    }

    public function activate()
    {
        wp_schedule_event(time(), 'rizzo-schedule', 'rizzo-cron');
        do_action('rizzo-cron');
    }

    public function deactivate()
    {
        wp_clear_scheduled_hook('rizzo-cron');
    }

    public function add_cron_schedule($schedules)
    {
        $schedules['rizzo-schedule'] = array(
            'interval' => $this->option('cron-time', 60),
            'display'  => 'Rizzo Schedule'
        );
        return $schedules;
    }

    public function buffer_template()
    {
        ob_start(array($this, 'handle_buffer'));
        $this->after_body = $this->get('body');
    }

    function handle_buffer($buffer)
    {
        return substr_replace(
            $buffer,
            $this->after_body,
            stripos(
                $buffer,
                '>',
                stripos($buffer, '<body')
            ) + 1,
            0
        );
    }

    public function head()
    {
        echo $this->get('head');
    }

    public function footer()
    {
        echo $this->get('footer');
    }

    public function input($args)
    {

        // var_dump($args);

        if ( ! isset($args['attributes'])) {
            $args['attributes'] = array();
        }

        if ( ! is_array($args['attributes'])) {
            $args['attributes'] = (array)$args['attributes'];
        }

        if ( ! isset($args['attributes']['type'])) {
            $args['attributes']['type'] = 'text';
        }

        if ( isset($args['id']) && ! isset($args['attributes']['id'])) {
            $args['attributes']['id'] = $args['id'];
        }

        if ( ! isset($args['attributes']['id'])) {
            $args['attributes']['id'] = '';
        }

        if ( isset($args['value']) && ! isset($args['attributes']['value'])) {
            $args['attributes']['value'] = $args['value'];
        }
        
        $attributes = \LonelyPlanet\Func\html_attr($args['attributes']);

        printf(
            '<input %1$s name="rizzo[%2$s]" />',
            $attributes,
            $args['attributes']['id']
        );
    }

    public function run_cron()
    {
        $run_count = intval(get_option('rizzo-cron-run-count', 0));
        update_option('rizzo-cron-run-count', ++$run_count);
        foreach ($this->endpoints as $key => $url) {
            $this->fetch($url, $key);
        }
    }

    public function get($name)
    {
        $key = 'rizzo_html_' . $name;
        $html = get_option($key, '');
        return apply_filters($key, $html);
    }

    public function set($name, $html)
    {
        update_option('rizzo_html_' . $name, $html);
    }

    public function fetch($url, $option_key, array $args = array())
    {
        $args = array_merge($this->args, $args);

        $response = wp_remote_get($url, $args);

        if (isset($response['response']['code']) && (int)$response['response']['code'] === 200) {

            $this->set($option_key, $response['body']);
            return true;

        } else {

            // $response could be WP_Error instance.
            do_action('rizzo-fetch-error', $url, $options_key, $args, $response);

        }

        return false;
    }
}

new RizzoPlugin(__FILE__);
