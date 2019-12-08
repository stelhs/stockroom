<?php
ini_set('error_reporting', E_ERROR);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);


define("EINVAL", -1); // Ошибка во входных аргументах
define("EBASE", -2); // Ошибка связи с базой
define("ESQL", -3); // Не корректный SQL запрос
define("ENOTUNIQUE", -4); // Ошибка добавления в базу, если такая запись уже существует

function conf()
{
    $path = parse_json_config('private/.path.json');
    $http_root_path = $path['http_root_path']; // Внутренний путь к файлам
    $absolute_root_path = $path['absolute_root_path']; // Абсолютный пусть к файлам

    return ['global_marks' => ['http_root' => $http_root_path,
                               'http_css' => $http_root_path.'css/',
                               'http_img' => $http_root_path.'i/',
                               'http_js' => $http_root_path.'js/',
                               'time' => time(),
                               'query_url' => $http_root_path.'query.php'],
            'http_root_path' => $http_root_path,
            'absolute_root_path' => $absolute_root_path,
            'clean_url_enable' => false];
}

function conf_db()
{
    static $config = NULL;
    if (!is_array($config))
        $config = parse_json_config('private/.database.json');

    return $config;
}

?>
