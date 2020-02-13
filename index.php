<?php
require_once ('Db.php');
require_once ('transformers.php');
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
        $this->old_db = Db::getInstance('sams_db_old');
        $this->new_db = Db::getInstance('sams_db_new');
        $this->queries = $this->columns = $this->values = array();
    }

    public function setMigration($migrations) {
        $this->migrations = $migrations;
    }

    public function runMigration() {

        foreach ($this->migrations as $table=>$migration) {

            if ($migration['type'] == 'GENERALIZE') {
                $migration_obj = new GeneralizeMigration($table, $migration);
                $migration_obj->migrate();
            }else if ($migration['type'] == 'REGULAR') {
                $migration_obj = new RegularMigration($table, $migration);
                $migration_obj->migrate();
            }
        }
    }
}

$migration = [
    'config' => [
        'table' => 'assessment_type',
        'migrations' => [
            ['school_schoolId', 'sid'],
            ['template', 'name'],
            ['totalMaxScore', 'total_score']
        ]
    ],
];

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
                ['minScore', 'min_score'],
                ['maxScore', 'max_score'],
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
    ],
];

$migration = new Migration();
$migration->setMigration($migrations);
$migration->runMigration();