<?php
include "const.php";
include "RoutesDB.php";

if (!$_SESSION[SESS_DRIVER]) {
    exit;
}

$buscfg = get_bus_cfg("bus_name");
if ($buscfg === "") {
    $buscfg = "{"
            . "\"bus_name\": \"\","
            . "\"width\": 3,"
            . "\"height\": 2,"
            . "\"driver\": \"0:0\","
            . "\"exclude\": []"
            . "}";
}
?>
<input type='text' class='busname' id='bus_name' placeholder='<?= STR_BUS_NAME ?>'>
<div class='divider'></div>
<div class='bus' id='bus'></div>
<div id='seatcfg'></div>
<?php
button(PAGE_TRANSPORT, STR_BACK);
?>
<!--# {
"vars": {
    "buscfg": <?= $buscfg ?>
},
"run":["drawseats", "seatscfg"]
} #-->
