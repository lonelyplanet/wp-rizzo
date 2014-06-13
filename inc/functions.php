<?php
namespace LonelyPlanet\Func;

function number_in_range($num, $min, $max)
{
    return min(max($min, $num), $max);
}

function html_attr(array $attributes = array())
{
    $attr = array();
    foreach($attributes as $name => $value) {
        $attr[] = $name . '="' . esc_attr($value) . '"';
    }
    return implode(' ', $attr);
}