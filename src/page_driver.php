<?php

include "const.php";
include "RoutesDB.php";

$pin = intval(filter_input(INPUT_GET, "pin", FILTER_SANITIZE_NUMBER_INT));
if ($pin === pin) {
    $_SESSION[SESS_DRIVER] = true;
}

if ($_SESSION[SESS_DRIVER]) {

    $db = new RoutesDB();
    $res = $db->query("SELECT route_name FROM routes GROUP BY route_name ORDER BY route_name");
    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
        button(PAGE_ROUTE . "&route_name=" . $row["route_name"], $row["route_name"]);
    }
    button(PAGE_ROUTE, STR_ADD_ROUTE);
    button(PAGE_TRANSPORT, STR_TRANSPORT);
}

button(PAGE_DEFAULT, STR_BACK);

