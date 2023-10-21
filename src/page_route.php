<?php
include "const.php";
include "RoutesDB.php";

if (!$_SESSION[SESS_DRIVER]) {
    exit;
}

function tm($t) {
    $s = strval(floor($t));
    while (strlen($s) < 2) {
        $s = "0" . $s;
    }
    return $s;
}

$route_name = get_route_name("route_name");
$bus_name = "";
$times = "";
$db = new RoutesDB();
$ps = $db->prepare("SELECT * FROM routes WHERE route_name=:route_name ORDER BY day, time");
$ps->bindValue("route_name", $route_name, SQLITE3_TEXT);
$res = $ps->execute();
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $bus_name = $row["bus_name"];
    if ($times != "") {
        $times .= ",";
    }
    $times .= "{\"day\":" . $row["day"] . ", \"time\":\"" . $row["time"] . "\"}";
}
$days = "";
foreach (ARR_DAY_NAMES as $d) {
    global $days;
    if ($days != "") {
        $days .= ",";
    }
    $days .= "\"" . $d . "\"";
}
$buses = "";
get_bus_names(function ($n) {
    global $bus_name, $buses;
    $buses .= "<option "
            . (($bus_name === $n) ? "selected" : "")
            . ">" . $n . "</option>";
});
?><input type='text' class='busname' id='route_name' 
       placeholder='<?= STR_ROUTE_NAME ?>' 
       value='<?= $route_name ?>' 
       <?= ($route_name === "") ? "" : " readonly" ?>>
<div id='times' style='display: contents'>
    <div class='timeitem' id='timeadd'>
        <div class='cfgitem add' onclick='timeadd()'></div>
    </div>
</div>
<select class='busname' id='bus_name'><?= $buses ?></select>
<div class="seatcfg">
    <div id='btn_save' class="cfgitem save" onclick='saveroute();'></div>
    <div id='btn_del' class="cfgitem del" onclick='btn_del_click(this);'></div>
</div>
<?php
button(PAGE_DRIVER, STR_BACK);
?>
<!--# {"times": [<?= $times ?>], "day_names": [<?= $days ?>]} #-->
