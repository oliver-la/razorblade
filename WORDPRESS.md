# Usage in WordPress

There are probably more elegant ways to solve this, but so far it worked for me:

````php
require_once get_template_directory() . '/inc/class-razor-blade.php';

$blade = new RazorBlade(get_template_directory(), ['/', '/views', '/views/components'], '/.cache');

add_filter('template_include', function ($path) {
    global $blade;
    $blade->view(basename($path, '.php'));
    return;
}, 10, 1);
````
