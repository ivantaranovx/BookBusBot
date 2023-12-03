<?php

include "./private.php";

session_start();

const LANG = "lang_";
const PAGE = "page_";
const PHP = ".php";
const PNG = ".png";
const JSON = ".json";
const CHK = ".chk";
const DB = "db/";
const PHOTO = "photo/";

const REG_PAGE = "/^[a-z]+$/";
const REG_NAME = "/^[a-zA-Z0-9\s-]{5,}$/";
const REG_SEAT = "/^[0-9]+:[0-9]+$/";
const REG_NN = "/^([а-яА-ЯЁёa-zA-Z0-9_]{1,2})$/u";

const SESS_LANG = "lang";
const LANG_DEFAULT = "default";

const SESS_PAGE = "page";
const PAGE_DEFAULT = "default";
const PAGE_DRIVER = "driver";
const PAGE_TRANSPORT = "transport";
const PAGE_BUS = "bus";
const PAGE_ROUTE = "route";
const PAGE_BOOK = "book";

const CTYPE_PNG = "Content-Type: image/png";
const CTYPE_JSON = "Content-Type: application/json; charset=UTF-8";

const SESS_UID = "uid";
const SESS_ROUTE_NAME = "route_name";
const SESS_BUS_NAME = "bus_name";
const SESS_TIME = "time";
const SESS_CHK = "chk";
const SESS_DRIVER = "driver";

const SESS_FIRST_NAME = "first_name";
const SESS_LAST_NAME = "last_name";
const SESS_USERNAME = "username";
const SESS_PHOTO_URL = "photo_url";

function button($link, $text) {
    global $btn_id;
    if (!isset($btn_id)) {
        $btn_id = 0;
    }
    echo "<div class='route-item' id='btn$btn_id' onclick='page(\"$link\",this)'>$text</div>";
    $btn_id++;
}

function get_route_name($var) {
    $route_name = (string) filter_input(INPUT_GET, $var, FILTER_SANITIZE_SPECIAL_CHARS);
    if (!preg_match(REG_NAME, $route_name)) {
        $route_name = "";
    }
    return $route_name;
}

function bus_exists($bus_name) {
    if (!preg_match(REG_NAME, $bus_name) || !file_exists(DB . $bus_name . JSON)) {
        return false;
    }
    return true;
}

function bus_cfg($bus_name) {
    if (!bus_exists($bus_name)) {
        return "";
    }
    return file_get_contents(DB . $bus_name . JSON);
}

function get_bus_cfg($var) {
    return bus_cfg((string) filter_input(INPUT_GET, $var, FILTER_SANITIZE_SPECIAL_CHARS));
}

function get_bus_names($cb) {
    $names = glob(DB . "*" . JSON);
    natsort($names);
    foreach ($names as $name) {
        $n = basename($name, JSON);
        $cb($n);
    }
}

function upd_filename($route_name, $time) {
    return DB . $route_name . "." . $time . CHK;
}

function get_file($url, $data = [], $file = "") {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    if (count($data) > 0) {
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    if ($file != "") {
        $f = fopen($file, "w");
        curl_setopt($curl, CURLOPT_FILE, $f);
    }
    $res["body"] = curl_exec($curl);
    $res["code"] = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    if (isset($f)) {
        fclose($f);
    }
    return $res;
}

if (!isset($_SESSION[SESS_DRIVER])) {
    $_SESSION[SESS_DRIVER] = false;
}

if (!isset($_SESSION[SESS_UID])) {
    $_SESSION[SESS_UID] = 0;
}

$lang = (string) filter_input(INPUT_GET, "lang", FILTER_SANITIZE_SPECIAL_CHARS);
if (!preg_match(REG_PAGE, $lang)) {
    $lang = LANG_DEFAULT;
}
if (!file_exists(LANG . $lang . PHP)) {
    $lang = LANG_DEFAULT;
}
include LANG . $lang . PHP;

const ARR_DAY_NAMES = [STR_MO, STR_TU, STR_WE, STR_TH, STR_FR, STR_SA, STR_SU];
