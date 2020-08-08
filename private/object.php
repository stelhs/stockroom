<?php

function parse_attrs($text_attrs)
{
    $rows = string_to_rows($text_attrs);
    if (!count($rows))
        return NULL;

    $attrs = [];
    foreach ($rows as $row) {
        $parts = string_to_words($row, ':');
        if (count($parts) != 2)
            continue;
        $key = trim($parts[0]);
        $val = trim($parts[1]);
        if (!$val)
            continue;

        $attrs[] = [$key, $val];
    }
    return $attrs;
}

function attrs_to_text($attrs)
{
    $text = "";
    foreach ($attrs as $attr) {
        $key = $attr[0];
        $val = $attr[1];
        $text .= sprintf("%s: %s\n", $key, $val);
    }
    return $text;
}

function get_existed_attrs()
{
    static $list_attrs = [];

    if (count($list_attrs))
        return $list_attrs;

    $list_attrs_keys = [];
    $rows = db()->query_list('select * from objects where attrs != ""');
    foreach ($rows as $row) {
        $attrs = parse_attrs($row['attrs']);
        if (!count($attrs))
            continue;

        foreach ($attrs as $attr) {
            $key = $attr[0];
            $val = $attr[1];
            if (isset($list_attrs_keys[$key]))
                continue;
            $list_attrs_keys[$key] = $val;
        }
    }
    $list_attrs = array_keys($list_attrs_keys);
    return $list_attrs;
}

function object_add($catalog_id, $location_id, $name, $description = "", $number = 1, $attrs_text = "")
{
    $attrs = parse_attrs($attrs_text);
    $attrs_text = attrs_to_text($attrs);
    return db()->insert('objects', ['name' => $name,
                                    'description' => $description,
                                    'attrs' => $attrs_text,
                                    'number' => (int)$number,
                                    'catalog_id' => $catalog_id,
                                    'location_id' => $location_id,
                                    'user_id' => user_by_cookie()['id']]);
}

function object_by_id($object_id)
{
    return db()->query('select * from objects where id = %d', (int)$object_id);
}

function object_edit($obj_id, $catalog_id, $location_id, $name, $description = "", $number = 1, $attrs_text = "")
{
    $obj = object_by_id($obj_id);
    $absent = 0;
    if ($number < $obj['absent'])
        $absent = $number;

    $attrs = parse_attrs($attrs_text);
    $attrs_text = attrs_to_text($attrs);
    return db()->update('objects', $obj_id, ['name' => $name,
                                             'description' => $description,
                                             'attrs' => $attrs_text,
                                             'catalog_id' => $catalog_id,
                                             'location_id' => $location_id,
                                             'number' => $number,
                                             'absent' => $absent]);
}

function objects_by_location($node_id)
{
    return db()->query_list('select * from objects where location_id = %d '.
                            'order by id asc', $node_id);
}

function objects_by_catalog($cat_id)
{
    return db()->query_list('select * from objects where catalog_id = %d', $cat_id);
}

function print_absent_objects($tpl)
{
    $objects = db()->query_list('select * from objects where absent > 0');
    if (!count($objects))
        return;
    $tpl->assign('absent_objects');
    foreach ($objects as $obj) {
        $img_url = '';
        $photos = images_by_obj_id('objects', $obj['id']);
        if (count($photos)) {
            $photo = $photos[0];
            $img_url = $photo->url('list');
        }
        $row = ['id' => $obj['id'],
                'name' => $obj['name'],
                'description' => str_replace("\n", '<br>', $obj['description']),
                'link_to_object' => mk_url(['mod' => 'object', 'id' => $obj['id']]),
                'img' => $img_url];
        $tpl->assign('absent_object_row', $row);
        if ($obj['absent'] > 1)
            $tpl->assign('absent_object_count', ['count' => $obj['absent']]);
    }
}

function object_attrs_match($object_attrs, $search_attrs)
{
    foreach ($search_attrs as $attr) {
        $key = $attr[0];
        $val = $attr[1];
        $o_val = NULL;
        foreach ($object_attrs as $o_attr) {
            if ($o_attr[0] == $key) {
                $o_val = $o_attr[1];
                break;
            }
        }
        if ($o_val === NULL)
            return false;

        if (!match_range($o_val, $val))
            return false;
    }
    return true;
}

function match_range($i, $query)
{
    preg_match('/\s*<\s*([\d\.]+)/i', $query, $m);
    if ($m && isset($m[1]))
        return $i <= $m[1];

    preg_match('/\s*>\s*([\d\.]+)/i', $query, $m);
    if ($m && isset($m[1]))
        return $i >= $m[1];

    preg_match('/([\d\.]+)\s*-\s*([\d\.]+)/i', $query, $m);
    if ($m && isset($m[2]))
        return ($i >= $m[1] && $i <= $m[2]);

    preg_match('/^[\d]/i', $query, $m);
    if (!$m)
        return strstr($i, $query) != NULL;

    return $i == $m[0];
}
