<?php 

/**
 * Подготовка к выводу всплывающего окна с сообщением
 * @param $block название блока из файла message_boxes.html с шаблоном сообщения
 * @param $data массив меток для шаблона с сообщением
 */
function message_box_set($block, $data = [])
{
    $_SESSION['msg_win'] = ['name' => $block, 'data' => $data];
}

/**
 * Функция возвращает данные всплывающего окна,
 * если ранее была запущена функция message_box_display().
 * Используется в index.php
 */
function message_box_get()
{
    $block = $_SESSION['msg_win']["name"];
    $data = $_SESSION['msg_win']["data"];
    unset($_SESSION['msg_win']);
    return ['block' => $block, 'data' => $data];
}


function message_box_err($msg) {
    message_box_set("message_error",
                    ['reason' => $msg]);
}

function message_box_ok($msg) {
    message_box_set("message_success",
                    ['reason' => $msg]);
}