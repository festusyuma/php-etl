<?php
require_once ('../Db.php');

$old_db = Db::getInstance('sams_db_old');
$new_db = Db::getInstance('sams_db_new');
$rooms = $old_db->query("SELECT DISTINCT schoolCode, classCode FROM result ORDER BY schoolCode");
$classAssessments = $old_db->query("SELECT DISTINCT school_schoolId, schoolClassCode, examCode FROM schoolclass WHERE examCode IS NOT NULL ORDER BY school_schoolId");

if ($classAssessments) {
    $classAssessments = $classAssessments->fetch_all(1);

    foreach ($classAssessments as $classAssessment) {
        $school = $old_db->query("SELECT * FROM school WHERE schoolId='{$classAssessment['school_schoolId']}'");
        if ($school and $school->num_rows > 0) {
            $school = $school->fetch_assoc();
            $templates = getTemplates($classAssessment['examCode'], $classAssessment['school_schoolId']);

            if ($templates) {
                $resultGroups = $old_db->query("SELECT DISTINCT
                                                            room, session, subject, term 
                                                        FROM result
                                                        WHERE 
                                                            classCode='{$classAssessment['schoolClassCode']}'
                                                        AND
                                                            schoolCode='{$school['schoolCode']}'
                                                        AND
                                                            room != 'NULL'");


                if ($resultGroups) {
                    foreach ($resultGroups as $resultGroup) {
                        createAssessment($resultGroup, $templates);
                    }
                }
            }
        }
    }
}

function getTemplates($examCode, $schoolId) {
    $old_db = Db::getInstance('sams_db_old');
    $new_db = Db::getInstance('sams_db_new');
    $resultTypes = $old_db->query("SELECT * FROM config WHERE template='{$examCode}' AND school_schoolId={$schoolId}");

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

        return $templates;
    }

    return false;
}

function createAssessment($resultGroup, $templates) {
    var_dump($resultGroup);
}