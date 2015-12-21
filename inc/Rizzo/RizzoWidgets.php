<?php
namespace LonelyPlanet\Rizzo;

class RizzoWidgets
{
    protected $sidebar_args;
    protected $format;

    public function __construct( array $sidebar_args, $format = null )
    {
        $this->sidebar_args = $sidebar_args;
        $this->format = $format;
        $this->register_sidebar();
    }

    protected function register_sidebar()
    {
        \register_sidebar( $this->sidebar_args );
    }

    protected function get_widgets()
    {
        $sidebar = null;

        if ( isset( $this->sidebar_args['id'] ) ) {
            $sidebar = $this->sidebar_args['id'];
        }

        if ( isset( $this->sidebar_args['name'] ) ) {
            $sidebar = $this->sidebar_args['name'];
        }

        if ( ! isset( $sidebar ) ) {
            return '';
        }

        ob_start();

        dynamic_sidebar( $sidebar );

        $widgets = ob_get_clean();

        return $widgets;
    }

    public function get_output()
    {
        $widgets = $this->get_widgets();

        if ( $widgets === '' ) {
            return '';
        }

        if ( isset( $this->format ) && $this->format !== '' ) {
            $widgets = sprintf( $this->format, $widgets );
        }

        return $widgets;
    }

    public function get_html( $html )
    {
        return $html . $this->get_output();
    }

    public function __toString()
    {
        return $this->get_output();
    }
}
