<?php

require_once "private/images.php";

class Mod_boxes extends Module {

    function content($args = [])
    {
        $location_id = isset($args['location_id']) ? $args['location_id'] : 0;

        $tpl = new strontium_tpl("private/tpl/mod_boxes.html", conf()['global_marks'], false);

        $rows = db()->query_list('select id from location where '.
                                 'fullness < 100 and is_box = 1 '.
                                 'order by fullness');
        if (!is_array($rows) || !count($rows))
            return $tpl->result();

        if ($location_id) {
            $location = location_by_id($location_id);
            foreach ($location['path'] as $item)
                $tpl->assign('location_path_header', ['name' => $item['name'],
                                                      'link' => $item['url']]);
        }

        $tpl->assign('location');
        foreach ($rows as $row) {
            $location = location_by_id($row['id']);

            if ($location_id) {
                /* filtring inappropriate boxes */
                $path_chain = location_chain_by_id($row['id']);
                $ok = false;
                foreach ($path_chain as $node) {
                    if ($node['id'] == $location_id)
                        $ok = true;
                }
                if (!$ok)
                    continue;
            }

            $tpl->assign('location_row',
                         ['id' => $location['id'],
                          'percent' => $location['fullness'],
                          'link' => mk_url(['mod' => 'location',
                                            'id' => $location['id']]),
                          'description' => $location['description']]);

            foreach ($location['path'] as $node)
                $tpl->assign('location_path', ['name' => $node['name']]);
        }
        return $tpl->result();
    }


}

modules()->register('boxes', new Mod_boxes);
