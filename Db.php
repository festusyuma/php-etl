<?php
date_default_timezone_set("Africa/Lagos");
class Db {
    private function __construct() {}
    private function __clone() {}
    public static function getInstance($db_name) {
        return mysqli_connect("localhost", "root", "", $db_name);
    }
}