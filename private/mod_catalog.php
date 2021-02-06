<?php

require_once "private/catalog.php";
require_once "private/images.php";

class Mod_catalog extends Module {

    function content($args = [])
    {
        $catalog_id = isset($args['id']) ? $args['id'] : 0;
        $catalog = catalog_by_id($catalog_id);

        $tpl = new strontium_tpl("private/tpl/mod_catalog.html", conf()['global_marks'], false);

        if (!$catalog) {
            $tpl->assign('no_catalog', ['catalog_id' => $catalog_id]);
            return $tpl->result();
        }

        foreach ($catalog['path'] as $item)
            $tpl->assign('catalog_path', ['name' => stripslashes($item['name']),
                                           'link' => $item['url']]);

        $tpl->assign('catalog', ['catalog_id' => $catalog_id,
                                 'catalog_name' => stripslashes($catalog['name']),
                                 'catalog_description' => stripslashes($catalog['description']),
                                 'form_url' => mk_url(['mod' => $this->name], 'query'),
                                 'link_delete' => mk_url(['mod' => $this->name,
                                                          'method' => 'remove_catalog',
                                                          'catalog_id' => $catalog_id], 'query'),
                                 'link_add_object' => mk_url(['mod' => 'object', 'catalog_id' => $catalog_id]),
                                 'link_search' => mk_url(['mod' => 'search', 'cat_id' => $catalog_id, 'no_search' => 1])]);

        $photos = images_by_obj_id('catalogs', $catalog_id);
        foreach ($photos as $photo) {
            $link_remove = mk_url(['method' => 'remove_photo',
                                   'photo_hash' => $photo->hash(),
                                   'mod' =>  $this->name,
                                   'catalog_id' => $catalog_id], 'query');

            $tpl->assign('catalog_photo', ['img' => $photo->url('mini'),
                                           'img_orig' => $photo->url(),
                                           'link_remove' => $link_remove]);
        }

        $for_pasting_cat_id = $this->cutted_catalog_id();
        if ($for_pasting_cat_id) {
            $pcatalog = catalog_by_id($for_pasting_cat_id);
            $tpl->assign('catalog_clipboard',
                         ['id' => $pcatalog['id'],
                          'name' => $pcatalog['name'],
                          'link_reset' => mk_url(['mod' => $this->name,
                                                  'method' => 'reset_clipboard',
                                                  'id' => $catalog_id],
                                                 'query')]);

            foreach ($pcatalog['path'] as $item)
                $tpl->assign('clipboard_catalog_path', ['name' => $item['name'],
                                                        'link' => $item['url']]);

            if ($for_pasting_cat_id != $catalog_id)
                $tpl->assign('past_catalog', ['past_catalog_name' => $pcatalog['name'],
                                              'catalog_name' => stripslashes($location['name'])]);
            else
                $tpl->assign('past_catalog_blocked');
        }

        $sub_catalogs = db()->query_list('select * from catalog where parent_id = %d '.
                                         'order by name asc',
                                      $catalog_id);
        if ($sub_catalogs) {
            $tpl->assign('sub_catalogs_list', ['total_number' => count($sub_catalogs)]);
            foreach($sub_catalogs as $sub_catalog)
            {
                $user = user_by_id($sub_catalog['user_id']);

                $row = db()->query('select count(id) as number from objects '.
                                   'where catalog_id=%d', $sub_catalog['id']);
                $objects_number = $row['number'] ? $row['number'] : '';

                $row = db()->query('select count(id) as number from catalog '.
                                   'where parent_id=%d', $sub_catalog['id']);
                $category_number = $row['number'] ? $row['number'] : '';

                $row = ['id' => $sub_catalog['id'],
                        'name' => $sub_catalog['name'],
                        'description' => $sub_catalog['description'],
                        'added_date' => $sub_catalog['created'],
                        'user' => $user['login'],
                        'link' => mk_url(['mod' => $this->name,
                                          'id' => $sub_catalog['id']]),
                        'objects_number' => $objects_number,
                        'category_number' => $category_number];
                $tpl->assign('sub_catalogs_row', $row);
            }
        }

    /*   if ($catalog_id == 0)
            return $tpl->result();*/

        $objects = objects_by_catalog($catalog_id);
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
                        'description' => str_replace("\n", '<br>', stripslashes($obj['description'])),
                        'link_to_object' => mk_url(['mod' => 'object', 'id' => $obj['id']]),
                        'img' => $img_url];
                $tpl->assign('object_row', $row);
                if ($obj['number'] > 1)
                    $tpl->assign('object_count', ['count' => $obj['number']]);

                if ($obj['is_absent'])
                    $tpl->assign('object_is_absent');

                $location = location_by_id($obj['location_id']);
                foreach ($location['path'] as $item)
                    $tpl->assign('location_path', ['name' => $item['name'],
                                                   'link' => $item['url']]);
            }
        }

        return $tpl->result();
    }


    function query($args)
    {
        $user = user_by_cookie();
        if ($user['role'] != 'admin')
            return mk_url(['mod' => $this->name, 'id' => $args['catalog_id']]);

        switch($args['method']) {
        case 'add_catalog':
            $new_catalog_id = $this->add_catalog($args['catalog_id'],
                                           addslashes($args['catalog_name']),
                                           addslashes($args['catalog_description']));
            if($new_catalog_id <= 0) {
                 message_box_err("Can't added new catalog");
                 return mk_url(['mod' => $this->name]);
            }

            if ($_FILES['photos']['name']) {
                $photos = images_upload_from_form('photos', 'catalogs', $new_catalog_id);
                if (!count($photos))
                    message_box_err('Can`t upload photos');

                foreach ($photos as $photo)
                     $photo->resize('mini', ['w' => 1000]);
            }

            return mk_url(['mod' => $this->name, 'id' => $new_catalog_id]);

        case 'edit_catalog':
            $this->edit_catalog($args['catalog_id'],
                                addslashes($args['catalog_name']),
                                addslashes($args['catalog_description']));

            if ($_FILES['photos']['name']) {
                $photos = images_upload_from_form('photos', 'catalogs', $args['catalog_id']);
                if (!count($photos))
                    message_box_err('Can`t upload photos');

                foreach ($photos as $photo)
                     $photo->resize('mini', ['w' => 1000]);
            }

            return mk_url(['mod' => $this->name, 'id' => $args['catalog_id']]);

        case 'remove_catalog':
            $catalog = catalog_by_id($args['catalog_id']);
            $rc = $this->remove_catalog($args['catalog_id']);
            if ($rc) {
                message_box_err(sprintf("Can't remove catalog '%s'", $catalog['name']));
                return mk_url(['mod' => $this->name, 'id' => $args['catalog_id']]);
            }

            message_box_ok(sprintf("catalog '%s' successfully removed", $catalog['name']));
            return mk_url(['mod' => $this->name, 'id' => $catalog['parent_id']]);


        case 'cut_catalog':
            $this->cut_catalog($args['catalog_id']);
            return mk_url(['mod' => $this->name, 'id' => $args['catalog_id']]);

        case 'past_catalog':
            $pcatalog_id = $this->cutted_catalog_id();
            $pcatalog = catalog_by_id($pcatalog_id);
            if (!$pcatalog) {
                message_box_err(sprintf("Can't past catalog: Location not cutted early or not exist"));
                return mk_url(['mod' => $this->name, 'id' => $args['catalog_id']]);
            }

            $parent_pcatalog = catalog_by_id($pcatalog['parent_id']);
            $catalog = catalog_by_id($args['catalog_id']);

            $this->move_catalog($pcatalog_id, $args['catalog_id']);
            message_box_ok(sprintf("Node '%s' moved from '%s' to '$s'",
                                   $pcatalog['name'], $parent_pcatalog['name'], $catalog['name']));
            $this->reset_clipboard();

            return mk_url(['mod' => $this->name, 'id' => $args['catalog_id']]);

        case 'reset_clipboard':
            $this->reset_clipboard();
            return mk_url(['mod' => $this->name, 'id' => $args['id']]);

        case 'remove_photo':
            $this->remove_catalog_photo($args['photo_hash']);
            return mk_url(['mod' => $this->name, 'id' => $args['catalog_id']]);

        /* AJAX requests */
        case 'catalog_path':
            $catalog_id = $args['id'];
            $path = catalog_chain_by_id($catalog_id);
            echo json_encode($path);
            return 0;

        case 'get_sub_catalog':
            $rows = db()->query_list('select * from catalog where parent_id = %d '.
                                     'order by name asc', $args['id']);
            if (!$rows) {
                echo json_encode([]);
                return 0;
            }

            foreach ($rows as $row)
                $list[] = $row;
            echo json_encode($list);
            return 0;

        }

        return mk_url(['mod' => $this->name]);
    }

    function add_catalog($parentd_id, $name, $description)
    {
        $id = db()->insert('catalog', ['parent_id' => (int)$parentd_id,
                                       'name' => $name,
                                      'description' => $description,
                                      'user_id' => (int)user_by_cookie()['id']]);
        return $id;
    }

    function edit_catalog($catalog_id, $name, $description)
    {
        db()->update('catalog', $catalog_id,
                     ['name' => $name,
                      'description' => $description,
                      'user_id' => (int)user_by_cookie()['id']]);
    }

    function remove_catalog($catalog_id)
    {
        $row = db()->query('select count(id) as count from catalog where parent_id = %d', $catalog_id);
        if (is_array($row) && isset($row['count']) && $row['count'] > 0)
            return -1;

        $objects = objects_by_catalog($catalog_id);
        if (is_array($objects) && count($objects))
            return -1;


        db()->query('delete from catalog where id = %d', (int)$catalog_id);
        $photos = images_by_obj_id('catalog', $catalog_id);
        foreach ($photos as $photo)
            $photo->remove();
        return 0;
    }

    function remove_catalog_photo($hash)
    {
        $photo = image_by_hash($hash);
        $photo->remove();
    }

    function move_catalog($from, $to)
    {
        db()->update('catalog', $from, ['parent_id' => $to]);
    }

    function cut_catalog($catalog_id)
    {
        $_SESSION['cut_catalog'] = $catalog_id;
    }

    function reset_clipboard()
    {
        unset($_SESSION['cut_catalog']);
    }

    function cutted_catalog_id()
    {
        return $_SESSION['cut_catalog'];
    }

}

modules()->register('catalog', new Mod_catalog);
