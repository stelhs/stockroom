<?php

require_once "private/object.php";
require_once "private/images.php";


class Mod_search extends Module {

    function content($args = [])
    {
        $tpl = new strontium_tpl("private/tpl/mod_search.html", conf()['global_marks'], false);
        $text = trim($args['text']);
        $catalog_id = (int)$args['cat_id'] ? (int)$args['cat_id'] : 0;
        $object_attrs_text = trim($args['object_attrs']);

        $obj_attrs = parse_attrs($object_attrs_text);
        $object_attrs_text = attrs_to_text($obj_attrs);

        $tpl->assign(NULL, ['form_url' => mk_url(),
                            'mod' => $this->name,
                            'cat_id' => $catalog_id,
                            'object_attrs' => $object_attrs_text,
                            'text' => $text]);

        $existed_attrs = get_existed_attrs();
        if (count($existed_attrs))
            foreach ($existed_attrs as $attr)
                $tpl->assign('existed_attr', ['attr' => $attr]);

        if ($text) {
            /* search object by #ID */
            preg_match('/^#(\d+)/', $text, $m);
            if (isset($m[1])) {
                $object = object_by_id($m[1]);
                if (!$object) {
                    $tpl->assign('no_result');
                    return $tpl->result();
                }

                header('location: '.mk_url(['mod' => 'object', 'id' => $object['id']]));
                return;
            }

            preg_match('/^\.(\d+)/', $text, $m);
            if (isset($m[1])) {
                $object = object_by_id($m[1]);
                if (!$object) {
                    $tpl->assign('no_result');
                    return $tpl->result();
                }

                header('location: '.mk_url(['mod' => 'object', 'id' => $object['id']]));
      	        return;
            }

            /* search location by ##ID */
            preg_match('/^##(\d+)/', $text, $m);
            if (isset($m[1])) {
                $location = location_by_id($m[1]);
                if (!$location) {
                    $tpl->assign('no_result');
                    return $tpl->result();
                }

                header('location: '.mk_url(['mod' => 'location', 'id' => $location['id']]));
                return;
            }
        }

        if (($text[0] == '' && $catalog_id == 0 && $object_attrs_text == "") || $args['no_search'])
            return $tpl->result();

        if (!$obj_attrs) {
            $cat_result = catalog_list_by_text($text, $catalog_id);
            $loc_result = location_list_by_text($text);
        }

        $obj_result = $this->find_by_object($text, $catalog_id, $obj_attrs);
        /*set_time_limit(0);
        dump($obj_result);
        foreach ($obj_result as $object) {
            preg_match('/-\s*([\d\.]+)/i', $object['name'], $m);
            if (!isset($m[1]))
                continue;

            db()->update('objects', $object['id'], ['attrs' => sprintf('Длина: %s\n', $m[1])]);
        }
        exit;*/


        if (!count($cat_result) && !count($loc_result) && !count($obj_result))
            $tpl->assign('no_result');

        if ($cat_result) {
            $tpl->assign('result_catalog');
            foreach ($cat_result as $category) {
                $tpl->assign('result_catalog_row',
                             ['id' => $category['id'],
                              'link' => mk_url(['mod' => 'catalog',
                              'id' => $category['id']])]);

                foreach ($category['path'] as $node)
                    $tpl->assign('result_catalog_path', ['name' => $node['name']]);
            }
        }


        if ($loc_result) {
            $tpl->assign('result_location');
            foreach ($loc_result as $location) {
                $tpl->assign('result_location_row',
                             ['id' => $location['id'],
                              'link' => mk_url(['mod' => 'location',
                              'id' => $location['id']])]);

                foreach ($location['path'] as $node)
                    $tpl->assign('result_location_path', ['name' => $node['name']]);
            }
        }

        if ($obj_result) {
            $tpl->assign('result_objects', ['total_num' => count($obj_result)]);

            $attr_sum_table = [];

            foreach ($obj_result as $object) {
                $img_url = '';
                $photo = NULL;

                if ($object['label_photo']) {
                    $photo = image_by_hash($object['label_photo']);
                    $img_url = $photo->url('list');
                }

                if (!$photo) {
                    $photos = images_by_obj_id('objects', $object['id']);
                    if (count($photos)) {
                        $photo = $photos[0];
                        $img_url = $photo->url('list');
                    }
                }


                $attrs = parse_attrs($object['attrs']);
                foreach ($attrs as $attr) {
                    $name = $attr[0];
                    $val = $attr[1];
                    if (!$val)
                        continue;
                    if (isset($attr_sum_table[$name]))
                        $attr_sum_table[$name] += $val;
                    else
                        $attr_sum_table[$name] = $val;
                }

                $tpl->assign('result_objects_row',
                             ['id' => $object['id'],
                              'link' => mk_url(['mod' => 'object',
                                                'id' => $object['id']]),
                              'name' => $object['name'],
                              'attrs' => $object['attrs'],
                              'description' => $object['description'],
                              'img' => $img_url]);
                if ($object['number'] > 1)
                    $tpl->assign('object_count', ['count' => $object['number']]);

                $location = location_by_id($object['location_id']);
                foreach ($location['path'] as $item)
                    $tpl->assign('location_path', ['name' => $item['name'],
                                                   'link' => $item['url']]);

                $catalog = catalog_by_id($object['catalog_id']);
                foreach ($catalog['path'] as $item)
                    $tpl->assign('catalog_path', ['name' => $item['name'],
                                                  'id' => $item['id'],
                                                  'link' => $item['url']]);
            }
            if (count($attr_sum_table)) {
                $tpl->assign('result_attrs_table');

                foreach ($attr_sum_table as $name => $sum) {
                    $tpl->assign('result_attrs_row', ['name' => $name,
                                                      'sum' => $sum]);
                }
            }
        }

        return $tpl->result();
    }


    function find_by_object($text, $cat_id, $search_attrs)
    {
        $rows = db()->query_list('select id, catalog_id from objects where '.
                                 'name LIKE "%%%s%%" or '.
                                 'description LIKE "%%%s%%" ORDER BY name ASC',
                                 $text, $text);
        if (!is_array($rows) || !count($rows))
            return NULL;

        $list = [];
        foreach ($rows as $row) {
            if (!catalog_is_child($row['catalog_id'], $cat_id))
                continue;

            $object = object_by_id($row['id']);
            if (!object_attrs_match(parse_attrs($object['attrs']), $search_attrs))
                continue;

            $list[] = $object;
        }
        return count($list) ? $list : NULL;
    }

}

modules()->register('search', new Mod_search);
