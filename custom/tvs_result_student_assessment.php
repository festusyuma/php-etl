<?php
require_once ('../Db.php');

$old_db = Db::getInstance('sams_db_old');
$new_db = Db::getInstance('sams_db_new');
$classAssessments = $old_db->query("SELECT DISTINCT school_schoolId, schoolClassCode, examCode FROM schoolclass WHERE examCode IS NOT NULL AND school_schoolId=7 ORDER BY school_schoolId");
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
                                            $createdStudentAssessment = createStudentAssessment($assessment, $studentsAssessment, $resultTypesCol[$index]);
                                            if ($createdStudentAssessment) {
                                                $relationshipQuery = $new_db->query("INSERT INTO assessment_student_assessments (assessment_id, student_assessments_id) VALUES ({$assessment['id']}, {$createdStudentAssessment})");
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
    $session = $new_db->query("SELECT * FROM session WHERE description='{$resultGroup['session']}' AND sid={$schoolId} ORDER BY id DESC");

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

                $class = $new_db->query("SELECT * FROM class WHERE name='{$resultGroup['room']}' AND level_id={$level['id']} AND sid={$schoolId} AND start_date='{$session['start_date']}' AND end_date='{$session['end_date']}'");

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

function createStudentAssessment($assessment, $studentAssessment, $resultTypeCol) {
    global $new_db;
    $student = $new_db->query("SELECT * FROM student WHERE admission_number='{$studentAssessment['studentid']}'");

    if ($student and $student->num_rows > 0) {
        $student = $student->fetch_assoc();

        $data = [
            'sid' => $assessment['sid'],
            'score' => $studentAssessment[$resultTypeCol],
            'total_score' => $assessment['total_score'],
            'event_id' => $assessment['event_id'],
            'sclass_id' => $assessment['sclass_id'],
            'student_id' => $student['id'],
            'subject_id' => $assessment['subject_id'],
            'type_id' => $assessment['type_id']
        ];

        $grade_score_id = 'null';
        $remarks = '';

        $assessmentType = $new_db->query("SELECT * FROM assessment_type WHERE id = {$data['type_id']}");
        if ($assessmentType and $assessmentType->num_rows > 0) {
            $assessmentType = $assessmentType->fetch_assoc();
            $gradeScore = $new_db->query("SELECT grade_score.* FROM grade_grade_scores, grade_score WHERE grade_grade_scores.grade_id = {$assessmentType['grade_id']} AND grade_grade_scores.grade_scores_id = grade_score.id AND  grade_score.min_score < {$data['score']} AND {$data['score']} <= grade_score.max_score");
            if ($gradeScore and $gradeScore->num_rows > 0) {
                $grade = $gradeScore->fetch_assoc();
                $grade_score_id = $grade['id'];
                $remarks = strtolower($grade['remarks']);
            }
        }

        $studentAssessmentQuery = $new_db->query("INSERT INTO student_assessment (sid, score, total_score, event_id, grade_score_id, remarks, sclass_id, student_id, subject_id, type_id) 
                                                        VALUES ({$data['sid']}, {$data['score']}, {$data['total_score']}, {$data['event_id']}, {$grade_score_id}, '{$remarks}', {$data['sclass_id']}, {$data['student_id']}, {$data['subject_id']}, {$data['type_id']})");

        if ($studentAssessmentQuery) return $new_db->insert_id;
    }

    return false;
}