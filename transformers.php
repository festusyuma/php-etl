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
    $school = $db->query("SELECT * FROM school WHERE schoolCode='{$schoolCode}'");

    if ($school) {
        return $school->fetch_assoc()['schoolId'];
    }

    return false;
}

function formatGradeName($str) {
    return ucwords(strtolower($str). " grade");
}

function getConfigTotalScore($id) {
    $db = Db::getInstance('sams_db_old');
    $config = $db->query("SELECT * FROM config WHERE id={$id}");

    if ($config and $config->num_rows > 0) {
        $config = $config->fetch_assoc();
        return getConfigTotalScoreValue($config);
    }

    return 0;
}

function getGradeMinScore($id) {
    $gradeConfigDetails = getGradeConfigDetails($id);
    if ($gradeConfigDetails) {
        $grade = $gradeConfigDetails['grade'];
        $config = $gradeConfigDetails['config'];
        $configTotal = $gradeConfigDetails['configTotal'];


        if ($config['totalMaxScore'] != $configTotal) {
            $percentage = ($grade['minScore']/$config['totalMaxScore']);
            $newScore = $configTotal * $percentage;
            return $newScore == 0 ? '0' : $newScore;
        }else return $grade['minScore'] ? $grade['minScore'] : '0';
    }

    return '0';
}

function getGradeMaxScore($id) {
    $gradeConfigDetails = getGradeConfigDetails($id);

    if ($gradeConfigDetails) {
        $grade = $gradeConfigDetails['grade'];
        $config = $gradeConfigDetails['config'];
        $configTotal = $gradeConfigDetails['configTotal'];

        if ($configTotal != 0) {
            if ($config['totalMaxScore'] != $configTotal) {
                $percentage = ($grade['maxScore']/$config['totalMaxScore']);
                $newScore = $configTotal * $percentage;
                return $newScore == 0 ? '0' : $newScore;
            }else return ($grade['maxScore']) ? $grade['maxScore'] : '0';
        }
    }

    return '0';
}

function getGradeConfigDetails($id) {
    $db = Db::getInstance('sams_db_old');

    $grade = $db->query("SELECT * FROM grade WHERE id={$id}");

    if ($grade and $grade->num_rows > 0) {
        $grade = $grade->fetch_assoc();
        $schoolId = schoolCodeToId($grade['schoolCode']);
        $config = $db->query("SELECT * FROM config WHERE template='{$grade['template']}' AND school_schoolId={$schoolId}");

        if ($schoolId and $config and $config->num_rows > 0) {
            $config = $config->fetch_assoc();
            $configTotal = getConfigTotalScoreValue($config);

            return [
                'grade' => $grade,
                'config' => $config,
                'configTotal' => $configTotal
            ];
        }
    }

    return false;
}

function getConfigTotalScoreValue($config) {
    $totalScore = 0;

    switch ($config['template']) {
        case $config['resultType1']:
            $totalScore = $config['maxcore1'];
            break;
        case $config['resultType2']:
            $totalScore = $config['maxscore2'];
            break;
        case $config['resultType3']:
            $totalScore = $config['maxscore3'];
            break;
        case $config['resultType4']:
            $totalScore = $config['maxscore4'];
            break;
        case $config['resultType5']:
            $totalScore = $config['maxscore5'];
            break;
    }

    return $totalScore;
}