<?php

require_once '/usr/local/lib/php/strontium_tpl.php';
require_once '/usr/local/lib/php/database.php';
require_once '/usr/local/lib/php/common.php';

require_once "private/config.php";
require_once "private/user.php";
require_once "private/url.php";
require_once "private/message_box.php";
require_once "private/modules.php";

function main($tpl)
{
    session_start();

    $tpl->assign(NULL, ['link_location' => mk_url(['mod' => 'location']),
                        'link_catalog' => mk_url(['mod' => 'catalog']),
                        'link_new' => mk_url(['mod' => 'object']),
                        'link_boxes' => mk_url(['mod' => 'boxes']),
                        'link_absent' => mk_url(['mod' => 'absent']),
                        'link_photos' => mk_url(['mod' => 'photos']),
                        ]);

    $mbx = message_box_get();
    if($mbx)
        $tpl->assign($mbx['block'], $mbx['data']);

    $absent_cnt = object_absent_cnt() + location_absent_cnt();
    if ($absent_cnt) {
        $tpl->assign('absent_cnt', ['cnt' => $absent_cnt]);
    }

    $photos = images_by_obj_type('not_assigned');
    $photos_cnt = count($photos);
    if ($photos_cnt) {
        $tpl->assign('photos_cnt', ['cnt' => $photos_cnt]);
    }

    $user = user_by_cookie();
    if (!$user) {
        $tpl->assign('user_auth');
        return;
    }

    $tpl->assign('user_logout', ['link_logout' => mk_url(['method' => 'user_logout'], 'query')]);

    $mod_name = "search";
    if(isset($_GET['mod']))
       $mod_name = $_GET['mod'];

    $content = modules()->mod_content($mod_name, $_GET);
    $tpl->assign('module', ['content' => $content]);

}

$tpl = new strontium_tpl("private/tpl/skeleton.html", conf()['global_marks'], false);
main($tpl);
echo $tpl->result();
header('Cache-Control: no-store');

