<?php


function location_by_id($location_id)
{
    $root_location =  ['id' => 0,
                   'parent_id' => 0,
                   'key_name' => 'root',
                   'name' => 'root',
                   'description' => '',
                   'user_id' => 0];
    $root_path_item = ['name' => ' / ', 'url' => mk_url(['mod' => 'location'])];

    if ($location_id == 0) {
        $root_location['path'] = [$root_path_item];
        return $root_location;
    }

    $location = db()->query('select * from location where id = %d', $location_id);
    $locations = location_chain_by_id($location_id);

    $path = [];
    foreach ($locations as $sub_location)
        $path[] = ['name' => $sub_location['name'].' / ',
                   'url' => mk_url(['mod' => 'location',
                                    'id' => $sub_location['id']])];
    $location['path'] = $path;
    return $location;
}

function location_chain_by_id($location_id)
{
    $list = [];
    while (true) {
        $location = db()->query('select * from location where id = %d', $location_id);
        if (!$location)
            break;
        $list[] = $location;

        $location_parent = db()->query('select * from location ' .
                                   'where id = %d', $location['parent_id']);
        if (!$location_parent || !is_array($location_parent))
            break;

        $location_id = $location_parent['id'];
    }
    $list[] = ['name' => '', 'id' => '0'];
    return array_reverse($list);
}

function location_absent_cnt()
{
    $locations = db()->query_list('select id from location where is_absent=1');
    return count($locations);
}
