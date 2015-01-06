<?php
namespace LonelyPlanet\Func;

function number_in_range($num, $min, $max)
{
    return min(max($min, $num), $max);
}

function html_attr(array $attributes = array())
{
    $attr = array();

    $binary_attr = array('required', 'checked', 'selected');

    foreach($attributes as $name => $value) {
        if (in_array($name, $binary_attr)) {
            if ($value)
                $attr[] = $name;
            continue;
        }
        $attr[] = $name . '="' . esc_attr($value) . '"';
    }

    return implode(' ', $attr);
}

function timer($which, $time = null)
{
    static $timer = array();
    $timer[$which] = isset($time) ? $time : time();
    return $timer;
}
