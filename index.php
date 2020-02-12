<?php
require_once ('Db.php');
require_once ('transformers.php');
require_once ('RegularMigration.php');

class Migration {

    private $old_db;
    private $new_db;
    private $migrations;

    private $queries;
    private $columns;
    private $values;

    public function __construct(){
        $this->old_db = Db::getInstance('sams_db_old');
        $this->new_db = Db::getInstance('test_etl');
        $this->queries = $this->columns = $this->values = array();
    }

    public function setMigration($migrations) {
        $this->migrations = $migrations;
    }

    public function runMigration() {

        foreach ($this->migrations as $table=>$migration) {

            if ($migration['type'] == 'GENERALIZE') {

            }else if ($migration['type'] == 'REGULAR') {
                $migration_obj = new RegularMigration($table, $migration);
                $built_schema = $migration_obj->buildSchema();
            }

            if (isset($built_schema) && $built_schema != null) {
                $this->queries[$table] = $built_schema['queries'];
                $this->columns[$table] = $built_schema['columns'];
                $this->values[$table] = $built_schema['values'];
            }
        }

        var_dump($this->queries, $this->columns, $this->values);
//        $this->migrate();
    }

    private function migrate() {
        $tables = array_keys($this->migrations);

        foreach ($tables as $table) {
            $data = $this->old_db->query("SELECT * FROM $table")->fetch_all(1);
            $queries = $this->queries[$table];
            $columns = $this->columns[$table];
            $values = $this->values[$table];

            foreach ($data as $row) {
                foreach ($queries as $t => $query) {
                    $this->runQuery($row, $query, $columns[$t], $values[$t]);
                }
            }
        }
    }

    private function runQuery($data, $query, $columns, $values) {

        $columns = $this->getColumns($columns);
        $values = $this->getValues($data, $values);

        $query = str_replace('{COLUMNS}', $columns, $query);
        $query = str_replace('{VALUES}', $values, $query);

        $this->new_db->query($query);
    }

    private function getColumns($columns) {
        $columns_string = implode(', ', $columns);
        return "($columns_string)";
    }

    private function getValues($data, $values) {
        $val = [];

        foreach ($values as $value) {
            $val[] = $data[$value];
        }

        $val = (array_map([$this, 'stringify'], $val));
        $val_string = implode(', ', $val);

        return "($val_string)";
    }

    private function stringify($val) {
        return "'".$val."'";
    }
}

$migrations = [
    'grade' => [
        'migrations' => [

        ],
        'type' => 'GENERALIZE',
    ],
    'result' => [
        'migrations' => [
            ['id', 'test_1.id'],
            ['id', 'test_2.id'],
            ['grade', 'test_1.col_one'],
            ['classCode', 'test_2.col_one'],
            ['remarks', 'test_1.col_three'],
        ],
        'type' => 'REGULAR',
    ],
];

$migration = new Migration();
$migration->setMigration($migrations);
$migration->runMigration();