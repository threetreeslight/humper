<?php

/**
 * rest api helper
 */

/**
 * get, delete method form or ancher generate helper
 *
 * @param string $name
 * @param string $path
 * @param string $method
 * @param string $token
 * @return string
 */
function link_to($name, $path, $_options = array()) {
    extract($_options);

    $method = isset($method) ? strtoupper($method) : 'GET';
    $classes = "class='".(isset($class) ? $class : null)."'";

    if( 'GET' === $method ) {
        return "<a href='$path' $classes>$name</a>";
        # return '<a href="' . $path . '"'. $class .' >' . $name . '</a>';
    } else {
        return <<<EOT
<form action='$path' method='post' $classes>
    <input type='hidden' name='_method' value='$method'>
    <input type='hidden' name='_token' value="$_token">
    <input type='submit' value='$name'>
</form>
EOT;
    }
}

