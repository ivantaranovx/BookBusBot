<?php

include "const.php";

$uid = intval(filter_input(INPUT_GET, "uid", FILTER_SANITIZE_NUMBER_INT));
if ($uid < 1) {
    goto err;
}

if (isset($_GET["first_name"])) {
    $_SESSION[SESS_UID] = $uid;
    $_SESSION[SESS_FIRST_NAME] = filter_input(INPUT_GET, "first_name", FILTER_SANITIZE_SPECIAL_CHARS);
    $_SESSION[SESS_LAST_NAME] = filter_input(INPUT_GET, "last_name", FILTER_SANITIZE_SPECIAL_CHARS);
    $_SESSION[SESS_USERNAME] = filter_input(INPUT_GET, "username", FILTER_SANITIZE_SPECIAL_CHARS);
    $_SESSION[SESS_PHOTO_URL] = filter_input(INPUT_GET, "photo_url", FILTER_SANITIZE_SPECIAL_CHARS);

    $myfile = fopen(PHOTO . $_SESSION[SESS_UID] . ".txt", "w");
    fwrite($myfile, "first_name:" . $_SESSION[SESS_FIRST_NAME] . "\n");
    fwrite($myfile, "last_name:" . $_SESSION[SESS_LAST_NAME] . "\n");
    fwrite($myfile, "username:" . $_SESSION[SESS_USERNAME] . "\n");
    fwrite($myfile, "photo_url:" . $_SESSION[SESS_PHOTO_URL] . "\n");
    fclose($myfile);
}

function get_char($str) {
    $chars = mb_str_split($str);
    if (count($chars) == 0)
        return "";
    for ($i = 0; $i < count($chars); $i++) {
        if (strlen($chars[$i]) == 1)
            return $chars[$i];
    }
    return ":)";
}

function get_short_name() {
    $n1 = get_char($_SESSION[SESS_FIRST_NAME]);
    $n2 = get_char($_SESSION[SESS_LAST_NAME]);
    $n3 = get_char($_SESSION[SESS_USERNAME]);
    if ((strlen($n1) == 1) && (strlen($n2) == 1)) {
        return strtoupper($n1) . strtoupper($n2);
    }
    if (strlen($n1) == 1) {
        return strtoupper($n1);
    }
    if (strlen($n2) == 1) {
        return strtoupper($n2);
    }
    if (strlen($n3) == 1) {
        return strtoupper($n3);
    }
    return ":)";
}

function create_name_img($img_fn) {
    $img_name = get_short_name();
    $font = "res/NotoSansDisplay-Bold.ttf";
    $font_size = 22;
    $img = imagecreatetruecolor(64, 64);
    imagealphablending($img, false);
    imagefill($img, 0, 0, imagecolorallocatealpha($img, 0, 0, 0, 127));
    imagesavealpha($img, true);
    include "hsl2rgb.php";
    $h = rand(0, 360);
    $s = rand(70, 90);
    $l = rand(60, 70);
    $rgb = hsl2rgb($h, $s / 100, $l / 100);
    imagefilledellipse($img, 32, 32, 64, 64, imagecolorallocate($img, $rgb[0], $rgb[1], $rgb[2]));
    $text_box = imagettfbbox($font_size, 0, $font, $img_name);
    $text_width = $text_box[2] - $text_box[0];
    $text_height = $text_box[7] - $text_box[1];
    $x = (32) - ($text_width / 2);
    $y = (32) - ($text_height / 2);
    imagettftext($img, $font_size, 0, $x, $y, imagecolorallocate($img, 0, 0, 0), $font, $img_name);
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
    create_name_img($img_out);
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
