<?php

require_once "private/images.php";

class Mod_boxes extends Module {

    function content($args = [])
    {
        $location_id = isset($args['location_id']) ? $args['location_id'] : 0;

        $psize1 = $psize2 = $psize3 = $cd = $ch = $bd = 0;
        if ($args['parallelepiped_size1'] && $args['parallelepiped_size2'] && $args['parallelepiped_size3']) {
            $psize1 = (float)$args['parallelepiped_size1'];
            $psize2 = (float)$args['parallelepiped_size2'];
            $psize3 = (float)$args['parallelepiped_size3'];
        } else if ($args['cylinder_diameter'] && $args['cylinder_height']) {
            $cd = (float)$args['cylinder_diameter'];
            $ch = (float)$args['cylinder_height'];
        } else if ($args['ball_diameter']) {
            $bd = (float)$args['ball_diameter'];
        }

        $vol = (float)$args['volume'];
        $min_capacity = $vol * 5 * 1000;
        $max_capacity = $vol * 100 * 1000;

        $tpl = new strontium_tpl("private/tpl/mod_boxes.html", conf()['global_marks'], false);
        $tpl->assign(NULL, ['form_url' => mk_url(),
                            'mod' => $this->name,
                            'location_id' => $location_id,
                            'parallelepiped_size1' => $psize1 ? $psize1 : "",
                            'parallelepiped_size2' => $psize2 ? $psize2 : "",
                            'parallelepiped_size3' => $psize3 ? $psize3 : "",
                            'cylinder_diameter' => $cd ? $cd : "",
                            'cylinder_height' => $ch ? $ch : "",
                            'ball_diameter' => $bd ? $bd : "",
                            'volume' => $vol,
                            'search_text' => $args['search_text'],
                            ]);

        $rows = db()->query_list('select id from location where '.
                                 'fullness < 100 and is_box = 1 and '.
                                     'volume > %s and volume < %s and '.
                                     'free_volume > %s and '.
                                     '(name like "%%%s%%" or description like "%%%s%%") '.
                                 'order by free_volume desc',
                                 $min_capacity, $max_capacity, $vol * 1000,
                                 $args['search_text'], $args['search_text']);

        if (!is_array($rows) || !count($rows))
            return $tpl->result();

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

            if ($psize1 && $psize2 && $psize3) {
                $pmaxsize = $this->max_size($psize1, $psize2, $psize3);
                $lmaxsize = $this->max_size($location['size1'],
                                            $location['size2'],
                                            $location['size3']);
                if ($lmaxsize < $pmaxsize)
                    continue;
            }

            $img_url = "";
            $photos = images_by_obj_id('locations', $location['id']);
            if (count($photos)) {
                $photo = $photos[0];
                $img_url = $photo->url('list');
                if (!$img_url) {
                    $photo->resize('list', ['w' => 300]);
                    $img_url = $photo->url('list');
                }
            }

            $tpl->assign('location_row',
                         ['id' => $location['id'],
                          'percent' => $location['fullness'],
                          'location_volume' => $location['volume'] / 1000,
                          'location_free_volume' => $location['free_volume'] / 1000,
                          'location_size1' => $location['size1'],
                          'location_size2' => $location['size2'],
                          'location_size3' => $location['size3'],
                          'link' => mk_url(['mod' => 'location',
                                            'id' => $location['id']]),
                          'location_img' => $img_url,
                          'name' => $location['name'],
                          'description' => $location['description']]);

            foreach ($location['path'] as $node)
                $tpl->assign('location_path', ['name' => $node['name']]);
        }
        return $tpl->result();
    }

    function max_size($size1, $size2, $size3)
    {
        $sizes = [$size1, $size2, $size3];
        $max_size = 0;
        foreach ($sizes as $size) {
            if ($size > $max_size)
                $max_size = $size;
        }
        return $max_size;
    }

}

modules()->register('boxes', new Mod_boxes);
