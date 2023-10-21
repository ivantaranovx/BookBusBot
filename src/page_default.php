<?php
include "const.php";
include "RoutesDB.php";

$db = new RoutesDB();
$res = $db->query("SELECT b.route_name, b.time, b.bus_name, x.count"
        . " FROM booking b"
        . " LEFT OUTER JOIN ("
        . " SELECT count(*) AS count, route_name, bus_name, time"
        . " FROM booking GROUP BY route_name, bus_name, time"
        . " ) x ON x.route_name = b.route_name AND x.bus_name = b.bus_name AND x.time = b.time"
        . " WHERE b.uid = 0"
        . " AND b.seat = ''"
        . " ORDER BY b.time");
while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
    $count = "";
    $buscfg = json_decode(bus_cfg($row["bus_name"]));
    if ($buscfg != "") {
        $count = " (" . ($row["count"] - 1) . "/"
                . (($buscfg->width * $buscfg->height) - count($buscfg->exclude) - 1) . ")";
    }
    button(PAGE_BOOK . "&route_name=" . $row["route_name"] . "&time=" . $row["time"],
            $row["route_name"] . " @" . date("j M H:i", $row["time"])
            . "<br>" . $row["bus_name"]
            . $count);
}
?>
<div class='route-item'>
    <input type='password' id='driver_pin'>
    <div onclick='page("<?= PAGE_DRIVER ?>", this)'><?= STR_IM_A_DRIVER ?></div>
</div>
