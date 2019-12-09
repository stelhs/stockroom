<?php

require_once "private/images.php";


class Mod_boxes extends Module {

    function content($args = [])
    {
        $tpl = new strontium_tpl("private/tpl/mod_boxes.html", conf()['global_marks'], false);

        $rows = db()->query_list('select id from location where '.
                                 'fullness < 100 and is_box = 1 order by fullness');
        if (!is_array($rows) || !count($rows))
            return $tpl->result();

        $tpl->assign('location');
        foreach ($rows as $row) {
            $location = location_by_id($row['id']);
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
