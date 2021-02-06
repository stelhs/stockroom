<?php

require_once "private/images.php";

class Mod_absent extends Module {

    function content($args = [])
    {
        $tpl = new strontium_tpl("private/tpl/mod_absent.html", conf()['global_marks'], false);
        $tpl->assign(NULL);

        $objects = db()->query_list('select * from objects where absent > 0');
        if (count($objects)) {
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

        $locations = db()->query_list('select * from location where is_absent=1');
        if (count($locations)) {
            $tpl->assign('absent_locations');
            foreach ($locations as $row) {
                $location = location_by_id($row['id']);
                $tpl->assign('absent_location_row',
                             ['link_to_location' => mk_url(['mod' => 'location',
                                                            'id' => $location['id']])]);
                foreach ($location['path'] as $node)
                    $tpl->assign('absent_location_path', ['name' => $node['name']]);
            }
        }

        return $tpl->result();
    }


}

modules()->register('absent', new Mod_absent);
