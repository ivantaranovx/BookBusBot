<?php
include "const.php";
include "RoutesDB.php";

if (!$_SESSION[SESS_DRIVER]) {
    exit;
}

$content = trim(file_get_contents("php://input"));
$res = "ok";
if (strlen($content) == 0) {
    throw new Exception("content");
}
try {
    $decoded = json_decode($content, true);
    $bus_name = trim($decoded["bus_name"]);
    if (!preg_match(REG_NAME, $bus_name)) {
        throw new Exception("bus_name");
    }
    if ($decoded["delete"]) {
        unlink(DB . $bus_name . JSON);
        $db = new RoutesDB();
        $db->exec("DELETE FROM routes WHERE bus_name='" . $bus_name . "'");
        $db->exec("DELETE FROM booking WHERE bus_name='" . $bus_name . "'");
    } else {
        if ($decoded["width"] < 1) {
            $decoded["width"] = 1;
        }
        if ($decoded["width"] > 5) {
            $decoded["width"] = 5;
        }
        if ($decoded["height"] < 1) {
            $decoded["height"] = 1;
        }
        if ($decoded["height"] > 20) {
            $decoded["height"] = 20;
        }
        unset($decoded["delete"]);
        if (!file_put_contents(DB . $bus_name . JSON, json_encode($decoded))) {
            throw new Exception("write");
        }
    }
} catch (Exception $e) {
    $res = $e->getMessage();
}
echo $res;

