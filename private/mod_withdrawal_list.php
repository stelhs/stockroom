<?php

require_once "private/images.php";


class Mod_withdrawal_list extends Module {
    function content($args = [])
    {
        $tpl = new strontium_tpl("private/tpl/mod_withdrawal_list.html", conf()['global_marks'], false);
        $tpl->assign(NULL, ['form_url' => mk_url(),
                            'mod' => $this->name]);

        $tree = new Tree();

        $rows = withdrawal_list();
        foreach ($rows as $row) {
            $obj = object_by_id($row['obj_id']);
            $chain = location_chain_by_id($obj['location_id']);

            foreach ($chain as $location_item) {
                if ($location_item['id'] == 0)
                    continue;
                $tree->insert_node($location_item['id'],
                                   $location_item['parent_id'],
                                   $chain);
            }
            $obj['withdrawal_quantity'] = $row['quantity'];
            $obj['withdrawal_completed'] = $row['completed'];
            $tree->insert_object($row['obj_id'],
                                 $obj['location_id'],
                                 $obj);
        }

        $tree->remove_empty_nodes();

        $html = $this->render_node($tree->root, 0);
        $tpl->assign('tree', ['html' => $html]);
        return $tpl->result();
    }


    function query($args)
    {
        $user = user_by_cookie();
        if ($user['role'] != 'admin')
            return mk_url(['mod' => $this->name]);

        switch($args['method']) {
        case 'take_away':
            $obj_id = (int)$args['object_id'];
            $obj = object_by_id($obj_id);

            $quantity = (int)$args['quantity'];
            $take = $obj['absent'] + $quantity;
            if ($take > $obj['number'])
                $take = $obj['number'];
            db()->update('objects', $obj_id, ['absent' => $take]);

            db()->query('update withdrawal_list set completed = 1 '.
                        'where obj_id = %d', $obj_id);
            return mk_url(['mod' => $this->name.'#'.$obj_id]);

        case 'remove':
            $obj_id = (int)$args['object_id'];
            db()->query('delete from withdrawal_list where obj_id = %d', $obj_id);
            return mk_url(['mod' => $this->name]);
        }

        return mk_url(['mod' => $this->name]);
    }


    function depth_color($depth) {
        $colors = ['green', 'red', 'blue', 'yellow', 'Coral', 'magenta', 'aqua'];
        $max = count($colors);
        $idx = $depth - (floor($depth / $max) * $max);
        return $colors[$idx];
    }

    function render_node($node, $depth) {
        $tpl = new strontium_tpl("private/tpl/withdrawal_list_node.html", conf()['global_marks'], false);
        $tpl->assign();

        $node_objects = $node->node_objects();
        if (count($node_objects)) {
            foreach ($node_objects as $node_obj) {
                $obj = $node_obj->obj;
                $parent_color = $this->depth_color($depth - 1);

                $photos = images_by_obj_id('objects', $obj['id']);
                $img = '';
                if (count($photos)) {
                    $photo = $photos[0];
                    $img = $photo->url('mini');
                }

                $tpl->assign('object',
                             ['name' => $obj['name'],
                              'object_id' => $obj['id'],
                              'img' => $img,
                              'quantity' => $obj['withdrawal_quantity'],
                              'form_url' => mk_url(['mod' => $this->name], 'query'),
                              'parent_color' => $parent_color,
                              'link' => mk_url(['mod' => 'object',
                                                'id' => $obj['id']])]);

                if (!$obj['withdrawal_completed'])
                    $tpl->assign('take_away',
                                 ['object_id' => $obj['id']]);
            }
        }

        $subnodes = $node->nodes();
        $number_nodes = count($subnodes);
        if ($number_nodes) {
            foreach ($subnodes as $subnode) {
                $content = $this->render_node($subnode, $depth + 1);
                $location = location_by_id($subnode->id);
                $color = $this->depth_color($depth);
                $parent_color = $this->depth_color($depth - 1);

                $link = mk_url(['mod' => 'location',
                                'id' => $location['id']]);

                $chain = location_chain_by_id($location['id']);
                $name = '';
                foreach ($chain as $item) {
                    $name .= sprintf("%s / ", $item['name']);
                }
                $tpl->assign('node',
                             ['node_content' => $content,
                              'name' => $name,
                              'color' => $color,
                              'link' => $link]);

                $photos = images_by_obj_id('locations', $location['id']);
                if (count($photos)) {
                    $photo = $photos[0];
                    $tpl->assign('location_img',
                                 ['img' => $photo->url('mini'),
                                  'color' => $color,
                                  'link' => $link]);
                }

                if ($node->parent)
                    $tpl->assign('arrow',
                                 ['parent_color' => $parent_color]);
            }
        }

        return $tpl->result();
    }
}

class Tree_node {
    function __construct($type, $id, $parent) {
        $this->type = $type;
        $this->id = $id;
        $this->sub = [];
        $this->chain = NULL;
        $this->obj = NULL;
        $this->parent = $parent;
    }

    function insert_subnode($id) {
        foreach ($this->sub as $item)
            if ($item->id == $id)
                return $item;

        $node = new Tree_node('node', $id, $this);
        $this->sub[] = $node;
        return $node;
    }

    function insert_subobject($id) {
        $obj = new Tree_node('obj', $id, $this);
        $this->sub[] = $obj;
        return $obj;
    }

    function set_chain($chain) {
        $this->chain = $chain;
    }

    function set_obj($obj) {
        $this->obj = $obj;
    }

    function find_subnode($id) {
        $nodes = $this->nodes();
        foreach ($nodes as $node) {
            if ($node->id == $id)
                return $node;

            $subnode = $node->find_subnode($id);
            if ($subnode)
                return $subnode;
        }
    }

    function node_objects() {
        $list = [];
        foreach ($this->sub as $item) {
            if ($item->type != 'obj')
                continue;
            $list[] = $item;
        }
        return $list;
    }

    function nodes() {
        $list = [];
        foreach ($this->sub as $item) {
            if ($item->type != 'node')
                continue;
            $list[] = $item;
        }
        return $list;
    }

    function dump() {
        echo sprintf("[ type: %s, id: %d ]\n --> Sub: \n", $this->type, $this->id);
        foreach ($this->sub as $item) {
            echo $item->dump();
        }
        echo "<-- \n";
    }

    function remove_empty_node() {
        $nodes = $this->nodes();

        if (count($nodes) == 0)
            return;

        foreach ($nodes as $subnode) {
            $subnode->remove_empty_node();
            $photos = images_by_obj_id('locations', $this->id);
            if ($this->parent and (!count($photos)) and count($nodes) == 1) {
                $this->parent->remove_subnode($this->id);
                $this->parent->sub[] = $subnode;
                $subnode->parent = $this->parent;
            }
        }
    }

    function remove_subnode($id) {
        foreach ($this->sub as $key => $item) {
            if ($item->type != 'node')
                continue;

            if ($item->id == $id)
                unset($this->sub[$key]);
        }
    }

}


class Tree {
    function __construct() {
        $this->root = new Tree_node('node', 0, NULL);
    }

    function insert_node($id, $parent_id, $chain) {
        if ($parent_id == 0) {
            $node = $this->root->insert_subnode($id);
            $node->set_chain($chain);
            return TRUE;
        }

        $node = $this->root->find_subnode($parent_id);
        if (!$node) {
            printf("not found sub_id: %d in id: %d\n", $parent_id, $this->root->id);
            return FALSE;
        }
        $node->insert_subnode($id);
        $node->set_chain($chain);
        return TRUE;
    }

    function insert_object($id, $parent_id, $obj) {
        if ($parent_id == 0) {
            $node = $this->root->insert_subobject($id);
            $node->set_obj($obj);
            return TRUE;
        }

        $node = $this->root->find_subnode($parent_id);
        if (!$node)
            return FALSE;
        $node_obj = $node->insert_subobject($id);
        $node_obj->set_obj($obj);
        return TRUE;
    }

    function remove_empty_nodes() {
        $nodes = $this->root->remove_empty_node();
    }

    function dump() {
        $this->root->dump();
    }
}

modules()->register('withdrawal_list', new Mod_withdrawal_list);
