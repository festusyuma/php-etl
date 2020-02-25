<?php
$new_db = Db::getInstance('sams_db_new');
$values = [];
$assessments = $new_db->query("SELECT * FROM assessment_type");

if ($assessments) {
    foreach ($assessments as $assessment) {
        $values[] = [
            'assessment_type_id' => $assessment['id'],
            'event_types_id' => 4
        ];
    }
}

foreach ($values as $value) {
    $new_db->query("INSERT INTO 	assessment_type_event_types (assessment_type_id, event_types_id) VALUES ({$value['assessment_type_id']}, {$value['event_types_id']})");
}