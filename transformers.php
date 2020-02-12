<?php
require_once ('Db.php');

function schoolIdToCode($id) {
    $db = Db::getInstance('sams_db_old');
    $school = $db->query("SELECT * FROM school WHERE schoolId=$id");

    if ($school) {
        return $school->fetch_assoc()['schoolCode'];
    }

    return false;
}

function schoolCodeToId($schoolCode) {
    $db = Db::getInstance('sams_db_old');
    $school = $db->query("SELECT * FROM school WHERE schoolCode=$schoolCode");

    if ($school) {
        return $school->fetch_assoc()['schoolId'];
    }

    return false;
}