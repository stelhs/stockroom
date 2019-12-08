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
    return db()->query_list('select * from objects where location_id = %d', $node_id);
}

function objects_by_catalog($cat_id)
{
    return db()->query_list('select * from objects where catalog_id = %d', $cat_id);
}