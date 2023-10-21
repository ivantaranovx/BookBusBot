<?php

include "const.php";
include "RoutesDB.php";

if (!$_SESSION[SESS_DRIVER]) {
    exit;
}
$res = "ok";
try {
    $decoded = json_decode(trim(file_get_contents("php://input")), true);

    $route_name = trim($decoded["route_name"]);
    if (!preg_match(REG_NAME, $route_name)) {
        throw new Exception("route_name");
    }

    $db = new RoutesDB();

    $ps = $db->prepare("DELETE FROM routes WHERE route_name=:route_name");
    $ps->bindValue("route_name", $route_name, SQLITE3_TEXT);
    $ps->execute();

    if ($decoded["delete"]) {
        $ps = $db->prepare("DELETE FROM booking WHERE route_name=:route_name");
        $ps->bindValue("route_name", $route_name, SQLITE3_TEXT);
        $ps->execute();
    } else {
        $bus_name = trim($decoded["bus_name"]);
        if (!preg_match(REG_NAME, $bus_name)) {
            throw new Exception("bus_name");
        }
        if (count($decoded["times"]) == 0) {
            throw new Exception("times");
        }

        $ps = $db->prepare("INSERT INTO routes (route_name, day, time, bus_name)"
                . " VALUES (:route_name, :day, :time, :bus_name)");

        foreach ($decoded["times"] as $tm) {

            $day = intval($tm["day"]);
            if (($day < 0) || ($day > 6)) {
                throw new Exception("day");
            }
            if (!strtotime($tm["time"])) {
                throw new Exception("time");
            }

            $ps->bindValue("route_name", $route_name, SQLITE3_TEXT);
            $ps->bindValue("day", $day, SQLITE3_INTEGER);
            $ps->bindValue("time", $tm["time"], SQLITE3_TEXT);
            $ps->bindValue("bus_name", $bus_name, SQLITE3_TEXT);
            $ps->execute();
        }
    }
} catch (Exception $e) {
    $res = $e->getMessage();
}
echo $res;
