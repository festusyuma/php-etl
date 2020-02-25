<?php
$new_db = Db::getInstance('sams_db_new');
$values = [];
$schools = $new_db->query('SELECT * FROM school');

if ($schools) {
    foreach ($schools->fetch_all(1) as $school) {
        $levels = $new_db->query("SELECT * FROM level WHERE sid={$school['id']}");
        if ($levels) {
            $levels = $levels->fetch_all(1);
            $grades = $new_db->query("SELECT * FROM grade WHERE sid={$school['id']}");

            if ($grades) {
                foreach ($grades->fetch_all(1) as $grade) {
                    foreach ($levels as $level) {
                        $values[] = [
                            'grade_id' => $grade['id'],
                            'levels_id' => $level['id']
                        ];
                    }
                }
            }
        }
    }
}

foreach ($values as $value) {
    $new_db->query("INSERT INTO grade_levels (grade_id, levels_id) VALUES ({$value['grade_id']}, {$value['levels_id']})");
}