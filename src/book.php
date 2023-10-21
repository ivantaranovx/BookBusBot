<?php

include "const.php";
include "RoutesDB.php";

if (!isset($_SESSION[SESS_UID]) || ($_SESSION[SESS_UID] == 0)) {
    die("have a nice day!");
}

function get_uid($sid) {
    global $db;
    $ps = $db->prepare("SELECT uid FROM booking"
            . " WHERE route_name=:route_name"
            . " AND bus_name=:bus_name"
            . " AND time=:time"
            . " AND seat=:seat");
    $ps->bindValue("route_name", $_SESSION[SESS_ROUTE_NAME], SQLITE3_TEXT);
    $ps->bindValue("bus_name", $_SESSION[SESS_BUS_NAME], SQLITE3_TEXT);
    $ps->bindValue("time", $_SESSION[SESS_TIME], SQLITE3_INTEGER);
    $ps->bindValue("seat", $sid, SQLITE3_TEXT);
    $res = $ps->execute();
    if (!$res) {
        throw new Exception("get_uid");
    }
    $row = $res->fetchArray(SQLITE3_ASSOC);
    $id = 0;
    if ($row) {
        $id = $row["uid"];
    }
    return $id;
}

function get_count() {
    global $db;
    $ps = $db->prepare("SELECT count(*) AS count"
            . " FROM booking"
            . " WHERE route_name=:route_name"
            . " AND bus_name=:bus_name"
            . " AND time=:time");
    $ps->bindValue("route_name", $_SESSION[SESS_ROUTE_NAME], SQLITE3_TEXT);
    $ps->bindValue("bus_name", $_SESSION[SESS_BUS_NAME], SQLITE3_TEXT);
    $ps->bindValue("time", $_SESSION[SESS_TIME], SQLITE3_INTEGER);
    $res = $ps->execute();
    if (!$res) {
        throw new Exception("get_count");
    }
    $row = $res->fetchArray(SQLITE3_ASSOC);
    $count = 0;
    if ($row) {
        $count = $row["count"];
    }
    return $count;
}

try {
    $decoded = json_decode(trim(file_get_contents("php://input")), true);

    $db = new RoutesDB();
    $bus_name = $db->bus_name($_SESSION[SESS_ROUTE_NAME], $_SESSION[SESS_TIME]);
    if (!$bus_name) {
        throw new Exception("bus_name");
    }
    $buscfg = json_decode(bus_cfg($bus_name));
    if (!$buscfg) {
        throw new Exception("bus_cfg");
    }

    $sid = trim($decoded["sid"]);
    if (!preg_match(REG_SEAT, $sid)) {
        throw new Exception("sid");
    }
    $s = explode(":", $sid);
    $y = intval($s[0]);
    $x = intval($s[1]);
    if (($x > $buscfg->width) || ($y > $buscfg->height) || ($buscfg->driver == $sid) || array_search($sid, $buscfg->exclude)) {
        throw new Exception("sid invalid");
    }

    $id = get_uid($sid);

    if ($_SESSION[SESS_UID] == $id) {
        $ps = $db->prepare("DELETE FROM booking"
                . " WHERE route_name=:route_name"
                . " AND bus_name=:bus_name"
                . " AND time=:time"
                . " AND uid=:uid"
                . " AND seat=:seat");
        $res = "cancel";
    } else
    if (0 == $id) {
        $ps = $db->prepare("INSERT INTO booking"
                . " (route_name, bus_name, time, uid, seat)"
                . " VALUES (:route_name, :bus_name, :time, :uid, :seat)");
        $res = "accept";
    } else {
        throw new Exception("busy " . $id);
    }
    $ps->bindValue("route_name", $_SESSION[SESS_ROUTE_NAME], SQLITE3_TEXT);
    $ps->bindValue("bus_name", $bus_name, SQLITE3_TEXT);
    $ps->bindValue("time", $_SESSION[SESS_TIME], SQLITE3_INTEGER);
    $ps->bindValue("uid", $_SESSION[SESS_UID], SQLITE3_INTEGER);
    $ps->bindValue("seat", $sid, SQLITE3_TEXT);
    if (!$ps->execute()) {
        $id = get_uid($_SESSION[SESS_ROUTE_NAME], $_SESSION[SESS_TIME], $sid);
        throw new Exception("busy " . $id);
    }
    $_SESSION[SESS_CHK] = time();
    file_put_contents(upd_filename($_SESSION[SESS_ROUTE_NAME], $_SESSION[SESS_TIME]), $_SESSION[SESS_CHK]);
    $total = ($buscfg->width * $buscfg->height) - count($buscfg->exclude) - 1;
    $count = get_count() - 1;
    include "tgSendMessage.php";
    tgSendMessage($_SESSION[SESS_ROUTE_NAME]
            . " @" . date("j M H:i", $_SESSION[SESS_TIME])
            . " " . $count . "/" . $total);
} catch (Exception $e) {
    $res = $e->getMessage();
}
echo $res;
