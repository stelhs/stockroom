<?php
require_once '/usr/local/lib/php/database.php';
require_once '/usr/local/lib/php/common.php';
require_once '/usr/local/lib/php/strontium_tpl.php';

require_once "private/user.php";
require_once "private/url.php";
require_once "private/config.php";
require_once "private/message_box.php";
require_once "private/modules.php";

function process_query($args)
{
    if (!isset($args['method'])) {
        message_box_set("message_error", ['reason' => "field 'method' not found"]);
        header('Location: ' . mk_url());
        return;
    }

    if ($args['method'] == 'user_auth') {
        $user = user_by_login_pass($args['login'], $args['pass']);
        if (!is_array($user)) {
            message_box_set("message_auth_error");
            header('Location: ' . mk_url());
            return;
        }

        user_to_cookie($user);
        header('Location: ' . mk_url());
    }

    $user = user_by_cookie();
    if (!$user) {
        header('Location: ' . mk_url());
        return;
    }

    switch ($args['method']) {
    case 'user_logout':
        $user = user_by_cookie();
        if (!is_array($user))
            return;

        user_remove_cookie($user);
        header('Location: ' . mk_url());
        return;

    /* AJAX requests */
    case 'load_def_marks':
        echo json_encode(conf()['global_marks']);
        return;

    case 'load_tpl':
        echo file_get_contents(sprintf('%s/private/tpl/%s.html',
                                 conf()['absolute_root_path'],
                                 $args['name']));
        return;
    }

    if (!$args['mod']) {
        header('Location: ' . mk_url());
        return;
    }

    $module = modules()->get_by_name($args['mod']);
    $ret = $module->query($args);
    if (!$ret)
        return;
    header('Location: ' . $ret);
}

session_start();
$args = array_merge($_GET, $_POST);
process_query($args);
