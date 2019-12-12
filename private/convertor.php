<?php

require_once '/usr/local/lib/php/database.php';
require_once '/usr/local/lib/php/common.php';
require_once '/usr/local/lib/php/os.php';
require_once 'images.php';

function conf_db()
{
    static $config = NULL;
    if (!is_array($config))
        $config = parse_json_config('.database.json');

    return $config;
}

function conf()
{
    return ['http_root_path' => '/stockroom/',
            'absolute_root_path' => '/storage/www/stockroom/'];
}

function process_dir($path, $parent_id) {
    $l = scandir($path);

    foreach ($l as $f) {
        if ($f[0] == '.')
            continue;
        if ($f == 'temp')
            continue;

        if(is_dir($path.$f)) {
            $loc_id = db()->insert('location',
                                   ['parent_id' => $parent_id,
                                    'name' => $f,
                                    'fullness' => '100',
                                    'user_id' => 1]);
            process_dir($path.$f.'/', $loc_id);
        }

        if(is_file($path.$f)) {
            $obj_id = db()->insert('objects', ['name' => $f,
                                               'number' => 1,
                                               'catalog_id' => 0,
                                               'location_id' => $parent_id,
                                               'user_id' => 1]);
            $hash = image_upload_local($path.$f, 'objects', $obj_id);
            $photo = image_by_hash($hash);
            $photo->resize('mini', ['w' => 1000]);
            $photo->resize('list', ['w' => 300]);

        }

    }
}

function convert_base()
{
    return 0;
    db()->query('delete from catalog');
    db()->query('ALTER TABLE catalog AUTO_INCREMENT = 1');

    db()->query('delete from images');
    db()->query('ALTER TABLE images AUTO_INCREMENT = 1');

    db()->query('delete from location');
    db()->query('ALTER TABLE location AUTO_INCREMENT = 1');

    db()->query('delete from objects');
    db()->query('ALTER TABLE objects AUTO_INCREMENT = 1');

    run_cmd('rm -f /storage/www/stockroom/i/obj/*');

    $gd_path = '/storage/google-disk/';
    process_dir($gd_path, 0);
}


function convert_metal_base()
{
    $c = file_get_contents("source.txt");
    $rows = split_string_by_separators($c, "\n");
    $list = [];
    foreach ($rows as $row) {
        $row = trim($row);
        if (!$row)
            continue;

//        preg_match('/([\w\s]+)\s([\d\w\.]+)\s+длина\s+(\d+)/ui', $row, $mathed);
//        preg_match('/([\w\s]+)\s([\d\w\.]+)x(\d+)/ui', $row, $mathed);
        preg_match('/([\w\s]+)\s([\d\w\.]+)\s+(\d+)/ui', $row, $mathed);
        $list[trim($mathed[1])][$mathed[2]][] = $mathed[3];
    }
    dump($list);
    return;
    foreach ($list as $cat_name => $sub_list) {
        $cat_id = db()->insert('catalog', ['name' => $cat_name,
                                           'parent_id' => 101,
                                           'user_id' => 1]);
        if ($cat_id < 1) {
            printf("catalog create fail\n");
            return;
        }
        foreach ($sub_list as $size_name => $lengths_list) {
            $sub_cat_id = db()->insert('catalog', ['name' => $size_name,
                                                   'parent_id' => $cat_id,
                                                   'user_id' => 1]);
            if ($sub_cat_id < 1) {
                printf("sub catalog create fail\n");
                return;
            }

            foreach ($lengths_list as $length) {
                db()->insert('objects', ['name' => sprintf('%s %s - %s', $cat_name, $size_name, $length),
                                         'catalog_id' => $sub_cat_id,
                                         'location_id' => 117,
                                         'user_id' => 1]);
            }
        }
    }

}

function main()
{
    set_time_limit(0);

//    convert_metal_base();
}


main();