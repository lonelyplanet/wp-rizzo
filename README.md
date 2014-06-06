# WP Rizzo

This plugin provides html content from http://rizzo.lonelyplanet.com.
It will automatically include the head section and the body footer section.
WordPress does not currently have a function like wp_footer() for the body.

To get the body header html, edit your theme and place this after you opening body tag.

```php
<?php
if (function_exists('\LonelyPlanet\WP\rizzo_body'))
    \LonelyPlanet\WP\rizzo_body();
?>
```

## Todo
Update plugin to use output buffering so that I can dynamically insert the code in the correct place without a needing to edit a theme.