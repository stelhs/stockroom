<?php

require_once "private/object.php";
require_once "private/images.php";


class Mod_object extends Module {

    function content($args = [])
    {
        $tpl = new strontium_tpl("private/tpl/mod_objects.html", conf()['global_marks'], false);

        if (!isset($args['id']) || $args['id'] <= 0) {
            $tpl->assign(NULL, ['number' => 1,
                                'form_url' => mk_url(['mod' => $this->name], 'query'),
                                'catalog_id' => $args['catalog_id'] ? $args['catalog_id'] : 0,
                                'location_id' => $args['location_id'] ? $args['location_id'] : "",
                                'free_boxes_link' => mk_url(['mod' => 'boxes',
                                                             'location_id' => 0])]);
            $tpl->assign('object_add');
            $tpl->assign('edit_quantity', ['number' => 1]);

            $existed_attrs = get_existed_attrs();
            if (count($existed_attrs))
                foreach ($existed_attrs as $attr)
                    $tpl->assign('existed_attr', ['attr' => $attr]);

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

            return $tpl->result();
        }

        $object_id = $args['id'];
        $object = object_by_id($object_id);
        $name = stripslashes($object['name']);
        $tpl->assign(NULL, ['number' => $object['number'],
                            'form_url' => mk_url(['mod' => $this->name], 'query'),
                            'catalog_id' => $object['catalog_id'],
                            'location_id' => $object['location_id'] ? $object['location_id'] : "",
                            'object_id' => $object_id,
                            'object_name' => $name,
                            'object_name_quoted' => str_replace('"', '&quot;', $name),
                            'object_description' => stripslashes($object['description']),
                            'object_attrs' => $object['attrs'],
                            'free_boxes_link' => mk_url(['mod' => 'boxes',
                                                         'location_id' => 0])]);

        $tpl->assign('show_quantity', ['number' => $object['number'] - $object['absent']]);

        $existed_attrs = get_existed_attrs();
        if (count($existed_attrs))
            foreach ($existed_attrs as $attr)
                $tpl->assign('existed_attr', ['attr' => $attr]);

        if ($object['absent'] > 0)
            $tpl->assign('object_is_absent', ['number' => $object['absent']]);

        $this->print_take_away_buttons($tpl, $object);

        $tpl->assign('object_edit_id', ['id' => $object_id,
                                        'name' => $object['name'],
                                        'created' => $object['created']]);
        $tpl->assign('edit_button', ['object_name' => stripslashes($object['name'])]);

        $tpl->assign('remove_button',
                     ['object_name' => stripslashes($object['name']),
                      'max_number' => $object['number']]);

        $list = images_by_obj_id('objects', $object_id);
        $first_photo = NULL;
        foreach ($list as $photo) {
            if ($photo->hash() == $object['label_photo']) {
                $first_photo = $photo;
                continue;
            }
            $photos[] = $photo;
        }
        if ($first_photo)
            array_unshift($photos , $first_photo);
        else
            $photos = $list;

        foreach ($photos as $photo) {
            $link_remove = mk_url(['method' => 'remove_photo',
                                   'photo_hash' => $photo->hash(),
                                   'mod' =>  $this->name,
                                   'obj_id' => $object_id], 'query');

            $tpl->assign('photo', ['img' => $photo->url('mini'),
                                   'img_orig' => $photo->url(),
                                   'img_hash' => $photo->hash(),
                                   'selected' => ($photo->hash() == $object['label_photo'] ? 'true' : 'false'),
                                   'link_remove' => $link_remove]);
        }

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


        $location = location_by_id($object['location_id']);
        foreach ($location['path'] as $item)
            $tpl->assign('location_path', ['name' => $item['name'],
                                           'link' => $item['url']]);

        $catalog = catalog_by_id($object['catalog_id']);
        foreach ($catalog['path'] as $item)
            $tpl->assign('catalog_path', ['name' => $item['name'],
                                           'link' => $item['url']]);

        if ($location['is_box']) {
            $block = ['location_fullness' => $location['fullness'],
                      'location_name' => $location['name']];
            $tpl->assign(($_SESSION['updated'] ? 'box_fullness_please_update' : 'box_fullness'), $block);
        }
        unset($_SESSION['updated']);

        if ($_SESSION['duplicated']) {
            unset($_SESSION['duplicated']);
            $tpl->assign('object_was_duplicated');
        }

        return $tpl->result();
    }

    function print_take_away_buttons($tpl, $object)
    {
        if ($object['absent'] == 0) {
            if ($object['number'] > 1)
                $tpl->assign('take_away_many', ['max_number' => $object['number']]);
            else
                $tpl->assign('take_away');
            return;
        }

        if ($object['absent'] == 1)
            $tpl->assign('return_back');
        else
            $tpl->assign('return_back_many', ['max_number' => $object['absent']]);

        $take_quantity = ($object['number'] - $object['absent']);
        if (!$take_quantity)
            return;

        if ($take_quantity > 1)
            $tpl->assign('take_away_many', ['max_number' => $take_quantity]);
        else
            $tpl->assign('take_away');
    }

    function query($args)
    {
        $user = user_by_cookie();
        if ($user['role'] != 'admin')
            return mk_url(['mod' => $this->name, 'id' => $args['object_id']]);

        switch($args['method']) {
        case 'object_add_with_img':
        case 'object_add':
            $location_id = $args['location_id'] ? $args['location_id'] : 0;
            $photos_for_attach = isset($args['attach_not_assigned_photos']) ? $args['attach_not_assigned_photos'] : [];

            $object_id = object_add($args['catalog_id'],
                                    $location_id,
                                    addslashes($args['object_name']),
                                    addslashes($args['object_description']),
                                    ($args['object_id'] ? 1 : $args['objects_number']),
                                    $args['object_attrs']);

            if ($object_id <= 0) {
                message_box_err('Can`t add new object');
                return mk_url(['mod' => 'location', 'id' => $location_id]);
            }

            if ($_FILES['photos']['name'][0]) {
                $photos = images_upload_from_form('photos', 'objects', $object_id);
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
                    $img->set_object_type('objects');
                    $img->set_object_id($object_id);
                }
            }

            /* If duplicate */
            if ($args['object_id']) {
                if ($args['method'] == 'object_add_with_img') {
                    $photos = images_by_obj_id('objects', $args['object_id']);
                    foreach ($photos as $photo)
                        $photo->duplicate('objects', $object_id);
                }
                $_SESSION['duplicated'] = 1;
            }

            message_box_ok(sprintf('Added new object %d', $object_id));
            $_SESSION['updated'] = 1;
            return mk_url(['mod' => $this->name, 'id' => $object_id]);

        case 'object_edit':
            $location_id = $args['location_id'] ? $args['location_id'] : 0;
            $photos_for_attach = isset($args['attach_not_assigned_photos']) ? $args['attach_not_assigned_photos'] : [];

            $rc = object_edit($args['object_id'],
                              $args['catalog_id'],
                              $location_id,
                              addslashes($args['object_name']),
                              addslashes($args['object_description']),
                              $args['object_attrs'],
                              $args['label_photo']);
            if ($rc)
                message_box_err('Can`t edit object');

            if (isset($args['location_fullness']) && $args['location_fullness'] !== "") {
                $obj = object_by_id($args['object_id']);
                db()->query('update location set fullness=%d where id=%d',
                            $args['location_fullness'], $location_id);
            }

            if ($_FILES['photos']['name'][0]) {
                $photos = images_upload_from_form('photos', 'objects', $args['object_id']);
                if (!count($photos))
                    message_box_err('Can`t upload photos');

                foreach ($photos as $photo) {
                    if (!$photo) {
                        message_box_err('Can`t resize photos');
                        message_box_ok(sprintf('Object %d changed', $args['object_id']));
                    }
                    $photo->resize('mini', ['w' => 1000]);
                    $photo->resize('list', ['w' => 300]);
                }
            }

            if (count($photos_for_attach)) {
                foreach ($photos_for_attach as $photo_hash => $checked) {
                    if ($checked == "false")
                        continue;
                    $img = new Image($photo_hash);
                    $img->set_object_type('objects');
                    $img->set_object_id($args['object_id']);
                }
            }

            message_box_ok(sprintf('Object %d changed', $args['object_id']));
            $_SESSION['updated'] = 1;
            return mk_url(['mod' => $this->name, 'id' => $args['object_id']]);

        case 'remove_photo':
            $photo = image_by_hash($args['photo_hash']);
            $photo->remove();
            return mk_url(['mod' => $this->name, 'id' => $args['obj_id']]);

        case 'take_away':
            $obj = db()->query('select * from objects where id=%d', $args['object_id']);
            $quantity = isset($args['quantity']) ? (int)$args['quantity'] : 1;
            $take = $obj['absent'] + $quantity;
            if ($take > $obj['number'])
                $take = $obj['number'];
            db()->update('objects', $args['object_id'], ['absent' => $take]);
            return mk_url(['mod' => $this->name, 'id' => $args['object_id']]);

        case 'return_back':
            $obj = db()->query('select * from objects where id=%d', $args['object_id']);
            $quantity = isset($args['quantity']) ? (int)$args['quantity'] : 1;
            $absent = $obj['absent'] - $quantity;
            if ($absent < 0)
                $absent = 0;
            db()->update('objects', $args['object_id'], ['absent' => $absent]);
            return mk_url(['mod' => $this->name, 'id' => $args['object_id']]);

        case 'dec_quantity':
            $obj_id = (int)$args['object_id'];
            $obj = db()->query('select * from objects where id=%d', $obj_id);
            $quantity = isset($args['quantity']) ? (int)$args['quantity'] : 1;
            $left = $obj['number'] - $quantity;
            if ($left < 1) {
                object_remove($obj_id);
                message_box_ok(sprintf('Object "%s" was removed', $obj['name']));
                return mk_url(['mod' => 'catalog', 'id' => $obj['catalog_id']]);
            }

            $fields = ['number' => $left];
            if ($left < $obj['absent'])
                $fields['absent'] = $left;

            db()->update('objects', $args['object_id'], $fields);
            return mk_url(['mod' => $this->name, 'id' => $args['object_id']]);

        case 'remove_absent':
            $obj_id = (int)$args['object_id'];
            $obj = db()->query('select * from objects where id=%d', $obj_id);
            if ($obj['absent'] < 1) {
                message_box_ok(sprintf('Object "%s" was not removed', $obj['name']));
                return mk_url(['mod' => $this->name, 'id' => $args['object_id']]);
            }

            $left = $obj['number'] - $obj['absent'];
            if ($left < 1) {
                object_remove($obj_id);
                message_box_ok(sprintf('Object "%s" was removed', $obj['name']));
                return mk_url(['mod' => 'catalog', 'id' => $obj['catalog_id']]);
            }

            db()->update('objects', $args['object_id'],
                         ['number' => $left, 'absent' => 0]);
            return mk_url(['mod' => $this->name, 'id' => $args['object_id']]);


        case 'inc_quantity':
            $obj = db()->query('select * from objects where id=%d', $args['object_id']);
            $quantity = isset($args['quantity']) ? (int)$args['quantity'] : 1;
            $new_num = $obj['number'] + $quantity;
            db()->update('objects', $args['object_id'], ['number' => $new_num]);
            return mk_url(['mod' => $this->name, 'id' => $args['object_id']]);


        case 'to_withdrawal':
            $obj_id = (int)$args['object_id'];
            $obj = db()->query('select * from objects where id=%d', $obj_id);
            $quantity = isset($args['quantity']) ? (int)$args['quantity'] : 1;

            $row = db()->query('select * from withdrawal_list where obj_id = %d', $obj_id);
            if ($row and is_array($row) and isset($row['quantity']) and (!$row['completed'])) {
                $quantity += $row['quantity'];
                db()->query('update withdrawal_list set ' .
                            'quantity = %d where obj_id = %d',
                            $quantity, $obj_id);
            } else {
                db()->query('delete from withdrawal_list where obj_id = %d', $obj_id);
                db()->insert('withdrawal_list',
                             ['obj_id' => $obj_id,
                              'quantity' => $quantity]);
            }
            message_box_ok(sprintf("'%s' placed into withdrawal list", $obj['name']));
            return mk_url(['mod' => $this->name, 'id' => $args['object_id']]);


        /* AJAX requests */
        case 'find_catalogs_by_string':
            $text = $args['text'];
            $cat_result = catalog_list_by_text($text);

            if (!$cat_result)
                return;

            $tpl = new strontium_tpl("private/tpl/catalog_search_list.html", conf()['global_marks'], false);
            $tpl->assign();

            foreach ($cat_result as $category) {
                $tpl->assign('row_cat',
                             ['id' => $category['id']]);

                foreach ($category['path'] as $node)
                    $tpl->assign('row_cat_path', ['name' => $node['name']]);
            }
            $content = $tpl->result();

            $cat_id = NULL;
            if (count($cat_result) == 1)
                $cat_id = $cat_result[0]['id'];

            echo json_encode(['content' => $content,
                              'cat_id' => $cat_id]);
            return 0;

        case 'find_locations_by_string':
            $text = $args['text'];
            $loc_result = location_list_by_text($text);
            if (!$loc_result)
                return;

            $tpl = new strontium_tpl("private/tpl/location_search_list.html", conf()['global_marks'], false);
            $tpl->assign();

            foreach ($loc_result as $location) {
                $tpl->assign('row_loc',
                             ['id' => $location['id']]);

                foreach ($location['path'] as $node)
                    $tpl->assign('row_loc_path', ['name' => $node['name']]);
            }
            $content = $tpl->result();


            $loc_id = NULL;
            if (count($loc_result) == 1)
                $loc_id = $loc_result[0]['id'];

            echo json_encode(['content' => $content,
                              'loc_id' => $loc_id]);
            return 0;

        }
        return mk_url(['mod' => $this->name]);
    }



}

modules()->register('object', new Mod_object);
