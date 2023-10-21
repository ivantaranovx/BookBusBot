<?php

include "const.php";
include 'RoutesDB.php';

$db = new RoutesDB();

function delete_done_routes() {
    global $db;
    $ps = $db->prepare("DELETE FROM booking WHERE time<:time");
    var_dump($ps);
    $ps->bindValue("time", time(), SQLITE3_INTEGER);
    var_dump($ps->execute());
}

function is_route_planned($route_name, $time) {
    global $db;
    $ps = $db->prepare("SELECT count(*) AS count FROM booking "
            . "WHERE route_name=:route_name "
            . "AND time=:time "
            . "AND uid=0");
    $ps->bindValue("route_name", $route_name, SQLITE3_TEXT);
    $ps->bindValue("time", $time, SQLITE3_INTEGER);
    $res = $ps->execute();
    $row = $res->fetchArray(SQLITE3_ASSOC);
    $ps->close();
    return ($row["count"] > 0);
}

function plan_route($route_name, $time, $bus_name) {
    global $db;
    $ps = $db->prepare("INSERT INTO booking (route_name, bus_name, time, uid, seat) "
            . "VALUES (:route_name, :bus_name, :time, 0, '')");
    $ps->bindValue("route_name", $route_name, SQLITE3_TEXT);
    $ps->bindValue("time", $time, SQLITE3_INTEGER);
    $ps->bindValue("bus_name", $bus_name, SQLITE3_TEXT);
    $ps->execute();
}

delete_done_routes();

$today = strtotime("today");
$tomorrow = strtotime("+1 day", $today);
$end = strtotime("+2 day", $today);
$n_today = date("N", $today) - 1;
$n_tomorrow = date("N", $tomorrow) - 1;

$ps = $db->prepare("SELECT * FROM routes WHERE day=:today OR day=:tomorrow");
$ps->bindValue("today", $n_today, SQLITE3_INTEGER);
$ps->bindValue("tomorrow", $n_tomorrow, SQLITE3_INTEGER);
$res = $ps->execute();
if (!$res) {
    return;
}
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $day = $today;
    if ($row["day"] == $n_tomorrow) {
        $day = $tomorrow;
    }
    $time = strtotime($row["time"], $day);
    if ($time < time()) {
        continue;
    }
    if (is_route_planned($row["route_name"], $time)) {
        continue;
    }
    if (!bus_exists($row["bus_name"])) {
        continue;
    }
    plan_route($row["route_name"], $time, $row["bus_name"]);
}
