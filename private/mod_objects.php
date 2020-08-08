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
                                'location_id' => $args['location_id'] ? $args['location_id'] : ""]);
            $tpl->assign('object_add');

            $existed_attrs = get_existed_attrs();
            if (count($existed_attrs))
                foreach ($existed_attrs as $attr)
                    $tpl->assign('existed_attr', ['attr' => $attr]);
            return $tpl->result();
        }

        $object_id = $args['id'];
        $object = object_by_id($object_id);
        $tpl->assign(NULL, ['number' => $object['number'],
                            'form_url' => mk_url(['mod' => $this->name], 'query'),
                            'catalog_id' => $object['catalog_id'],
                            'location_id' => $object['location_id'] ? $object['location_id'] : "",
                            'object_id' => $object_id,
                            'object_name' => stripslashes($object['name']),
                            'object_description' => stripslashes($object['description']),
                            'object_attrs' => $object['attrs']]);

        $existed_attrs = get_existed_attrs();
        if (count($existed_attrs))
            foreach ($existed_attrs as $attr)
                $tpl->assign('existed_attr', ['attr' => $attr]);

        print_absent_locations($tpl);

        $this->print_take_away_buttons($tpl, $object);

        $tpl->assign('object_edit_id', ['id' => $object_id,
                                        'name' => $object['name']]);
        $tpl->assign('edit_button', ['object_name' => stripslashes($object['name'])]);
        $tpl->assign('remove_button',
                     ['link' => mk_url(['mod' => $this->name,
                                        'method' => 'remove_object',
                                        'id' => $object_id],
                                       'query'),
                      'object_name' => stripslashes($object['name'])]);

        $photos = images_by_obj_id('objects', $object_id);
        foreach ($photos as $photo) {
            $link_remove = mk_url(['method' => 'remove_photo',
                                   'photo_hash' => $photo->hash(),
                                   'mod' =>  $this->name,
                                   'obj_id' => $object_id], 'query');

            $tpl->assign('photo', ['img' => $photo->url('mini'),
                                   'img_orig' => $photo->url(),
                                   'link_remove' => $link_remove]);
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

        $take_quanity = ($object['number'] - $object['absent']);
        if (!$take_quanity)
            return;

        if ($take_quanity > 1)
            $tpl->assign('take_away_many', ['max_number' => $take_quanity]);
        else
            $tpl->assign('take_away');
    }

    function query($args)
    {
        $user = user_by_cookie();
        if ($user['role'] != 'admin')
            return mk_url(['mod' => $this->name, 'id' => $args['object_id']]);

        switch($args['method']) {
        case 'object_add':
            $location_id = $args['location_id'] ? $args['location_id'] : 0;
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

            /* If duplicate */
            if ($args['object_id']) {
             /*   $photos = images_by_obj_id('objects', $args['object_id']);
                foreach ($photos as $photo)
                    $photo->duplicate('objects', $object_id);*/
                $_SESSION['duplicated'] = 1;
            }

            message_box_ok(sprintf('Added new object %d', $object_id));
            $_SESSION['updated'] = 1;
            return mk_url(['mod' => $this->name, 'id' => $object_id]);

        case 'object_edit':
            $location_id = $args['location_id'] ? $args['location_id'] : 0;
            $rc = object_edit($args['object_id'],
                              $args['catalog_id'],
                              $location_id,
                              addslashes($args['object_name']),
                              addslashes($args['object_description']),
                              $args['objects_number'],
                              $args['object_attrs']);
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

            message_box_ok(sprintf('Object %d changed', $args['object_id']));
            $_SESSION['updated'] = 1;
            return mk_url(['mod' => $this->name, 'id' => $args['object_id']]);

        case 'remove_object':
            $obj_id = $args['id'];
            $location_id = $args['location_id'] ? $args['location_id'] : 0;
            $photos = images_by_obj_id('objects', $obj_id);
            if ($photos)
                foreach ($photos as $photo)
                    $photo->remove();
            $obj = object_by_id($obj_id);
            db()->query('delete from objects where id = %d', $obj_id);
            message_box_ok(sprintf('Object %d was removed', $obj_id));
            return mk_url(['mod' => 'location', 'id' => $location_id]);

        case 'remove_photo':
            $photo = image_by_hash($args['photo_hash']);
            $photo->remove();
            return mk_url(['mod' => $this->name, 'id' => $args['obj_id']]);

        case 'take_away':
            $obj = db()->query('select * from objects where id=%d', $args['object_id']);
            $quanity = isset($args['quanity']) ? (int)$args['quanity'] : 1;
            $take = $obj['absent'] + $quanity;
            if ($take > $obj['number'])
                $take = $obj['number'];
            db()->update('objects', $args['object_id'], ['absent' => $take]);
            return mk_url(['mod' => $this->name, 'id' => $args['object_id']]);

        case 'return_back':
            $obj = db()->query('select * from objects where id=%d', $args['object_id']);
            $quanity = isset($args['quanity']) ? (int)$args['quanity'] : 1;
            $absent = $obj['absent'] - $quanity;
            if ($absent < 0)
                $absent = 0;
            db()->update('objects', $args['object_id'], ['absent' => $absent]);
            return mk_url(['mod' => $this->name, 'id' => $args['object_id']]);

        }
        return mk_url(['mod' => $this->name]);
    }



}

modules()->register('object', new Mod_object);
