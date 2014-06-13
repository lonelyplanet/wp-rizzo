<?php
namespace LonelyPlanet\WP\Rizzo;

/*
function register_settings()
{
    register_setting('rizzo', 'use_head', 'boolval'); 
    register_setting('rizzo', 'use_body', 'boolval'); 
    register_setting('rizzo', 'use_footer', 'boolval');
}


function add_to_menu()
{
    add_options_page('Rizzo Settings', 'Rizzo Settings', 'edit_theme_options', 'rizzo-settings', __NAMESPACE__ . '\settings_page');
    add_action( 'admin_init', __NAMESPACE__ . '\register_settings' );
}
add_action('admin_menu', __NAMESPACE__ . '\add_to_menu');


function settings_page()
{
    echo '<h1>Rizzo Settings</h1>';
}
*/

class RizzoSettingsPage {

    private $options;
    private $menu_slug;
    private $endpoints;

    public function __construct()
    {
        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_init', array( $this, 'admin_init' ) );

        $this->options = get_option('rizzo');

        $this->endpoints = array(
            'head_endpoint'   => 'Head Endpoint',
            'body_endpoint'   => 'Body Endpoint',
            'footer_endpoint' => 'Footer Endpoint'
        );
    }

    /**
     * Add options page
     */
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

    /**
     * Options page callback
     */
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

    /**
     * Register and add settings
     */
    public function admin_init()
    {        
        register_setting('rizzo', 'rizzo', array($this, 'sanitize'));

        add_settings_section(
            'rizzo-api-endpoints',
            'API Endpoints',
            array( $this, 'print_api_endpoint_info' ),
            $this->menu_slug
        );  

        foreach ($this->endpoints as $endpoint => $label) {
            // add_settings_field( $id, $title, $callback, $page, $section, $args );
            add_settings_field(
                $endpoint,
                $label,
                array($this, 'input'),
                $this->menu_slug,
                'rizzo-api-endpoints',
                array(
                    'label_for'  => $endpoint,
                    'value'      => $this->options[$endpoint],
                    'attributes' => array(
                        'id'    => $endpoint,
                        'class' => 'widefat',
                        'type'  => 'url'
                    )
               )
            );
        }

        add_settings_field(
            'timeout',
            'Connection Timeout',
            array($this, 'input'),
            $this->menu_slug,
            'rizzo-api-endpoints',
            array(
                'label_for'  => 'timeout',
                'attributes' => array(
                    'id'    => 'timeout',
                    'type'  => 'number',
                    'value' => $this->options['timeout'],

                )
            )
        );
    }

    public function sanitize( $input )
    {
        $new_input = array();

        foreach ($this->endpoints as $endpoint => $label) {
            if (isset($input[$endpoint]) && ! empty($input[$endpoint])) {
                $new_input['head_endpoint'] = filter_var($input[$endpoint], FILTER_VALIDATE_URL);
            }
        }

        if (isset($input['timeout'])) {
            $new_input['timeout'] = \LonelyPlanet\Func\number_in_range($input['timeout'], 0, 60);
        }

        return array_filter($new_input);
    }

    public function print_api_endpoint_info()
    {
        echo 'Enter your custom API endoints below. If you don&#8217;t fill this in, the default endpoints will be used.';
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

        if ( isset($args['value']) && ! isset($args['attributes']['value'])) {
            $args['attributes']['value'] = $args['value'];
        }

        $required = isset($args['attributes']['required']) && $args['attributes']['required'] == true ? 'required' : '';
        
        $attributes = \LonelyPlanet\Func\html_attr($args['attributes']);

        // var_dump($args, $attributes);

        printf(
            '<input %1$s name="rizzo[%2$s]" %3$s />',
            $attributes,
            $args['attributes']['id'],
            $required
        );
    }
}

$rizzo_settings_page = new RizzoSettingsPage();
