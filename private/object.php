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

function object_remove($obj_id)
{
    $photos = images_by_obj_id('objects', $obj_id);
    if ($photos)
        foreach ($photos as $photo)
            $photo->remove();
    $obj = object_by_id($obj_id);
    db()->query('delete from objects where id = %d', $obj_id);
    db()->query('delete from withdrawal_list where obj_id = %d', $obj_id);
}

function object_by_id($object_id)
{
    return db()->query('select * from objects where id = %d', (int)$object_id);
}

function object_edit($obj_id, $catalog_id, $location_id, $name, $description = "", $attrs_text = "")
{
    $obj = object_by_id($obj_id);

    $attrs = parse_attrs($attrs_text);
    $attrs_text = attrs_to_text($attrs);
    $update_data = ['name' => $name,
                    'description' => $description,
                    'attrs' => $attrs_text,
                    'catalog_id' => $catalog_id,
                    'location_id' => $location_id];

    return db()->update('objects', $obj_id, $update_data);
}

function objects_by_location($node_id)
{
    return db()->query_list('select * from objects where location_id = %d '.
                            'order by name asc', $node_id);
}

function objects_by_catalog($cat_id)
{
    return db()->query_list('select * from objects where catalog_id = %d '.
                            'order by name asc', $cat_id);
}

function object_attrs_match($object_attrs, $search_attrs)
{
    if (!count($search_attrs))
        return true;

    $i = 0;
    $matched = 0;
    foreach ($search_attrs as $attr) {
        $i ++;
        $match = false;

        $key = $attr[0];
        $val = $attr[1];
        $o_val = NULL;

        // grouping attributes by arrtibute name
        $group_obj_attrs = [];
        foreach ($object_attrs as $o_attr) {
            if (!isset($group_obj_attrs[$o_attr[0]]))
                $group_obj_attrs[$o_attr[0]] = [];
            $group_obj_attrs[$o_attr[0]][] = $o_attr[1];
        }

        foreach ($group_obj_attrs as $o_attr => $o_vals) {
            if ($o_attr != $key)
                continue;

            foreach ($o_vals as $o_val) {
                if (match_range($o_val, $val)) {
                    $match = true;
                    break;
                }
            }
        }

        if ($match)
            $matched ++;
    }

    if ($i == $matched)
        return true;

    return false;
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

    preg_match('/^\d*(?:\.\d+)?/i', $query, $m);
    if (count($m) <= 1)
        return strstr($i, $query) != NULL;

    return $i == $m[0];
}

function object_absent_cnt()
{
    $objects = db()->query_list('select id from objects where absent > 0');
    return count($objects);
}

function withdrawal_list()
{
    return db()->query_list('select * from withdrawal_list');
}