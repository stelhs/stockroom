<?php

require_once '/usr/local/lib/php/common.php';
require_once '/usr/local/lib/php/os.php';

class Image {
    private $id;
    private $obj_type;
    private $obj_id;
    private $hash;
    private $name;
    private $filename;
    private $extension;
    private $width;
    private $height;
    private $original_filename;

    function __construct($hash) {
        $row = db()->query('select * from images ' .
                           'where hash = "%s" and size_name = "original"', $hash);
        if (!is_array($row) || !isset($row['hash']))
            throw new Exception(sprintf('Image %s not found', $hash));

        $this->id = $row['id'];
        $this->obj_type = $row['obj_type'];
        $this->obj_id = $row['obj_id'];
        $this->hash = $row['hash'];
        $this->name = $row['name'];
        $this->filename = $row['filename'];
        $this->extension = $row['extension'];
        $this->width = $row['width'];
        $this->height = $row['height'];
        $this->original_filename = $row['original_filename'];
    }

    /**
     * Determine what size (width or height) is needed to resize the image.
     * If the width and height are set together, the image resize
     * is changed to fill full square of the image.
     * Supposed that protruding parts of the image
     * overlap by 'div overflow:hidden' setting.
     * @param $sw - width of image square
     * @param $sh - height of image square
     * @param $w - image width
     * @param $h - image height
     * @return return string:
     *         'none' - if image resizing is not required
     *         'width' - if image resizing is required only in width
     *         'height' - if image resizing is required only in height
     */
    private function resize_mode($sw, $sh, $w, $h)
    {
        if (!$sw && !$sh)
            return 'none';

        if ($sw && $sh) {
            if (($w <= $sw) || ($h <= $sh))
                return 'none';

            if ($w > $h)
                return 'height';

            if ($w < $h)
                return 'width';

            if ($w == $h) {
                if ($sw < $sh)
                    return 'height';

                if ($sw > $sh)
                    return 'width';

                if ($sw == $sh)
                    return 'none';
            }
        }
        if ($sw && ($w > $sw))
            return 'width';

        if ($sh && ($h > $sh))
            return 'height';

        return 'none';
    }

    private function resize_img_file($src_file, $dst_file, $w, $h, $jpeg_quality = '90') {
        $size = getimagesize($src_file);
        if (!is_array($size))
            return -1;
        $filetype = $size['mime'];


        switch ($filetype) {
        case 'image/jpeg':
        case 'image/pjpeg':
        case 'image/jpg':
            $src = imagecreatefromjpeg($src_file);
            break;

        case 'image/gif':
            $src = imagecreatefromgif($src_file);
            break;

        case 'image/x-png':
        case 'image/png':
            $src = imagecreatefrompng($src_file);
            break;

        default:
            return -1;
        }

        $dest = imagecreatetruecolor($w, $h);
        if (!$src || !$dest)
            return -1;

        $sh = $size[1];
        $sw = $size[0];
        imagecopyresampled($dest, $src, 0, 0, 0, 0, $w, $h, $sw, $sh);

        switch ($filetype) {
        case 'image/jpeg':
        case 'image/pjpeg':
        case 'image/jpg':
            imagejpeg($dest, $dst_file, $jpeg_quality);
            break;

        case 'image/gif':
            imagegif($dest, $dst_file);
            break;

        case 'image/x-png':
        case 'image/png':
            imagepng($dest, $dst_file);
            break;

        default:
            return -1;
        }

        @imagedestroy($src);
        @imagedestroy($dest);
        return 0;
    }


    function resize($size_name, $rect)
    {
        if ($size_name == 'original')
            return -1;

        $mode = $this->resize_mode($rect['w'], $rect['h'],
                                   $this->width, $this->height);

        switch ($mode) {
        case 'width':
            $dst_file = sprintf('%sw%d', $this->filename, $rect['w']);
            break;

        case 'height':
            $dst_file = sprintf('%sh%d', $this->filename, $rect['h']);
            break;

        case 'none':
            $dst_file = sprintf('%s%s', $this->filename, $size_name);
            copy($this->full_filename(), sprintf('%s%s.%s', $this->images_path(),
                                                 $dst_file, $this->extension));
            $dw = $this->width;
            $dh = $this->height;
            break;

        default:
            return -1;
        }

        if ($mode != 'none') {
            if (isset($rect['w']) && ($rect['w'] < $this->width)) {
                $dw = $rect['w'];
                $dh = (int)($this->height * ($rect['w'] / $this->width));
            }
            else
                if ($rect['h'] < $this->height) {
                $dw = (int)($rect['w'] * ($rect['h'] / $this->height));
                $dh = $rect['h'];
            }

            $row = db()->query('select id from images where '.
                               'hash="%s" and width=%d and height=%d',
                               $this->hash, $dw, $dh);

            if (is_array($row) && $row['id'])
                return 0;

            $rc = $this->resize_img_file($this->full_filename(),
                                         sprintf('%s%s.%s', $this->images_path(),
                                                 $dst_file, $this->extension),
                                                 $dw, $dh);
            if ($rc)
                return $rc;
        }

        db()->insert('images', ['obj_type' => $this->obj_type,
                                'obj_id' => $this->obj_id,
                                'name' => $this->name,
                                'size_name' => $size_name,
                                'hash' => $this->hash,
                                'filename' => $dst_file,
                                'extension' => $this->extension,
                                'width' => $dw,
                                'height' => $dh,
                                'original_filename' => $this->original_filename]);
    }

    function duplicate($obj_type, $obj_id)
    {
        $img_dir = sprintf('%si/obj', conf()['absolute_root_path']);
        $filename = '';
        for (;;) {
            $name = substr(sha1(sprintf('%s%d', $this->hash, time())), 0, 16);
            $filename = sprintf('%s.%s', $name, $this->extension);
            $full_filename = sprintf('%s/%s', $img_dir, $filename);
            if (!file_exists($full_filename))
                break;
        }
        $rc = copy($this->full_filename(), $full_filename);
        if (!$rc)
            return -1;

        $new_hash = sha1($full_filename);
        $id = db()->insert('images', ['obj_type' => $obj_type,
                                      'obj_id' => $obj_id,
                                      'name' => $row['name'],
                                      'size_name' => 'original',
                                      'hash' => $new_hash,
                                      'filename' => $name,
                                      'extension' => $this->extension,
                                      'width' => $this->width,
                                      'height' => $this->height,
                                      'original_filename' => $this->original_filename]);
        if (!$id)
            return -1;

        $new_image = image_by_hash($new_hash);
        $list_resized = $this->list_resized();
        foreach ($list_resized as $size_name => $size)
            $new_image->resize($size_name, $size);

        return $new_image;
    }

    function filename($size_name = NULL)
    {
        if (!$size_name)
            return sprintf('%s.%s', $this->filename, $this->extension);

        $row = db()->query('select filename, extension from images ' .
                           'where hash = "%s" and size_name = "%s"',
                            $this->hash, $size_name);
        if (!is_array($row) || !isset($row['filename']))
            return NULL;

        return sprintf('%s.%s', $row['filename'], $row['extension']);
    }

    function url($size_name = NULL)
    {
        $filename = $this->filename($size_name);
        return sprintf('%si/obj/%s', conf()['http_root_path'], $filename);
    }

    function full_filename($size_name = NULL)
    {
        return sprintf('%s%s', $this->images_path(), $this->filename($size_name));
    }

    function images_path()
    {
        return sprintf('%si/obj/', conf()['absolute_root_path']);
    }

    function remove()
    {
        $rows = db()->query_list('select * from images ' .
                                 'where hash = "%s"',
                                 $this->hash);
        if (!is_array($rows))
            return;

        foreach ($rows as $row) {
            $img_file = sprintf('%si/obj/%s.%s',
                                conf()['absolute_root_path'],
                                $row['filename'],
                                $row['extension']);
            unlink($img_file);
        }

        db()->query_list('delete from images where hash = "%s"', $this->hash);
    }

    function hash()
    {
        return $this->hash;
    }

    function list_resized()
    {
        $rows = db()->query_list('select size_name, width, height from images '.
                                 'where hash = "%s" and '.
                                     'size_name != "original"',
                                 $this->hash);
        $list = [];
        foreach ($rows as $row)
            $list[$row['size_name']] = ['w' => $row['width'],
                                        'h' => $row['height']];

        return $list;
    }
}

function images_by_obj_id($obj_type, $obj_id)
{
    $rows = db()->query_list('select hash from images ' .
                             'where obj_type = "%s" and obj_id = "%s" and size_name ="original"',
                             $obj_type, $obj_id);

    if (!is_array($rows))
        return [];
    $images = [];
    foreach ($rows as $row) {
        $images[] = new Image($row['hash']);
    }
    return $images;
}

function image_by_hash($hash)
{
    return new Image($hash);
}



function image_upload($tmp_name, $orig_name, $obj_type, $obj_id, $img_name = "")
{
    $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));
    $img_dir = sprintf('%si/obj', conf()['absolute_root_path']);
    $filename = '';
    for (;;) {
        $name = substr(sha1(sprintf('%s%s%d', $tmp_name, $orig_name, time())), 0, 16);
        $filename = sprintf('%s.%s', $name, $ext);
        $full_filename = sprintf('%s/%s', $img_dir, $filename);
        if (!file_exists($full_filename))
            break;
    }

    $rc = move_uploaded_file($tmp_name, $full_filename);
    if (!$rc) {
        printf("can't move uploaded file\n");
        return -1;
    }

    list($width, $height) = getimagesize($full_filename);
    $hash = sha1($full_filename);

    $id = db()->insert('images', ['obj_type' => $obj_type,
                                 'obj_id' => $obj_id,
                                 'name' => $img_name,
                                 'size_name' => 'original',
                                 'hash' => $hash,
                                 'filename' => $name,
                                 'extension' => $ext,
                                 'width' => $width,
                                 'height' => $height,
                                 'original_filename' => $orig_name]);

    if (!$id)
        return -1;
    return $hash;
}

function image_upload_local($src_name, $obj_type, $obj_id, $img_name = "")
{
    $ext = strtolower(pathinfo($src_name, PATHINFO_EXTENSION));
    $img_dir = sprintf('%si/obj', conf()['absolute_root_path']);
    $filename = '';
    for (;;) {
        $name = substr(sha1(sprintf('%s%s%d', $src_name, $src_name, time())), 0, 16);
        $filename = sprintf('%s.%s', $name, $ext);
        $full_filename = sprintf('%s/%s', $img_dir, $filename);
        if (!file_exists($full_filename))
            break;
    }

    $rc = copy($src_name, $full_filename);
    if (!$rc) {
        printf("can't move uploaded file\n");
        return -1;
    }

    list($width, $height) = getimagesize($full_filename);
    $hash = sha1($full_filename);

    $id = db()->insert('images', ['obj_type' => $obj_type,
                                 'obj_id' => $obj_id,
                                 'name' => $img_name,
                                 'size_name' => 'original',
                                 'hash' => $hash,
                                 'filename' => $name,
                                 'extension' => $ext,
                                 'width' => $width,
                                 'height' => $height,
                                 'original_filename' => basename($src_name)]);

    if (!$id)
        return -1;
    return $hash;
}

function images_upload_from_form($field_name, $obj_type, $obj_id)
{
    $uploaded = [];
    foreach ($_FILES[$field_name]['name'] as $num => $orig_name) {
        $tmp_name = $_FILES[$field_name]['tmp_name'][$num];
        $hash = image_upload($tmp_name, $orig_name, $obj_type, $obj_id);
        if ($hash < 0)
            return -1;
        $uploaded[] = image_by_hash($hash);
    }
    return $uploaded;
}