<?php
require_once ('Db.php');
require_once ('transformers.php');
require_once ('CommonMigration.php');
require_once ('RegularMigration.php');
require_once ('GeneralizeMigration.php');

class Migration {

    private $old_db;
    private $new_db;
    private $migrations;

    private $queries;
    private $columns;
    private $values;

    public function __construct(){
        $this->old_db = Db::getInstance('school_management');
        $this->new_db = Db::getInstance('school_management_new');
        $this->queries = $this->columns = $this->values = array();
    }

    public function setMigration($migrations) {
        $this->migrations = $migrations;
    }

    public function runMigration() {

        foreach ($this->migrations as $table=>$migration) {

            if ($migration['type'] == 'GENERALIZE') {
                $migration_obj = new GeneralizeMigration($table, $migration);
                $migration_obj->normalMigrate();
            }else if ($migration['type'] == 'REGULAR') {
                $migration_obj = new RegularMigration($table, $migration);
                $migration_obj->normalMigrate();
            }
        }
    }
}

$migrations_batch = [
    'grade' => [
        'table' => 'grade',
        'migrations' => [
            [['template', 'formatGradeName'], 'name'],
            [['schoolCode', 'schoolCodeToId'], 'sid'],
        ],
        'type' => 'GENERALIZE',
        'generalization' => ['template', 'schoolCode'],
        'relationship' => [
            ['schoolCode', 'config.']
        ],
        'child_migrations' => [
            'table' => 'grade_score',
            'migrations' => [
                ['gradeType', 'name'],
                ['gradeRemark', 'remarks'],
                [['id', 'getGradeMinScore'], 'min_score'],
                [['id', 'getGradeMaxScore'], 'max_score'],
                [['schoolCode', 'schoolCodeToId'], 'sid'],
            ],
            'relationship' => [
                'type' => 'external',
                'table' => 'grade_grade_scores',
                'field' => [
                    ['id', 'grade_id'],
                    ['id', 'grade_scores_id']
                ],
            ]
        ],
        'defaults' => [
            'version' => 0,
        ],
    ],
];

$migrations = [
    'config' => [
        'table' => 'assessment_type',
        'migrations' => [
            ['school_schoolId', 'sid'],
            ['template', 'name'],
            [['id', 'getConfigTotalScore'], 'total_score'],
        ],
        'type' => 'REGULAR',
        'after' => [
            'grade' => [
                'migration' => $migrations_batch['grade'],
                'data' => ['template', ['school_schoolId', 'schoolIdToCode']],
                'relationship' => [
                    'type' => 'internal',
                    'field' => ['assessment_type.grade_id', 'id'],
                ]
            ]
        ],
        'defaults' => [
            'version' => 0,
            'appear_in_result' => 1,
            'allow_multiple' => 0
        ],
    ],
];

$migration = new Migration();
$migration->setMigration($migrations);
$migration->runMigration();

require_once ('custom/grade_level.php');
require_once ('custom/assessment_type_event_type.php');