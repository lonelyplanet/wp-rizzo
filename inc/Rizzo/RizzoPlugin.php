<?php
namespace LonelyPlanet\Rizzo;

class RizzoPlugin {

    protected $after_body;
    protected $wrapper_printed;
    protected $options;
    protected $rules;
    protected $endpoints;
    protected $args;

    protected $plugin_file;
    protected $menu_slug;

    protected $timeout_min;
    protected $timeout_max;
    protected $cron_time_min;
    protected $cron_time_max;

    protected $settings_tabs;

    public function __construct($plugin_file)
    {
        $this->plugin_file = $plugin_file;

        $this->init();

        // If you want to use your own error handler, you can disable mine with this:
        // remove_all_actions('rizzo-fetch-error');

        add_action('rizzo-cron',               array(&$this, 'run_cron'));
        add_action('rizzo-fetch-error',        array(&$this, 'fetch_error'), 10, 4);
        add_action('update_option_rizzo-cron', array(&$this, 'rizzo_cron_option_updated'), 10, 2);
        add_action('admin_bar_menu',           array(&$this, 'modify_admin_bar'), 1000, 1);
        add_action('init',                     array(&$this, 'check_enable_cron'), 10, 0);

        add_filter('cron_schedules',           array(&$this, 'add_cron_schedule'));

        if ($this->option('update-logo-url', false))
            add_filter('rizzo_html_post-header-endpoint', array(&$this, 'fix_logo'), 1, 1);

        // add_filter('pre_update_option_rizzo_html_post-header-endpoint', array(&$this, 'fix_logo_during_option_update'), 10, 2 );

        if (is_admin()) {

            add_action('admin_init', array(&$this, 'admin_init'));
            add_action('admin_menu', array(&$this, 'add_plugin_page'));
            add_filter('plugin_action_links_' . plugin_basename($this->plugin_file), array(&$this, 'plugin_links'), 10, 2 );

        } else {

            add_action('init', array(&$this, 'setup_hooks'));

        }   

        register_activation_hook($this->plugin_file,   array(&$this, 'activate'));
        register_deactivation_hook($this->plugin_file, array(&$this, 'deactivate'));

    }

    public function setup_hooks()
    {
        if ( ! defined('DOING_CRON') &&
             ! defined('DOING_AJAX') &&
             ! defined('APP_REQUEST') &&
             ! defined('XMLRPC_REQUEST') &&
             ! is_feed() &&
             ! in_array( $GLOBALS['pagenow'], array( 'wp-login.php', 'wp-register.php' ) ) ) {

            // Set the priority to 1 so that it is printed higher up in the head section.
            // This will allow other queued css to override it.

            if ($this->option('insert-head'))
                add_action('wp_head', array(&$this, 'head'), 1, 0);

            if ($this->insert_header())
                $this->buffer_template();

            if ($this->option('insert-footer'))
                add_action('wp_footer', array(&$this, 'footer'), 1, 0);

            add_action('wp_footer', array(&$this, 'close_wrapper'), 1000, 0);

        }
    }

    public function modify_admin_bar($bar)
    {
        $bar->add_node(
            array(
                'id'    => 'rizzo-settings',
                'title' => 'Rizzo Settings',
                'href'  => admin_url('options-general.php?page=rizzo-settings'),
            )
        );
    }

    function plugin_links($links, $file)
    {
        if ($file == plugin_basename($this->plugin_file)) {
            array_unshift(
                $links,
                sprintf('<a href="%1$s">%2$s</a>', \menu_page_url($this->menu_slug, false), 'Settings')
            );
        }
        return $links;
    }

    public function add_plugin_page()
    {
        $menu = add_options_page(
            'Rizzo Settings',
            'Rizzo Settings',
            'manage_options',
            $this->menu_slug = 'rizzo-settings',
            array(&$this, 'create_admin_page')
        );

        add_action( 'admin_print_styles-' . $menu, array(&$this, 'enqueue_css'));
    }

    public function enqueue_css()
    {
        wp_enqueue_style(
            'rizzo-admin',
            plugins_url('/css/rizzo-admin.css', $this->plugin_file),
            array(),
            WP_RIZZO_VERSION
        );
    }

    public function create_admin_page() {
        $tab = null;
        if (filter_has_var(INPUT_GET, 'tab') && array_key_exists($_GET['tab'], $this->settings_tabs)) {
            $tab = $_GET['tab'];
        } else {
            reset($this->settings_tabs);
            $tab = key($this->settings_tabs);
        }
        $button_label = 'Save ' . $this->settings_tabs[$tab] . ' Settings';
        ?>
        <div class="wrap">
            <h2>Rizzo Settings</h2>
            <?php $this->plugin_options_tabs($tab); ?>
            <form method="post" action="options.php">
                <?php
                    // wp_nonce_field( 'update-options' );
                    settings_fields($tab);
                    do_settings_sections($tab);
                    submit_button($button_label);
                ?>
            </form>
        </div>
        <?php
    }

    public function plugin_options_tabs($tab)
    {
        echo '<h2 class="nav-tab-wrapper">';
        foreach ($this->settings_tabs as $key => $caption) {
            $active = $tab == $key ? 'nav-tab-active' : '';
            $url = \add_query_arg(
                array('tab' => $key),
                \menu_page_url($this->menu_slug, false)
            );
            echo '<a class="nav-tab ', $active, '" href="', $url, '">', $caption, '</a>';
        }
        echo '</h2>';
    }

    public function init()
    {
        $this->wrapper_printed = false;

        /*
        If you want to filter these in your own plugin, use something like this:
        add_filter('pre_option_rizzo-api', 'your_callable', 10, 1);
        */
        $this->rizzo_api = array_merge(
            array(
                'head-endpoint'         => 'http://rizzo.lonelyplanet.com/layouts/modern/head',
                'pre-header-endpoint'   => 'http://rizzo.lonelyplanet.com/layouts/modern/pre_header',
                'post-header-endpoint'  => 'http://rizzo.lonelyplanet.com/layouts/modern/post_header',
                'footer-endpoint'       => 'http://rizzo.lonelyplanet.com/layouts/modern/footer',
            ),
            get_option('rizzo-api', array())
        );

        $this->rizzo_cron = array_merge(
            array(
                'timeout'      => 3,
                'cron-time'    => 300, // Five minutes.
                'disable-cron' => false
            ),
            get_option('rizzo-cron', array())
        );

        $this->rizzo_theme_hooks = array_merge(
            array(
                'insert-head'        => true,
                'insert-pre-header'  => true,
                'insert-post-header' => true,
                'insert-footer'      => true,
                'update-logo-url'    => false
            ),
            get_option('rizzo-theme-hooks', array())
        );

        $this->args = array(
            'timeout' => &$this->rizzo_cron['timeout']
        );

        // This is just for convenience.
        foreach (array('rizzo_api', 'rizzo_cron', 'rizzo_theme_hooks') as $options_name) {
            foreach ($this->$options_name as $key => &$value) {
                $this->options[$key] = &$value;
            }
        }
        
    }

    public function option($name, $default = '')
    {
        return isset($this->options[$name]) ? $this->options[$name] : $default;
    }

    public function insert_header()
    {
        return $this->option('insert-pre-header', false) || $this->option('insert-post-header', false);
    }

    public function admin_init()
    {
        $this->timeout_min   = apply_filters('rizzo-timeout-min', 0);
        $this->timeout_max   = apply_filters('rizzo-timeout-max', 60);
        $this->cron_time_min = apply_filters('rizzo-cron-time-min', 60); // One minute
        $this->cron_time_max = apply_filters('rizzo-cron-time-max', 60 * 60 * 24); // One day

        $this->settings_tabs = array(
            'rizzo-api'         => 'Rizzo API',
            'rizzo-cron'        => 'Schedule',
            'rizzo-theme-hooks' => 'Theme Hooks'
        );

        foreach ($this->settings_tabs as $key => $value) {
            $method = 'register_' . str_replace( array('rizzo-','-'), array('', '_'), trim($key));
            if (method_exists($this, $method))
                $this->$method($key, $value);
        }

    }

    public function sanitize_api($input)
    {
        $new_input = array();

        foreach ($this->rizzo_api as $key => $url) {
            if (isset($input[$key]) && ! empty($input[$key])) {
                $new_input[$key] = $this->rizzo_url($input[$key]);
            }
        }

        return $new_input;
    }

    public function register_api($tab, $label)
    {
        register_setting($tab, $tab, array(&$this, 'sanitize_api'));

        $labels = array(
            'head-endpoint'   => 'Head Endpoint',
            'pre-header-endpoint' => 'Pre Header Endpoint<br /><small>Cookie compliance, accessibility, <span class="nowrap">ad banner</span></small>',
            'post-header-endpoint' => 'Post Header Endpoint<br /><small>Main navigation bar</small>',
            'footer-endpoint' => 'Footer Endpoint',
        );

        add_settings_section(
            'rizzo-api-endpoints',
            'Endpoints',
            array(&$this, 'print_api_endpoint_info'),
            $tab
        );

        foreach ($this->rizzo_api as $key => $endpoint) {
            // add_settings_field( $id, $title, $callback, $page, $section, $args );
            add_settings_field(
                $endpoint,
                $labels[$key],
                array(&$this, 'input'),
                $tab,
                'rizzo-api-endpoints',
                array(
                    'label_for'  => $key,
                    'value'      => $endpoint,
                    'attributes' => array(
                        'id'    => $key,
                        'class' => 'widefat',
                        'type'  => 'url',
                        'name'  => $tab . '[' . $key . ']'
                    )
               )
            );
        }

    }

    public function sanitize_cron($input)
    {
        $new_input = array();

        if (isset($input['timeout'])) {
            $new_input['timeout'] = \LonelyPlanet\Func\number_in_range(
                $input['timeout'],
                $this->timeout_min,
                $this->timeout_max
            );
        }

        if (isset($input['cron-time'])) {
            $new_input['cron-time'] = \LonelyPlanet\Func\number_in_range(
                $input['cron-time'],
                $this->cron_time_min,
                $this->cron_time_max
            );
        }

        if (isset($input['disable-cron']) && (int)$input['disable-cron'] == 1)
            $new_input['disable-cron'] = true;
        else
            $new_input['disable-cron'] = false;

        return $new_input;
    }

    public function register_cron($tab, $label)
    {
        register_setting($tab, $tab, array(&$this, 'sanitize_cron'));

        add_settings_section(
            'rizzo-cron',
            'Schedule Settings',
            array(&$this, 'print_cron_section_info'),
            $tab
        );

        add_settings_field(
            'timeout',
            'Connection Timeout<br /><small>number of seconds</small>',
            array(&$this, 'input'),
            $tab,
            'rizzo-cron',
            array(
                'label_for'  => 'timeout',
                'attributes' => array(
                    'id'    => 'timeout',
                    'type'  => 'number',
                    'value' => $this->rizzo_cron['timeout'],
                    'min'   => $this->timeout_min,
                    'max'   => $this->timeout_max,
                    'name'  => $tab . '[timeout]'
                )
            )
        );

        add_settings_field(
            'cron-time',
            'Cron Interval<br /><small>number of seconds</small>',
            array(&$this, 'input'),
            $tab,
            'rizzo-cron',
            array(
                'label_for'  => 'cron-time',
                'attributes' => array(
                    'id'    => 'cron-time',
                    'type'  => 'number',
                    'value' => $this->rizzo_cron['cron-time'],
                    'min'   => $this->cron_time_min,
                    'max'   => $this->cron_time_max,
                    'name'  => $tab . '[cron-time]'
                )
            )
        );

        add_settings_field(
            'disable-cron',
            'Disable Cron',
            array(&$this, 'input'),
            $tab,
            'rizzo-cron',
            array(
                'label_for'   => 'disable-cron',
                'attributes'  => array(
                    'id'      => 'disable-cron',
                    'type'    => 'checkbox',
                    'value'   => 1,
                    'checked' => $this->rizzo_cron['disable-cron'] == 1,
                    'name'    => $tab . '[disable-cron]'
                )
            )
        );
    }

    public function sanitize_theme_hooks($input)
    {
        $new_input = array();

        foreach (array('insert-head', 'insert-pre-header', 'insert-post-header', 'insert-footer', 'update-logo-url') as $key) {
            if (isset($input[$key]) && (int)$input[$key] == 1)
                $new_input[$key] = true;
            else
                $new_input[$key] = false;
        }

        return $new_input;
    }

    public function register_theme_hooks($tab, $label)
    {
        register_setting($tab, $tab, array(&$this, 'sanitize_theme_hooks'));

        add_settings_section(
            'rizzo-theme-hooks',
            'Automatically Output HTML',
            array(&$this, 'print_theme_hooks_info'),
            $tab
        );

        $fields = array(
            'insert-head'        => 'Insert Head Content<br /><small>This hooks into wp_head()</small>',
            'insert-pre-header'  => 'Insert Pre Header Content<br /><small>This uses output buffering.</small>',
            'insert-post-header' => 'Insert Post Header Content<br /><small>This uses output buffering.</small>',
            'insert-footer'      => 'Insert Footer Content<br /><small>This hooks into wp_footer()</small>',
            'update-logo-url'    => 'Set the Lonely Planet logo link to my home URL <small>(' . get_home_url() . ')</small>',
        );

        foreach ($fields as $key => $label) {
            add_settings_field(
                $key,
                $label,
                array(&$this, 'input'),
                $tab,
                'rizzo-theme-hooks',
                array(
                    'label_for'   => $key,
                    'attributes'  => array(
                        'id'      => $key,
                        'type'    => 'checkbox',
                        'value'   => 1,
                        'checked' => $this->rizzo_theme_hooks[$key] == 1,
                        'name'  => $tab . '[' . $key . ']'
                    )
                )
            );
        }

    }

    public function print_theme_hooks_info()
    {
        echo '<p>This plugin hooks into the wp_head and wp_footer functions to output the HTML content for those sections.</p>';
        echo '<p>Since WordPress doesn&#8217;t have a function like wp_footer for the body header, I had to use output buffering to automatically insert the body header into the html.</p>';

        printf(
            '<p>If you don&#8217;t want to use output buffering, uncheck "Insert Pre Header Content" and "Insert Post Header Content", and place this in your theme after the <code>&lt;body&gt;</code> tag:</p><p class="rizzo-code-sample">%1$s</p>',
            highlight_string("<?php\nif (function_exists('\\LonelyPlanet\\Rizzo\\print_headers'))\n\t\\LonelyPlanet\\Rizzo\\print_headers();\n?>", true)
        );

        echo '<p>The <code>print_headers()</code> function takes two optional parameters ($print_pre = true, $print_post = true) that control which of the two headers you want to print out.</p>';
    }

    public function rizzo_url($url)
    {
        $url = filter_var($url, FILTER_VALIDATE_URL);

        if ($url !== false) {
            $host = strtolower(parse_url($url, PHP_URL_HOST));
            return $host === 'rizzo.lonelyplanet.com' ? $url : false;
        }

        return $url;
    }

    public function print_api_endpoint_info()
    {
        echo '<p>Enter your custom API endoints below. If you don&#8217;t fill this in, the default endpoints will be used.</p>';
    }

    public function print_cron_section_info()
    {
        $has_scheduled_event = wp_next_scheduled('rizzo-cron');

        $messages = array();
        
        $messages[] = $has_scheduled_event === false ? '<span class="cron-disabled">Cron is disabled</span>' : '<span class="cron-enabled">Cron is enabled</span>';

        $last_run = get_option('rizzo-cron-last-run', array());

        if (isset($last_run['start'], $last_run['stop'])) {
            $messages[] = sprintf('Cron run count: %1$s', number_format(get_option('rizzo-cron-run-count', 0)));
            $messages[] = sprintf('Last run: %1$s ago', human_time_diff($last_run['stop'], time()));
        } else {
            $messages[] = 'Cron has not run yet';
        }

        if ($has_scheduled_event !== false) {
            $messages[] = sprintf('Next run: %s', human_time_diff($has_scheduled_event, time()));
        }

        echo '<p>', implode(' | ', $messages), '</p>';

        echo '<p>This plugin uses <a href="http://codex.wordpress.org/Function_Reference/wp_schedule_event" target="_blank">scheduled events</a>, which is the way WordPress does cron jobs.</p>';
        echo '<p>If you don&#8217t want to always have the latest content from rizzo, you can disable the cron below.</p>';
    }

    public function rizzo_cron_option_updated($old_value, $new_value)
    {
        if ($new_value['disable-cron']) {
            $this->disable_cron();
        } else {
            if ($old_value['cron-time'] !== $new_value['cron-time']) {
                $this->disable_cron();
            }
            $this->enable_cron($new_value['cron-time']);
        }
    }

    public function activate()
    {
    }

    public function deactivate()
    {
        $this->disable_cron();
    }

    public function check_enable_cron()
    {
        if ($this->option('disable-cron', false) === false) {
            $this->enable_cron();
        } 
    }

    public function enable_cron($cron_time = null)
    {
        if ( ! wp_next_scheduled('rizzo-cron')) {

            if ( ! isset($cron_time))
                $cron_time = $this->option('cron-time', 0);

            wp_schedule_event(
                (int)$cron_time + time(),
                'rizzo-schedule',
                'rizzo-cron'
            );

        }
    }

    public function disable_cron()
    {
        wp_clear_scheduled_hook('rizzo-cron');
    }

    public function add_cron_schedule($schedules)
    {
        $schedules['rizzo-schedule'] = array(
            'interval' => $this->option('cron-time'),
            'display'  => 'Rizzo Schedule'
        );
        return $schedules;
    }

    public function buffer_template()
    {
        ob_start(array(&$this, 'handle_buffer'));

        $header = array();

        if ($this->option('insert-pre-header', false))
            $header[] = $this->get('pre-header-endpoint');

        $header[] = $this->open_wrapper(false, true);

        if ($this->option('insert-post-header', false))
            $header[] = $this->get('post-header-endpoint');

        $this->after_body = implode('', $header);
    }

    function handle_buffer($buffer)
    {
        global $wp_query;

        $insert_header = false;

        $properties = apply_filters(
            'rizzo-check-wp-query-properties',
            array(
                'is_singular',
                'is_preview',
                'is_archive',
                'is_search',
                'is_trackback',
                'is_paged',
                'is_posts_page',
                'is_home',
                'is_404',
            )
        );

        foreach ( $properties as &$prop ) {
            if ( $wp_query->$prop === true ) {
                $insert_header = true;
                break;
            }
        }

        // Use this filter if you have your own criteria for inserting the header into the buffer.
        $insert_header = apply_filters( 'rizzo-insert-header-buffer', $insert_header );

        if ($insert_header) {

            $body_position = stripos($buffer, '<body');

            if ($body_position !== false) {

                $closing_body_char = stripos( $buffer, '>', $body_position );

                if ($closing_body_char !== false) {

                    return substr_replace(
                        $buffer,
                        apply_filters( 'rizzo-after-body', $this->after_body),
                        $closing_body_char + 1,
                        0
                    );

                }

            }

        }

        return $buffer;
    }

    public function head($print = true)
    {
        return $this->get('head-endpoint', $print);
    }

    public function pre_header($print = true)
    {
        return $this->get('pre-header-endpoint', $print);
    }

    public function post_header($print = true)
    {
        return $this->get('post-header-endpoint', $print);
    }

    public function open_wrapper($print = true, $set_flag = false)
    {
        $wrapper = '<div class="wrapper js-wrapper">';

        if ($print)
            echo $wrapper;

        if ($print || $set_flag)
            $this->wrapper_printed = true;

        return $wrapper;
    }

    public function close_wrapper()
    {
        if ($this->wrapper_printed)
            echo '</div><!-- .wrapper.js-wrapper -->';
    }

    public function footer($print = true)
    {
        return $this->get('footer-endpoint', $print);
    }

    public function input($args)
    {
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

        if ( ! isset($args['attributes']['name'])) {
            $args['attributes']['name'] = $args['attributes']['id'];
        }

        if ( isset($args['value']) && ! isset($args['attributes']['value'])) {
            $args['attributes']['value'] = $args['value'];
        }
        
        $attributes = \LonelyPlanet\Func\html_attr($args['attributes']);

        echo '<input ', $attributes, ' />';

    }

    public function run_cron()
    {
        $run_count = intval(get_option('rizzo-cron-run-count', 0));
        update_option('rizzo-cron-run-count', ++$run_count);
        foreach ($this->rizzo_api as $key => $url) {
            $this->fetch($url, $key);
        }
    }

    public function get($name, $print = false)
    {
        $key  = 'rizzo_html_' . $name;
        $html = apply_filters($key, get_option($key, ''));

        if ($print)
            echo $html;

        return $html;
    }

    public function set($name, $html)
    {
        update_option('rizzo_html_' . $name, $html);
    }

    public function fetch($url, $option_key, array $args = array())
    {
        $args = array_merge($this->args, $args);

        $response = wp_remote_get($url, $args);

        if (is_array($response) && isset($response['response']['code']) && (int)$response['response']['code'] === 200) {

            $this->set($option_key, $response['body']);
            return true;

        }

        // $response is either an instance of WP_Error or the response code is not 200.
        do_action('rizzo-fetch-error', $url, $option_key, $args, $response);

        return false;
    }

    public function fetch_error($url, $option_key, $args, $response)
    {
        error_log('WP Rizzo fetch error $url: '        . $url);
        error_log('WP Rizzo fetch error $option_key: ' . $option_key);
        error_log('WP Rizzo fetch error $args: '       . var_export($args, true));

        if (is_wp_error($response)) {

            error_log(
                sprintf(
                    'WP Rizzo fetch WP_Error: %s %s',
                    $response->get_error_code()    ?: 'code not set',
                    $response->get_error_message() ?: 'message not set'
                )
            );

        } elseif (is_array($response)) {

            if (isset($response['response']) && is_array($response['response'])) {
                error_log(
                    sprintf(
                        'WP Rizzo fetch error HTTP status: %s %s',
                        $response['response']['code']    ?: 'code not set',
                        $response['response']['message'] ?: 'message not set'
                    )
                );
            }

        }

    }

    public function fix_logo_during_option_update($value, $old_value)
    {
        return $this->fix_logo($value);
    }

    public function fix_logo($html)
    {
        // Replace the first occurence of the URL, which should be in the logo.
        $html =  preg_replace('#' . preg_quote('http://www.lonelyplanet.com') . '#', get_home_url(), $html, 1);
        return $html;
    }

}
