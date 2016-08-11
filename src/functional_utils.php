<?php

function prevent_output($func) {
    ob_start();
    $result = $func();
    ob_end_clean();
    return $result;
}

//Supports up to 9 arguments. This is a 100% arbitrary number.
//Better ways of doing this exist in PHP 5.6+.
function bind($func) {
    $args = array_slice(func_get_args(), 1);
    return function() use ($func, $args) {
        call_user_func_array($func, $args);
    };
}