<?php

include "const.php";
include "RoutesDB.php";

if (!$_SESSION[SESS_DRIVER]) {
    exit;
}

get_bus_names(function ($n) {
    button(PAGE_BUS . "&bus_name=" . $n, $n);
});

button(PAGE_BUS, STR_ADD_BUS);
button(PAGE_DRIVER, STR_BACK);
