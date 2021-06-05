<?php

require_once "private/images.php";

class Mod_photos extends Module {

    function content($args = [])
    {
        $tpl = new strontium_tpl("private/tpl/mod_photos.html", conf()['global_marks'], false);
        $tpl->assign(NULL, ['form_url' => mk_url(['mod' => $this->name], 'query')]);

        $photos = images_by_obj_type('not_assigned');
        foreach ($photos as $photo) {
            $link_remove = mk_url(['method' => 'remove_photo',
                                   'photo_hash' => $photo->hash(),
                                   'mod' =>  $this->name], 'query');

            $tpl->assign('photo', ['img' => $photo->url('mini'),
                                   'img_orig' => $photo->url(),
                                   'link_remove' => $link_remove]);
        }

        return $tpl->result();
    }

    function query($args)
    {
        $user = user_by_cookie();
//        if ($user['role'] != 'admin')
  //          return mk_url(['mod' => $this->name, 'id' => $args['object_id']]);


        switch($args['method']) {
        case 'add_photo':
            if (!$_FILES['photos']['name'][0]) {
                message_box_err('Can`t upload photos');
                return mk_url(['mod' => 'photos']);
            }

            $photos = images_upload_from_form('photos', 'not_assigned', 0);
            if (!count($photos)) {
                message_box_err('Can`t upload photos');
                return mk_url(['mod' => 'photos']);
            }

            foreach ($photos as $photo) {
                if (!$photo) {
                    message_box_err('Can`t resize photos');
                    message_box_ok(sprintf('Object %d changed', $args['object_id']));
                }
                $photo->resize('mini', ['w' => 1000]);
                $photo->resize('list', ['w' => 300]);
            }

            return mk_url(['mod' => 'photos']);


        case 'remove_photo':
            $photo = image_by_hash($args['photo_hash']);
            $photo->remove();
            return mk_url(['mod' => $this->name]);
        }
    }
}

modules()->register('photos', new Mod_photos);
