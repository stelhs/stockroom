<?php

require_once "private/images.php";

class Mod_photos extends Module {

    function content($args = [])
    {
        $tpl = new strontium_tpl("private/tpl/mod_photos.html", conf()['global_marks'], false);
        $tpl->assign(NULL);

        return $tpl->result();
    }


}

modules()->register('photos', new Mod_photos);
