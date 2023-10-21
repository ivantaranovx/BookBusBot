<?php

class RoutesDB extends SQLite3 {

    function __construct() {
        $db_name = DB . "routes.db";
        $e = file_exists($db_name);
        $this->open($db_name);
        if (!$e) {

            $this->exec("CREATE TABLE routes (
            route_name TEXT NOT NULL,
            bus_name TEXT NOT NULL,
            day INT NOT NULL,
            time TEXT NOT NULL)");

            $this->exec("CREATE TABLE booking (
            route_name TEXT NOT NULL,
            bus_name TEXT NOT NULL,
            time INT NOT NULL,
            uid INT NOT NULL,
            seat STRING NOT NULL)");

            $this->exec("CREATE INDEX index1 ON routes (route_name)");
            $this->exec("CREATE INDEX index2 ON routes (bus_name)");
            $this->exec("CREATE INDEX index3 ON booking (route_name)");
            $this->exec("CREATE INDEX index4 ON booking (bus_name)");
            $this->exec("CREATE UNIQUE INDEX index5 ON booking (route_name,bus_name,time,seat)");
        }
    }

    function bus_name($route_name, $time) {
        $ps = $this->prepare("SELECT bus_name "
                . "FROM booking "
                . "WHERE route_name=:route_name "
                . "AND time=:time");
        $ps->bindValue("route_name", $route_name, SQLITE3_TEXT);
        $ps->bindValue("time", $time, SQLITE3_INTEGER);
        $res = $ps->execute();
        if (!$res || (!$row = $res->fetchArray(SQLITE3_ASSOC))) {
            return null;
        }
        return $row["bus_name"];
    }
}
