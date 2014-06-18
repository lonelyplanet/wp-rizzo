# WP Rizzo

This plugin provides html content from http://rizzo.lonelyplanet.com/.
It will automatically include the head section, body header, and footer section.

It hooks into the wp_head and wp_footer functions to output the HTML content.

Since WordPress doesn't have a function like wp_footer for the body, I had to use
output buffering to automatically insert the body header into the html.

## Features

I've created a settings page where you can change the following:

* API endpoint URLs
* API connection timeout limit (seconds)
* WP cron interval (seconds)
* Which content to auto insert into the HTML.

### Output Buffering

If you don’t want to use output buffering, uncheck "Insert Body Content", and place this in your theme after the &lt;body&gt; tag:

```php
<?php
if (function_exists('\LonelyPlanet\Rizzo\body'))
    \LonelyPlanet\Rizzo\body();
?>
```