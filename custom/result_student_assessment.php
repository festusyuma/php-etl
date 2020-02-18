<?php
require_once ('../Db.php');

$old_db = Db::getInstance('sams_db_old');
$new_db = Db::getInstance('sams_db_new');
$rooms = $old_db->query("SELECT DISTINCT schoolCode, classCode FROM result ORDER BY schoolCode");
$classAssessments = $old_db->query("SELECT DISTINCT school_schoolId, schoolClassCode, examCode FROM schoolclass WHERE examCode IS NOT NULL ORDER BY school_schoolId");

if ($classAssessments) {
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
                    var_dump("School: {$school['schoolName']}, classCode: {$classAssessment['schoolClassCode']} result groups {$resultGroups->num_rows}");
                    foreach ($resultGroups as $resultGroup) {
                        $createdAssessment = createAssessments($resultGroup, $templates, $school['schoolId']);
                    }
                }
            }
        }
    }
}

function getTemplates($examCode, $schoolId) {
    global $old_db;
    $resultTypes = $old_db->query("SELECT * FROM config WHERE template='{$examCode}' AND school_schoolId={$schoolId}");

    if ($resultTypes and $resultTypes->num_rows > 0) {
        $resultTypes = $resultTypes->fetch_assoc();

        $templates = [
            getTemplate($resultTypes, 'resultType1'),
            getTemplate($resultTypes, 'resultType2'),
            getTemplate($resultTypes, 'resultType3'),
            getTemplate($resultTypes, 'resultType4'),
        ];

        return $templates;
    }

    return false;
}

function getTemplate($resultTypes, $resultType) {
    global $new_db;
    $template = $new_db->query("SELECT * FROM assessment_type WHERE name='{$resultTypes[$resultType]}' and sid={$resultTypes['school_schoolId']}");

    if ($template && $template->num_rows > 0) {
        return $template->fetch_assoc();
    }else return null;
}

function createAssessments($resultGroup, $templates, $schoolId) {
    global $new_db;
    $createdAssessments = [];
    $session = $new_db->query("SELECT * FROM session WHERE description='{$resultGroup['session']}' AND sid={$schoolId}");

    if ($session && $session->num_rows > 0) {
        $session = $session->fetch_assoc();
        $event = $new_db->query("SELECT
                                            school_event.*
                                        FROM
                                            session_events, school_event
                                        WHERE
                                            session_events.session_id = {$session['id']}
                                        AND session_events.events_id = school_event.id
                                        AND school_event.description = '{$resultGroup['term']}'");

        if ($event && $event->num_rows > 0) {
            //var_dump($event->fetch_assoc());
        }
        $values = [];

        foreach ($templates as $template) {
            if ($template) {
            } $createdAssessments[] = null;
        }
    }

    return $createdAssessments;
}