<?php
require_once ('../Db.php');

$old_db = Db::getInstance('school_management');
$new_db = Db::getInstance('school_management_new');
$schools = $old_db->query("SELECT * FROM school");

if ($schools) {
    foreach ($schools as $school) {
        $schoolId = $school['schoolId'];
        $classes = $old_db->query("SELECT DISTINCT classCode, room, session FROM result WHERE schoolCode='{$school['schoolCode']}'");

        if ($classes) {
            foreach ($classes as $classGroup) {
                $session = $new_db->query("SELECT * FROM session WHERE description='{$classGroup['session']}' AND sid={$schoolId} ORDER BY id DESC");

                if ($session and $session->num_rows > 0) {
                    $session = $session->fetch_assoc();
                    $level = $new_db->query("SELECT * FROM level WHERE name='{$classGroup['classCode']}' AND sid={$schoolId}");

                    if ($level and $level->num_rows > 0) {
                        $level = $level->fetch_assoc();
                        $class = $new_db->query("SELECT * FROM class WHERE name='{$classGroup['room']}' AND level_id={$level['id']} AND sid={$schoolId} AND start_date='{$session['start_date']}' AND end_date='{$session['end_date']}'");
                        //todo replace class fetch with class creation
                        //todo get last insert id ($db->insert_id) and fetch the new class created
                        //todo continue with code to add students to class created

                        if ($class and $class->num_rows > 0) {
                            $class = $class->fetch_assoc();
                            $students = $old_db->query("SELECT DISTINCT studentid FROM result WHERE classCode='{$classGroup['classCode']}' AND room='{$classGroup['room']}' AND session='{$classGroup['session']}' AND schoolCode='{$school['schoolCode']}' AND total > 0 AND totalsofar > 0");

                            if ($students) {
                                $values = [];

                                foreach ($students as $student) {
                                    $studentNew = $new_db->query("SELECT * FROM student WHERE admission_number='{$student['studentid']}'");

                                    if ($studentNew and $studentNew->num_rows > 0) {
                                        $studentNew = $studentNew->fetch_assoc();
                                        $values[] = "('{$class['id']}', '{$studentNew['id']}')";
                                    }
                                }

                                $values = implode(', ', $values);
                                $new_db->query("INSERT INTO class_students (class_id, student_id) VALUES {$values}");
                            }
                        }
                    }
                }
            }
        }
    }
}