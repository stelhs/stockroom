<?php

require_once "private/location.php";
require_once "private/images.php";


class Mod_location extends Module {

    function content($args = [])
    {
        $location_id = isset($args['id']) ? $args['id'] : 0;
        $location = location_by_id($location_id);

        $tpl = new strontium_tpl("private/tpl/mod_location.html", conf()['global_marks'], false);

        if (!$location) {
            $tpl->assign('no_location', ['location_id' => $location_id]);
            return $tpl->result();
        }

        foreach ($location['path'] as $item)
            $tpl->assign('location_path', ['name' => $item['name'],
                                           'link' => $item['url']]);


        $size1 = (int)$location['size1'];
        $size2 = (int)$location['size2'];
        $size3 = (int)$location['size3'];
        $fullness = (int)$location['fullness'];
        $volume = ($size1 / 100 * $size2 / 100 * $size3 / 100);
        $free_volume = $volume * (100 - $fullness) / 100;

        $tpl->assign('location', ['location_id' => $location_id,
                                  'location_name' => $location['name'],
                                  'location_description' => $location['description'],
                                  'location_size1' => $size1 ? $size1 : "",
                                  'location_size2' => $size2 ? $size2 : "",
                                  'location_size3' => $size3 ? $size3 : "",
                                  'location_fullness' => $fullness,
                                  'location_volume' => round($volume, 3),
                                  'location_free_volume' => round($free_volume, 3),
                                  'form_url' => mk_url(['mod' => $this->name], 'query'),
                                  'link_delete' => mk_url(['mod' => $this->name,
                                                           'method' => 'remove_location',
                                                           'location_id' => $location_id], 'query'),
                                  'link_add_object' => mk_url(['mod' => 'object', 'location_id' => $location_id]),
                                  'box_checked' => ($location['is_box'] ? 'checked' : ''),
                                  'free_boxes_link' => mk_url(['mod' => 'boxes',
                                                               'location_id' => $location_id])]);

        $photos = images_by_obj_type('not_assigned');
        if (count($photos)) {
            $tpl->assign('not_assigned_photo_expand');
            foreach ($photos as $photo) {
                $tpl->assign('not_assigned_photo',
                             ['img' => $photo->url('mini'),
                              'photo_hash' => $photo->hash(),
                              'img_orig' => $photo->url()]);
            }
        }

        if ($location['is_absent']) {
            $tpl->assign('return_back');
        } else
            $tpl->assign('take_away');

        $photos = images_by_obj_id('locations', $location_id);
        foreach ($photos as $photo) {
            $link_remove = mk_url(['method' => 'remove_photo',
                                   'photo_hash' => $photo->hash(),
                                   'mod' =>  $this->name,
                                   'location_id' => $location_id], 'query');

            $tpl->assign('location_photo', ['img' => $photo->url('mini'),
                                            'img_orig' => $photo->url(),
                                           'link_remove' => $link_remove]);
        }

        $for_pasting_loc_id = $this->cutted_location_id();
        if ($for_pasting_loc_id) {
            $plocation = location_by_id($for_pasting_loc_id);
            $tpl->assign('location_clipboard',
                         ['id' => $plocation['id'],
                          'name' => $plocation['name'],
                          'link_reset' => mk_url(['mod' => $this->name,
                                                  'method' => 'reset_clipboard',
                                                  'id' => $location_id],
                                                 'query')]);

            foreach ($plocation['path'] as $item)
                $tpl->assign('clipboard_location_path', ['name' => $item['name'],
                                                         'link' => $item['url']]);

            if ($for_pasting_loc_id != $location_id)
                $tpl->assign('past_location', ['past_location_name' => $plocation['name'],
                                               'location_name' => $location['name']]);
            else
                $tpl->assign('past_location_blocked');
        }



        $sub_locations = db()->query_list('select * from location where parent_id = %d order by name asc',
                                      $location_id);
        if ($sub_locations) {
            $tpl->assign('sub_locations_list', ['total_number' => count($sub_locations)]);
            foreach($sub_locations as $sub_location)
            {
                $user = user_by_id($sub_location['user_id']);

                $row = db()->query('select count(id) as number from objects '.
                                   'where location_id=%d', $sub_location['id']);
                $objects_number = $row['number'] ? $row['number'] : '';

                $row = db()->query('select count(id) as number from location '.
                                   'where parent_id=%d', $sub_location['id']);
                $location_number = $row['number'] ? $row['number'] : '';

                $row = ['id' => $sub_location['id'],
                        'name' => $sub_location['name'],
                        'description' => $sub_location['description'],
                        'added_date' => $sub_location['created'],
                        'user' => $user['login'],
                        'link' => mk_url(['mod' => $this->name,
                                          'id' => $sub_location['id']]),
                        'objects_number' => $objects_number,
                        'location_number' => $location_number];

                if ($sub_location['is_box'])
                    $row['fullness'] = $sub_location['fullness'].'%';

                $tpl->assign('sub_locations_row', $row);
            }
        }

        $objects = objects_by_location($location_id);
        if ($objects) {
            $tpl->assign('objects_list', ['total_number' => count($objects)]);
            foreach ($objects as $obj) {
                $img_url = '';
                $photos = images_by_obj_id('objects', $obj['id']);
                if (count($photos)) {
                    $photo = $photos[0];
                    $img_url = $photo->url('list');
                }
                $row = ['id' => $obj['id'],
                        'name' => $obj['name'],
                        'attrs' => $obj['attrs'],
                        'description' => str_replace("\n", '<br>', $obj['description']),
                        'link_to_object' => mk_url(['mod' => 'object', 'id' => $obj['id']]),
                        'img' => $img_url];
                $tpl->assign('object_row', $row);
                if ($obj['number'] > 1)
                    $tpl->assign('object_count', ['count' => $obj['number']]);

                if ($obj['is_absent'])
                    $tpl->assign('object_is_absent');

                $catalog = catalog_by_id($obj['catalog_id']);
                foreach ($catalog['path'] as $item)
                    $tpl->assign('catalog_path', ['name' => $item['name'],
                                                 'link' => $item['url']]);
            }

        }

        return $tpl->result();
    }


    function query($args)
    {
        $user = user_by_cookie();
        if ($user['role'] != 'admin')
            return mk_url(['mod' => $this->name, 'id' => $args['location_id']]);

        switch($args['method']) {
        case 'add_location':
            $parent_location = location_by_id((int)$args['location_id']);
            $parent_id = $parent_location['parent_id'];
        case 'add_sublocation':
            if ($args['method'] == 'add_sublocation')
                $parent_id = (int)$args['location_id'];

            $size1 = (int)$args['location_size1'];
            $size2 = (int)$args['location_size2'];
            $size3 = (int)$args['location_size3'];
            $volume = ($size1 / 100 * $size2 / 100 * $size3 / 100);
            $fullness = 0;
            $free_volume = $volume * (100 - $fullness) / 100;
            $photos_for_attach = isset($args['attach_not_assigned_photos']) ? $args['attach_not_assigned_photos'] : [];

            $new_location_id = db()->insert('location',
                                            ['parent_id' => $parent_id,
                                             'name' => $args['location_name'],
                                             'description' => $args['location_description'],
                                             'size1' => $size1,
                                             'size2' => $size2,
                                             'size3' => $size3,
                                             'fullness' => $fullness,
                                             'volume' => (int)($volume * 1000),
                                             'free_volume' => (int)($free_volume * 1000),
                                             'is_box' => ($volume ? '1' : '0'),
                                             'user_id' => (int)user_by_cookie()['id']]);
            if($new_location_id <= 0) {
                 message_box_err("Can't added new location");
                 return mk_url(['mod' => $this->name]);
            }

            if ($_FILES['photos']['name']) {
                $photos = images_upload_from_form('photos', 'locations', $new_location_id);
                if (!count($photos))
                    message_box_err('Can`t upload photos');

                foreach ($photos as $photo) {
                    $photo->resize('mini', ['w' => 1000]);
                    $photo->resize('list', ['w' => 300]);
                }
            }

            if (count($photos_for_attach)) {
                foreach ($photos_for_attach as $photo_hash => $checked) {
                    if ($checked == "false")
                        continue;
                    $img = new Image($photo_hash);
                    $img->set_object_type('locations');
                    $img->set_object_id($new_location_id);
                }
            }

            return mk_url(['mod' => $this->name, 'id' => $new_location_id]);

        case 'edit_location':
            $size1 = (int)$args['location_size1'];
            $size2 = (int)$args['location_size2'];
            $size3 = (int)$args['location_size3'];
            $volume = ($size1 / 100 * $size2 / 100 * $size3 / 100);
            $fullness = (int)$args['location_fullness'];
            $free_volume = $volume * (100 - $fullness) / 100;
            $photos_for_attach = isset($args['attach_not_assigned_photos']) ? $args['attach_not_assigned_photos'] : [];

            db()->update('location',
                         $args['location_id'],
                         ['name' => $args['location_name'],
                          'description' => $args['location_description'],
                          'size1' => $size1,
                          'size2' => $size2,
                          'size3' => $size3,
                          'fullness' => $fullness,
                          'volume' => (int)($volume * 1000),
                          'free_volume' => (int)($free_volume * 1000),
                          'is_box' => ($volume ? '1' : '0'),
                          'user_id' => (int)user_by_cookie()['id']]);

            if ($_FILES['photos']['name'][0]) {
                $photos = images_upload_from_form('photos', 'locations', $args['location_id']);
                if (!count($photos))
                    message_box_err('Can`t upload photos');

                foreach ($photos as $photo) {
                    $photo->resize('mini', ['w' => 1000]);
                    $photo->resize('list', ['w' => 300]);
                }
            }

            if (count($photos_for_attach)) {
                foreach ($photos_for_attach as $photo_hash => $checked) {
                    if ($checked == "false")
                        continue;
                    $img = new Image($photo_hash);
                    $img->set_object_type('locations');
                    $img->set_object_id($args['location_id']);
                }
            }

            return mk_url(['mod' => $this->name, 'id' => $args['location_id']]);

        case 'remove_location':
            $location = location_by_id($args['location_id']);
            $rc = $this->remove_location($args['location_id']);
            if ($rc) {
                message_box_err(sprintf("Can't remove location '%s'", $location['name']));
                return mk_url(['mod' => $this->name, 'id' => $args['location_id']]);
            }

            message_box_ok(sprintf("location '%s' successfully removed", $location['name']));
            return mk_url(['mod' => $this->name, 'id' => $location['parent_id']]);


        case 'cut_location':
            $this->cut_location($args['location_id']);
            return mk_url(['mod' => $this->name, 'id' => $args['location_id']]);

        case 'past_location':
            $plocation_id = $this->cutted_location_id();
            $plocation = location_by_id($plocation_id);
            if (!$plocation) {
                message_box_err(sprintf("Can't past location: Location not cutted early or not exist"));
                return mk_url(['mod' => $this->name, 'id' => $args['location_id']]);
            }

            $parent_plocation = location_by_id($plocation['parent_id']);
            $location = location_by_id($args['location_id']);

            $this->move_location($plocation_id, $args['location_id']);
            message_box_ok(sprintf("Node '%s' moved from '%s' to '$s'",
                                   $plocation['name'], $parent_plocation['name'], $location['name']));
            $this->reset_clipboard();
            return mk_url(['mod' => $this->name, 'id' => $args['location_id']]);

        case 'reset_clipboard':
            $this->reset_clipboard();
            return mk_url(['mod' => $this->name, 'id' => $args['id']]);

        case 'remove_photo':
            $this->remove_location_photo($args['photo_hash']);
            return mk_url(['mod' => $this->name, 'id' => $args['location_id']]);

        /* AJAX requests */
        case 'location_path':
            $location_id = $args['id'];
            $path = location_chain_by_id($location_id);
            echo json_encode($path);
            return 0;

        case 'get_sub_location':
            $rows = db()->query_list('select * from location where parent_id = %d '.
                                     'order by name asc', $args['id']);
            if (!$rows) {
                echo json_encode([]);
                return 0;
            }
            foreach ($rows as $row)
                $list[] = $row;
            echo json_encode($list);
            return 0;

        case 'take_away':
            db()->update('location', $args['location_id'], ['is_absent' => 1]);
            return mk_url(['mod' => $this->name, 'id' => $args['location_id']]);

        case 'return_back':
            db()->update('location', $args['location_id'], ['is_absent' => 0]);
            return mk_url(['mod' => $this->name, 'id' => $args['location_id']]);

        }

        return mk_url(['mod' => $this->name]);
    }

    function edit_location($location_id, $name, $description, $fullness)
    {
        db()->update('location', $location_id,
                     ['name' => $name,
                      'description' => $description,
                      'user_id' => (int)user_by_cookie()['id'],
                      'fullness' => (int)$fullness]);
    }

    function remove_location($location_id)
    {
        $row = db()->query('select count(id) as count from location where parent_id = %d', $location_id);
        if (is_array($row) && isset($row['count']) && $row['count'] > 0)
            return -1;

        $objects = objects_by_location($location_id);
        if (is_array($objects) && count($objects))
            return -1;


        db()->query('delete from location where id = %d', (int)$location_id);
        $photos = images_by_obj_id('location', $location_id);
        foreach ($photos as $photo)
            $photo->remove();
        return 0;
    }

    function remove_location_photo($hash)
    {
        $photo = image_by_hash($hash);
        $photo->remove();
    }

    function move_location($from, $to)
    {
        db()->update('location', $from, ['parent_id' => $to]);
    }

    function cut_location($location_id)
    {
        $_SESSION['cut_location'] = $location_id;
    }

    function reset_clipboard()
    {
        unset($_SESSION['cut_location']);
    }

    function cutted_location_id()
    {
        return $_SESSION['cut_location'];
    }

}

modules()->register('location', new Mod_location);
