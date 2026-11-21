<?php


function mk_url($args = [], $entry = '')
{
    $url = conf()['http_root_path'];
    if ($entry == 'query')
        $url .= "query.php?";
    else
        $url .= "index.php?";
        
    $separator = "";
    foreach ($args as $key => $value) {
        $url .= $separator;
        $url .= $key . "=" .$value;
        $separator = "&";   
    }
    return $url;
}



