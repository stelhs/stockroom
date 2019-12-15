<?php


function object_add($catalog_id, $location_id, $name, $description, $number)
{
    return db()->insert('objects', ['name' => $name,
                                    'description' => $description,
                                    'number' => (int)$number,
                                    'catalog_id' => $catalog_id,
                                    'location_id' => $location_id,
                                    'user_id' => user_by_cookie()['id']]);
}

function object_by_id($object_id)
{
    return db()->query('select * from objects where id = %d', (int)$object_id);
}

function object_edit($obj_id, $catalog_id, $location_id, $name, $description, $number)
{
    return db()->update('objects', $obj_id, ['name' => $name,
                                             'description' => $description,
                                             'catalog_id' => $catalog_id,
                                             'location_id' => $location_id,
                                             'number' => $number]);
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
    $objects = db()->query_list('select * from objects where is_absent=1');
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
    }
}

