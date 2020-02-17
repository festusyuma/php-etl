<?php
require_once ('../Db.php');

$old_db = Db::getInstance('sams_db_old');
$new_db = Db::getInstance('sams_db_new');
$rooms = $old_db->query("SELECT DISTINCT schoolCode, classCode FROM result ORDER BY schoolCode");


$classAssessments = $old_db->query("SELECT DISTINCT school_schoolId, schoolClassCode, examCode FROM schoolclass WHERE examCode IS NOT NULL ORDER BY school_schoolId");

if ($classAssessments) {
    $classAssessments = $classAssessments->fetch_all(1);

    foreach ($classAssessments as $classAssessment) {
        $resultTypes = $old_db->query("SELECT * FROM config WHERE template='{$classAssessment['examCode']}' AND school_schoolId={$classAssessment['school_schoolId']}");

        if ($resultTypes and $resultTypes->num_rows > 0) {
            $resultTypes = $resultTypes->fetch_assoc();

            $template_one = $new_db->query("SELECT * FROM assessment_type WHERE name='{$resultTypes['resultType1']}' and sid={$resultTypes['school_schoolId']}");
            $template_two = $new_db->query("SELECT * FROM assessment_type WHERE name='{$resultTypes['resultType2']}' and sid={$resultTypes['school_schoolId']}");
            $template_three = $new_db->query("SELECT * FROM assessment_type WHERE name='{$resultTypes['resultType3']}' and sid={$resultTypes['school_schoolId']}");
            $template_four = $new_db->query("SELECT * FROM assessment_type WHERE name='{$resultTypes['resultType4']}' and sid={$resultTypes['school_schoolId']}");

            $templates = [
                $template_one,
                $template_two,
                $template_three,
                $template_four
            ];

            var_dump($templates);
        }
    }
}

if ($rooms) {
    $rooms = $rooms->fetch_all(1);
    foreach ($rooms as $room) {
        $school = $old_db->query("SELECT * FROM school WHERE schoolCode='{$room['schoolCode']}'");

        if ($school and $school->num_rows > 0) {
            $school = $school->fetch_assoc();
            $classRoom = $old_db->query("SELECT * FROM schoolClass WHERE schoolClassCode='{$room['classCode']}' AND school_schoolId={$school['schoolId']}");
            if ($classRoom and $classRoom->num_rows > 0) {
                $classRoom = $classRoom->fetch_assoc();

                if ($classRoom['examCode'] !== null) {
                    $resultTypes = $old_db->query("SELECT * FROM config WHERE template='{$classRoom['examCode']}' AND school_schoolId={$school['schoolId']}");

                    if ($resultTypes and $resultTypes->num_rows > 0) {
                        $resultTypes = $resultTypes->fetch_assoc();
                        var_dump($school['schoolCode'], $classRoom['schoolClassCode']);
                    }
                }
            }
        }
    }
}