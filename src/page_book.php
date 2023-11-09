<?php
include "const.php";
include "RoutesDB.php";

if ($_SESSION[SESS_UID] == 0) {
    //goto out;
}
$route_name = get_route_name("route_name");
if ($route_name === "") {
    goto out;
}
$time = intval(filter_input(INPUT_GET, "time", FILTER_SANITIZE_NUMBER_INT));
if ($time == 0) {
    goto out;
}

$_SESSION[SESS_ROUTE_NAME] = $route_name;
$_SESSION[SESS_TIME] = $time;

$db = new RoutesDB();
include "./pass.php";

$bus_name = $db->bus_name($route_name, $time);
if (!$bus_name) {
    exit;
}
$buscfg = bus_cfg($bus_name);
if ($buscfg === "") {
    goto out;
}
$_SESSION[SESS_BUS_NAME] = $bus_name;
?><div class='busname'><?= $route_name ?>  @<?= date("j M H:i", $time) ?><br><?= $bus_name ?></div>
<p id='book_label'><?= TAP_TO_BOOKING ?></p>
<div class='bus' id='bus'></div>
<!--# {
"vars": {
    "buscfg": <?= $buscfg ?>,
    "pass": [<?= $pass ?>],
    "TAP_TO_BOOKING": "<?= TAP_TO_BOOKING ?>",
    "STR_PROCESS": "<?= STR_PROCESS ?>",
    "STR_DONE": "<?= STR_DONE ?>",
    "STR_BUSY": "<?= STR_BUSY ?>"
},
"run": ["drawseats", "draw_pass"]
} #-->
<?php
out:
button(PAGE_DEFAULT, STR_BACK);
