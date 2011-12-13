# Enables LESS in WordPress

LESS is an abstraction layer that adds some very powerful features to CSS. It will speed up your development
process and make your life that much easier. Find out more from the links below and then head on back.

The addon I've written allows you to write and edit .less files directly and have WordPress do the job of compiling and
caching the resulting CSS. It eliminates the extra step of having to compile the .less files into CSS yourself before
deploying them.

## Usage:

Extract the zip into your theme or plugin directory.

For use with themes add the following lines to your functions.php:

```php
<?php

// Include the class
require_once( 'wp-less/wp-less.php' );

// enqueue a .less style sheet
if ( ! is_admin() )
    wp_enqueue_style( 'style', get_stylesheet_directory_uri() . '/style.less' );

// you can also use .less files as mce editor style sheets
add_editor_style( 'editor-style.less' );

?>
```

Any registered styles with the .less suffix will be compiled and the file URL rewritten.

You won't need a link to your main style sheet in header.php, just make sure that `wp_head()` is called in the document head.

All the standard LESS features are supported as well as @import rules anywhere within the file.

Read the LESS.js documentation here: http://lesscss.org/
For documenation specific to the PHP parser: http://leafo.net/lessphp/docs/
