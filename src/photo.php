<?php

include "const.php";

$uid = intval(filter_input(INPUT_GET, "uid", FILTER_SANITIZE_NUMBER_INT));
if ($uid < 1) {
    goto err;
}

$nn = filter_input(INPUT_GET, "nn", FILTER_SANITIZE_SPECIAL_CHARS);
if ($nn) {
    $_SESSION[SESS_UID] = $uid;
    if (!preg_match(REG_NN, $nn)) {
        $nn = "??";
    }
}

function create_nnimg($nn, $img_fn) {
    $font = "res/OpenSans-Bold.ttf";
    $font_size = 22;
    $img = imagecreatetruecolor(64, 64);
    imagealphablending($img, false);
    imagefill($img, 0, 0, imagecolorallocatealpha($img, 0, 0, 0, 127));
    imagesavealpha($img, true);
    $black = imagecolorallocate($img, 0, 0, 0);
    include "hsl2rgb.php";
    $h = rand(0, 360);
    $s = rand(70, 90);
    $l = rand(60, 70);
    $rgb = hsl2rgb($h, $s / 100, $l / 100);
    imagefilledellipse($img, 32, 32, 64, 64, imagecolorallocate($img, $rgb[0], $rgb[1], $rgb[2]));
    $text_box = imagettfbbox($font_size, 0, $font, $nn);
    $text_width = $text_box[2] - $text_box[0];
    $text_height = $text_box[7] - $text_box[1];
    $x = (32) - ($text_width / 2);
    $y = (32) - ($text_height / 2);
    imagettftext($img, $font_size, 0, $x, $y, $black, $font, $nn);
    imagepng($img, $img_fn);
}

$img_out = PHOTO . $uid . PNG;

if (file_exists($img_out)) {
    goto out;
}

$res = get_file(TG_API . TG_TOKEN . "/getUserProfilePhotos",
        array("user_id" => $uid));
if ($res["code"] != 200) {
    goto err;
}

$photos = json_decode($res["body"], false);
if ($photos->ok != "ok") {
    goto err;
}

if ($photos->result->total_count < 1) { // no photo
    create_nnimg(strtoupper($nn), $img_out);
    goto out;
}

$width = PHP_INT_MAX;
$height = PHP_INT_MAX;
$file_id = "";

foreach ($photos->result->photos[0] as $photo) {
    if (($photo->width < $width) || ($photo->height < $height)) {
        $width = $photo->width;
        $height = $photo->height;
        $file_id = $photo->file_id;
    }
}
if ($file_id === "") {
    goto err;
}

$res = get_file(TG_API . TG_TOKEN . "/getFile",
        array("file_id" => $file_id));
if ($res["code"] != 200) {
    goto err;
}

$file = json_decode($res["body"], false);
if ($file->ok != "ok") {
    goto err;
}

$img_in = PHOTO . $uid . "." . pathinfo($file->result->file_path, PATHINFO_EXTENSION);
$res = get_file(TG_API . "file/" . TG_TOKEN . "/" . $file->result->file_path, [], $img_in);

if ($res["code"] != 200) {
    unlink($img_in);
    goto err;
}

include_once "./SimpleImage.php";

$mask = imagecreatetruecolor(64, 64);
imagefill($mask, 0, 0, imagecolorallocate($mask, 0, 0, 0));
imagefilledellipse($mask, 32, 32, 64, 64, imagecolorallocate($mask, 255, 255, 255));

$image = new SimpleImage();
$image->load($img_in);
$image->resize(64, 64);
$image->mask($mask);
$image->save($img_out, IMAGETYPE_PNG);

unlink($img_in);
goto out;

err:
$img_out = "img/anon.png";

out:
header(CTYPE_PNG);
readfile($img_out);
