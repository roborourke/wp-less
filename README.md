# Enable LESS CSS in WordPress

LESS is an abstraction layer that adds some very powerful features to CSS. It
will speed up your development process and make your life that much easier. Find
out more from the links below and then head on back.

The addon allows you to write and edit `.less` files directly and
have WordPress do the job of compiling and caching the resulting CSS. It
eliminates the extra step of having to compile the `.less` files into CSS yourself
before deploying them.

## Installation:

If you are using git to clone the repository, do the following:

    git clone --recursive git://github.com/sanchothefat/wp-less.git wp-less

If you are downloading the `.zip` or `.tar`, don't forget to download the [lessphp
dependency too](https://github.com/leafo/lessphp) and copy it into the `lessc`
folder.

## Usage:

You can either install the script as a standard plugin or use it as an include within a theme or plugin.

For use with themes add the following lines to your functions.php:

```php
<?php

// Include the class (unless you are using the script as a plugin)
require_once( 'wp-less/wp-less.php' );

// enqueue a .less style sheet
if ( ! is_admin() )
    wp_enqueue_style( 'style', get_stylesheet_directory_uri() . '/style.less' );

// you can also use .less files as mce editor style sheets
add_editor_style( 'editor-style.less' );

?>
```

Any registered styles with the `.less` suffix will be compiled and the file URL rewritten.

You won't need a link to your main style sheet in `header.php`. Just make sure
that `wp_head()` is called in the document head.

All the standard LESS features are supported as well as `@import` rules anywhere
within the file.

### Passing in variables from PHP

You can pass variables into your `.less` files using the `less_vars` hook or with the
functions defined in the PHP Interface section:

```php
<?php

// pass variables into all .less files
add_filter( 'less_vars', 'my_less_vars', 10, 2 );
function my_less_vars( $vars, $handle ) {
    // $handle is a reference to the handle used with wp_enqueue_style()
    $vars[ 'color' ] = '#000000';
    return $vars;
}

?>
```

Within your `.less` files you can use the variable as if you had declared it in the stylesheet.
For e.g.:

```css
body { color: @color; }
```

### Default variables

*There are 2 default variables* you can use without worrying about the above code:

**`@themeurl`** is the URL of the current theme directory:

```css
body { background-image: url(@{themeurl}/images/background.png); }
```

*`@lessurl`** is the URL of the enqueued LESS file (this does not change inside imported files):

```css
.plugin-title { background-image: url(@{lessurl}/images/icon.png); }
```

`@lessurl` is useful in those cases where you have .less files inside plugins or
other non theme folder locations.

It is important to use these because you can't use relative paths - the compiled CSS is
stored in the uploads folder as it is the only place you can guarantee being
able to write to in any given WordPress installation. As a result relative URLs will
break.

### PHP interface

`register_less_function()` allows you to create additional less compiler functions
for use in your stylesheet without having to touch the `lessc` class yourself.

```php
register_less_function( 'double', function( $args ) {
    list( $type, $value, $unit ) = $args;
	return array( $type, $value*2, $unit );
} );
```

`unregister_less_function()` works in a similar way but unregisters any compiler
functions passed to it by name.

```php
unregister_less_function( 'double' );
```

`add_less_var()` makes it easy to create or modify variables passed into the
compiler. Both arguments should be a string, as `lessc` will work out the type of
variable it is.

```php
add_less_var( 'brandcolour', '#ec6704' );
```

`remove_less_var()` is the inverse of `add_less_var()` and only requires the
variable name to remove.

```php
remove_less_var( 'brandcolour' );
```

## Further Reading

[Read the LESS.js documentation here](http://lesscss.org/).

Read the documentation [specific to the PHP parser here](http://leafo.net/lessphp/docs/=.


## Contributors

Big massive thanks to those whose contributions and discussion has helped to improve the plugin.

* [Tom Willmot](https://github.com/willmot)
* [Franz Josef Kaiser](https://github.com/franz-josef-kaiser)
* [Rarst](https://github.com/rarst)

## License

The software is licensed under the [MIT Licence](http://www.opensource.org/licenses/mit-license.php).
