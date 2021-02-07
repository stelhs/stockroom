<?php


function catalog_by_id($catalog_id)
{
    $root_catalog =  ['id' => 0,
                   'parent_id' => 0,
                   'key_name' => 'root',
                   'name' => 'root',
                   'description' => '',
                   'user_id' => 0];
    $root_path_item = ['name' => ' / ', 'url' => mk_url(['mod' => 'catalog'])];

    if ($catalog_id == 0) {
        $root_catalog['path'] = [$root_path_item];
        return $root_catalog;
    }

    $catalog = db()->query('select * from catalog where id = %d', $catalog_id);
    $catalogs = catalog_chain_by_id($catalog_id);

    $path = [];
    foreach ($catalogs as $sub_catalog)
        $path[] = ['name' => $sub_catalog['name'].' / ',
                   'id' => $sub_catalog['id'],
                   'url' => mk_url(['mod' => 'catalog',
                                    'id' => $sub_catalog['id']])];
    $catalog['path'] = $path;
    return $catalog;
}

function catalog_chain_by_id($catalog_id)
{
    $list = [];
    while (true) {
        $catalog = db()->query('select * from catalog where id = %d', $catalog_id);
        if (!$catalog)
            break;
        $list[] = $catalog;

        $catalog_parent = db()->query('select * from catalog ' .
                                      'where id = %d', $catalog['parent_id']);
        if (!$catalog_parent || !is_array($catalog_parent))
            break;

        $catalog_id = $catalog_parent['id'];
    }
    $list[] = ['name' => '', 'id' => '0'];
    return array_reverse($list);
}

function catalog_is_child($cat_id, $parent_cat_id)
{
    if ($cat_id == $parent_cat_id)
        return true;

    while (true) {
        $row = db()->query('select parent_id from catalog where id = %d', $cat_id);
        if (!($row && isset($row['parent_id'])))
            break;

        if ($row['parent_id'] == $parent_cat_id)
            return true;
        $cat_id = $row['parent_id'];
    }
    return false;
}

function catalog_list_by_text($text, $cat_id = NULL)
{
    if (!$text)
        return NULL;

    $rows = db()->query_list('select id from catalog where '.
                             'name LIKE "%%%s%%" or '.
                             'description LIKE "%%%s%%" ',
                             $text, $text);

    if (!is_array($rows) || !count($rows))
        return NULL;

    $list = [];
    foreach ($rows as $row) {
        if (!$cat_id || catalog_is_child($row['id'], $cat_id))
            $list[] = catalog_by_id($row['id']);
    }
    return count($list) ? $list : NULL;
}

