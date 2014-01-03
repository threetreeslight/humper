<?php

/**
 * html escape
 *
 * @param string|array $string
 * @return string
 */
function h($string) {
    if(is_array($string)){
        return array_map('h', $string);
    }
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
