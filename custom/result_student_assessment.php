<?php
require_once ('../Db.php');

$old_db = Db::getInstance('sams_db_old');
$new_db = Db::getInstance('sams_db_new');
$rooms = $old_db->query("SELECT DISTINCT schoolCode, classCode FROM result ORDER BY schoolCode");
$classAssessments = $old_db->query("SELECT DISTINCT school_schoolId, schoolClassCode, examCode FROM schoolclass WHERE examCode IS NOT NULL ORDER BY school_schoolId");
$resultTypesCol = ['resulttype1', 'resulttype2', 'resulttype3', 'resulttype4'];

if ($classAssessments) {
    foreach ($classAssessments as $classAssessment) {
        $school = $old_db->query("SELECT * FROM school WHERE schoolId='{$classAssessment['school_schoolId']}'");
        if ($school and $school->num_rows > 0) {
            $school = $school->fetch_assoc();
            $templates = getTemplates($classAssessment['examCode'], $classAssessment['school_schoolId']);
            $classCode = $classAssessment['schoolClassCode'];

            if ($templates) {
                $resultGroups = $old_db->query("SELECT DISTINCT
                                                            room, session, subject, term 
                                                        FROM result
                                                        WHERE 
                                                            classCode='{$classCode}'
                                                        AND
                                                            schoolCode='{$school['schoolCode']}'
                                                        AND
                                                            room IS NOT NULL");


                if ($resultGroups) {
                    foreach ($resultGroups as $resultGroup) {
                        $assessmentData = getAssessmentData($resultGroup, $school['schoolId'], $classCode);
                        $studentsAssessments = $old_db->query("SELECT * FROM result WHERE classCode='{$classCode}' AND room='{$assessmentData['class']['name']}' AND session='{$assessmentData['session']['description']}' AND term='{$assessmentData['event']['title']}' AND subject='{$assessmentData['subject']['code']}' AND schoolCode='{$school['schoolCode']}'");

                        if ($studentsAssessments) {
                            $studentsAssessments = $studentsAssessments->fetch_all(1);
                            foreach ($templates as $index => $template) {
                                if ($template and $template != null) {
                                    $assessment = createAssessment($assessmentData, $template);
                                    if ($assessment) {
                                        foreach ($studentsAssessments as $studentsAssessment) {
                                            //todo populate score for assessment
                                        }
                                    }
                                }
                            }
                        }
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

function getAssessmentData($resultGroup, $schoolId, $classCode) {
    global $new_db;
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
            $event = $event->fetch_assoc();
            $level = $new_db->query("SELECT * FROM level WHERE name='{$classCode}' AND sid={$schoolId}");

            if ($level and $level->num_rows > 0) {
                $level = $level->fetch_assoc();

                $class = $new_db->query("SELECT * FROM class WHERE name='{$resultGroup['room']}' AND level_id={$level['id']} AND sid={$schoolId}");

                if ($class and  $class->num_rows > 0) {
                    $class = $class->fetch_assoc();
                    $subject = $new_db->query("SELECT * FROM subject WHERE code='{$resultGroup['subject']}' AND sid={$schoolId}");

                    if ($subject and $subject->num_rows > 0) {
                        $subject = $subject->fetch_assoc();

                        return [
                            'class' => $class,
                            'subject' => $subject,
                            'event' => $event,
                            'session' => $session,
                        ];
                    }
                }
            }
        }
    }

    return false;
}

function createAssessment($assessmentData, $template) {
    global $new_db;

    $sid = $template['sid'];
    $name = ucfirst(strtolower("{$template['name']} Assessment"));
    $total_score = $template['total_score'];
    $event_id = $assessmentData['event']['id'];
    $class_id = $assessmentData['class']['id'];
    $subject_id = $assessmentData['subject']['id'];
    $type_id = $template['id'];

    $assessment = $new_db->query("INSERT INTO assessment (sid, name, total_score, event_id, sclass_id, subject_id, type_id) VALUES ({$sid}, '{$name}', {$total_score}, {$event_id}, {$class_id}, {$subject_id}, {$type_id})");

    if ($assessment) {
        $id = $new_db->insert_id;
        $assessment = $new_db->query("SELECT * FROM assessment WHERE id={$id}");
        return $assessment->fetch_assoc();
    }

    return false;
}

function mapStudentAssessment() {

}