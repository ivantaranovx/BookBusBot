<?php

$inc = true;
if (!isset($db)) {
    include "const.php";
    include "RoutesDB.php";
    $db = new RoutesDB();
    $inc = false;
} else {
    $_SESSION[SESS_CHK] = 0;
}

if (!isset($_SESSION[SESS_CHK])) {
    die("1");
}

if (!isset($_SESSION[SESS_ROUTE_NAME])) {
    die("2");
}
$route_name = $_SESSION[SESS_ROUTE_NAME];

if (!isset($_SESSION[SESS_TIME])) {
    die("3");
}
$time = $_SESSION[SESS_TIME];

$fn = upd_filename($route_name, $time);
if (!file_exists($fn)) {
    file_put_contents($fn, time());
}
$chk = file_get_contents($fn);

$pass = "null";
if ($_SESSION[SESS_CHK] != $chk) {
    $_SESSION[SESS_CHK] = $chk;
    $pass = "";
    $ps = $db->prepare("SELECT uid, seat"
            . " FROM booking"
            . " WHERE route_name = :route_name"
            . " AND time = :time"
            . " AND uid > 0");
    $ps->bindValue("route_name", $route_name, SQLITE3_TEXT);
    $ps->bindValue("time", $time, SQLITE3_TEXT);
    $res = $ps->execute();
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        if ($pass != "") {
            $pass .= ",";
        }
        $pass .= "{\"uid\": " . $row["uid"]
                . ", \"seat\": \"" . $row["seat"] . "\"}";
    }
}
if (!$inc) {
    header(CTYPE_JSON);
    echo "{\"pass\": " . (($pass == "null") ? "\"\"" : "[" . $pass . "]") . "}";
}
