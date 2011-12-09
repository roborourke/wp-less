# Enables LESS in WordPress

## Usage:

Extract the zip into your theme or plugin directory.

For use with themes add the following lines to your functions.php:

```php
<?php
require_once( 'wp-less/wp-less.php' );
if ( ! is_admin() )
    wp_enqueue_style( 'style', get_stylesheet_directory_uri() . '/style.less' );
?>
```

Any registered styles with the .less suffix will be compiled and the file URL rewritten.

You won't need a link to your main style sheet in header.php, just make sure that `wp_head()` is called in the document head.

All the standard LESS features are supported as well as @import rules anywhere within the file.

Read the LESS.js documentation here: http://lesscss.org/
For documenation specific to the PHP parser: http://leafo.net/lessphp/docs/
