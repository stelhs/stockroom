<?php

function user_by_login_pass($login, $pass)
{
    $query = sprintf('SELECT * FROM users WHERE `login` = "%s" ' .
                     'AND `pass` = sha("%s")', $login, $pass);
    return db()->query($query);
}

function user_by_hash($hash)
{
    $query = sprintf('SELECT * FROM users WHERE `hash` = "%s"', $hash);
    return db()->query($query);
}

function user_by_id($id)
{
    $query = sprintf('SELECT * FROM users WHERE `id` = %d', (int)$id);
    return db()->query($query);
}

function user_to_cookie($user)
{
    setcookie('user', $user['hash'], 
              time() + 60 * 60 * 24 * 365 * 3, '/');
}

function user_remove_cookie($user)
{
    setcookie('user', $user['hash'], 
              time() - 3600, '/');
}

function user_by_cookie()
{
    if(!isset($_COOKIE["user"]))
        return NULL;

    return user_by_hash($_COOKIE["user"]);
}
