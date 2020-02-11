<?php
require_once ('Db.php');

class Migration {

    private $old_db;
    private $new_db;
    private $migrations;

    public function __construct(){
        $this->old_db = Db::getInstance('sams_db_old');
        $this->new_db = Db::getInstance('sams_db_new');
    }

    public function setMigration($migrations) {
        $this->migrations = $migrations;
    }

    public function runMigration() {
        $queries = [];
        $columns = [];
        $values = [];

        foreach ($this->migrations as $table=>$migration) {
            $old_table = $table;
            $t_queries = [];
            $t_columns = [];
            $t_values = [];

            foreach ($migration as $schema) {
                $old_column = $schema[0];
                $new_field = explode('.', $schema[1]);
                $new_table = $new_field[0];
                $new_column = $new_field[1];

                if (in_array($new_table, array_keys($t_queries))) {
                    $query = $t_queries[$new_table];
                    array_push($t_columns[$new_table], $new_column);
                    array_push($t_values[$new_table], $old_column);
                } else {
                    $query = "INSERT INTO $new_table {COLUMNS} VALUES {VALUES}";
                    $t_queries[$new_table] = $query;
                    $t_columns[$new_table] = [$new_column];
                    $t_values[$new_table] = [$old_column];
                }
            }

            $queries[$old_table] = $t_queries;
            $columns[$old_table] = $t_columns;
            $values[$old_table] = $t_values;
        }

        var_dump($queries, $columns, $values);
    }

    private function regularMigration() {

    }
    
    /*private function regularMigration($old_table, $old_column, $new_field) {

        $new_table = $new_field[0];
        $new_column = $new_field[1];
        $data = $this->old_db->query("SELECT id, $old_column FROM $old_table");

        if ($data) {
            $old_fields = $data->fetch_all(1);

            foreach ($old_fields as $field) {
                $id = $field[0];
                $value = $field[1];
                $found_field = $this->new_db->query("SELECT * FROM $new_table");

                if ($found_field and $found_field->num_rows > 0) {
                    $q = "UPDATE $new_table SET $new_column = $value";
                }else $q = "INSERT INTO $new_table  ";
            }
        }
    }*/
}

$migrations = [
    'result' => [
        ['grade', 'test_1.col_one'],
        ['class_code', 'test_2.col_one'],
        ['remarks', 'test_1.col_two'],
    ]
];

$migration = new Migration();
$migration->setMigration($migrations);
$migration->runMigration();