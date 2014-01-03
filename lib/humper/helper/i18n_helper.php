<?php

function i18n_path_info($path_info, $current_lang = null){

    preg_match('/\/([^\/]*)\/.*/', $path_info, $matches);

    if(isset($current_lang) && isset($matches[1]) && $matches[1] === $current_lang) {
        $path_info = substr($path_info, strlen($current_lang) + 1);
    }

    return $path_info;

}
